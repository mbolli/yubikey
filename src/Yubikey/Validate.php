<?php

namespace Yubikey;

class Validate
{
    /**
     * Yubico API hosts.
     */
    private array $hosts = [
        'api.yubico.com',
        'api2.yubico.com',
        'api3.yubico.com',
        'api4.yubico.com',
        'api5.yubico.com',
    ];

    /**
     * Selected hosted for request.
     */
    private ?string $host = null;

    /**
     * API given for request.
     */
    private ?string $apiKey = null;

    /**
     * Use a secure/insecure connection (HTTPS vs HTTP).
     */
    private bool $useSecure = true;

    /**
     * OTP provided by user.
     */
    private ?string $otp = null;

    /**
     * Yubikey ID, to identify a connected user.
     */
    private ?string $yubikeyid = null;
    private ?int $clientId = null;

    /**
     * Init the object and set the API key, Client ID and optionally hosts.
     *
     * @param string     $apiKey   API Key
     * @param int|string $clientId Client ID
     * @param array      $hosts    Set of hostnames (overwrites current)
     *
     * @throws \DomainException If curl is not enabled
     */
    public function __construct(string $apiKey, int|string $clientId, array $hosts = [])
    {
        if ($this->checkCurlSupport() === false) {
            throw new \DomainException('cURL support is required and is not enabled!');
        }

        $this->setApiKey($apiKey);
        $this->setClientId((int) $clientId);

        if (!empty($hosts)) {
            $this->setHosts($hosts);
        }
    }

    /**
     * Check for enabled curl support (requirement).
     *
     * @return bool Enabled/not found flag
     */
    public function checkCurlSupport(): bool
    {
        return \function_exists('curl_init');
    }

    /**
     * Get the currently set API key.
     *
     * @return null|string API key
     */
    public function getApiKey(?bool $decoded = false): ?string
    {
        return ($decoded === false) ? $this->apiKey : base64_decode($this->apiKey, true);
    }

    /**
     * Set the API key.
     *
     * @param string $apiKey API request key
     */
    public function setApiKey(string $apiKey): self
    {
        $key = base64_decode($apiKey, true);
        if ($key === false) {
            throw new \InvalidArgumentException('Invalid API key');
        }

        $this->apiKey = $key;

        return $this;
    }

    /**
     * Set the OTP for the request.
     *
     * @param string $otp One-time password
     */
    public function setOtp(string $otp): self
    {
        $this->otp = $otp;

        return $this;
    }

    /**
     * Get the currently set OTP.
     *
     * @return string One-time password
     */
    public function getOtp(): string
    {
        return $this->otp;
    }

    /**
     * Get the current Client ID.
     *
     * @return null|int Client ID
     */
    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    /**
     * Set the current Client ID.
     *
     * @param int|string $clientId Client ID
     */
    public function setClientId(string|int $clientId): self
    {
        $this->clientId = (int) $clientId;

        return $this;
    }

    /**
     * Get the "use secure" setting.
     *
     * @return bool Use flag
     */
    public function getUseSecure(): bool
    {
        return $this->useSecure;
    }

    /**
     * Set the "use secure" setting.
     *
     * @param bool $use Use/don't use secure
     *
     * @throws \InvalidArgumentException when value is not boolean
     */
    public function setUseSecure(?bool $use = null): self
    {
        if (!\is_bool($use)) {
            throw new \InvalidArgumentException('"Use secure" value must be boolean');
        }
        $this->useSecure = $use;

        return $this;
    }

    /**
     * Get the host for the request
     *     If one is not set, it returns a random one from the host set.
     *
     * @return string Hostname string
     */
    public function getHost(): string
    {
        if ($this->host === null) {
            // pick a "random" host
            $host = $this->hosts[random_int(0, \count($this->hosts) - 1)];
            $this->setHost($host);

            return $host;
        }

        return $this->host;
    }

    /**
     * Get the current hosts list.
     *
     * @return array Hosts list
     */
    public function getHosts(): array
    {
        return $this->hosts;
    }

    /**
     * Set the API host for the request.
     *
     * @param string $host Hostname
     */
    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Add a new host to the list.
     *
     * @param string $host Hostname to add
     */
    public function addHost(string $host): self
    {
        $this->hosts[] = $host;

        return $this;
    }

    /**
     * Set the hosts to request results from.
     *
     * @param array $hosts Set of hostnames
     */
    public function setHosts(array $hosts): void
    {
        $this->hosts = $hosts;
    }

    /**
     * Geenrate the signature for the request values.
     *
     * @param array $data Data for request
     *
     * @return string Hashed request signature
     *
     * @throws \InvalidArgumentException when API key is invalid
     */
    public function generateSignature(array $data, ?string $key = null): string
    {
        if ($key === null) {
            $key = $this->getApiKey();
            if (empty($key)) {
                throw new \InvalidArgumentException('Invalid API key!');
            }
        }

        $query = http_build_query($data);
        $query = str_replace('%3A', ':', $query);

        if (\PHP_VERSION_ID >= 80200) {
            $query = \function_exists('mb_convert_encoding') ? mb_convert_encoding($query, 'UTF-8') : $query;
        } else {
            $query = utf8_encode($query);
        }

        return preg_replace(
            '/\+/',
            '%2B',
            // base64_encode(hash_hmac('sha1', http_build_query($data), $key, true))
            base64_encode(hash_hmac('sha1', $query, $key, true))
        );
    }

    /**
     * Check the One-time Password with API request.
     *
     * @param string $otp   One-time password
     * @param bool   $multi Client ID for API
     *
     * @return \Yubikey\ResponseCollection object
     *
     * @throws \InvalidArgumentException when OTP length is invalid
     */
    public function check(string $otp, ?bool $multi = false): ResponseCollection
    {
        $otp = trim($otp);
        if (\strlen($otp) < 32 || \strlen($otp) > 48) {
            throw new \InvalidArgumentException('Invalid OTP length');
        }

        $this->setOtp($otp);
        $this->setYubikeyId();

        $clientId = $this->getClientId();
        if ($clientId === null) {
            throw new \InvalidArgumentException('Client ID cannot be null');
        }

        $nonce = $this->generateNonce();
        $params = ['id' => $clientId, 'otp' => $otp, 'nonce' => $nonce, 'timestamp' => '1'];
        ksort($params);

        $url = '/wsapi/2.0/verify?'.http_build_query($params).'&h='.$this->generateSignature($params);
        $hosts = ($multi === false) ? [array_shift($this->hosts)] : $this->hosts;

        return $this->request($url, $hosts, $otp, $nonce);
    }

    /**
     * Generate a good nonce for the request.
     *
     * @return string Generated hash
     */
    public function generateNonce(): string
    {
        if (\function_exists('openssl_random_pseudo_bytes') === true) {
            $hash = md5(openssl_random_pseudo_bytes(32));
        } else {
            $hash = md5(uniqid(random_int(0, mt_getrandmax())));
        }

        return $hash;
    }

    /**
     * Make the request(s) to the Yubi server(s).
     *
     * @param string $url   URL for request
     * @param array  $hosts Set of hosts to request
     * @param string $otp   One-time password string
     * @param string $nonce Generated nonce
     *
     * @return array Set of responses
     */
    public function request(string $url, array $hosts, string $otp, string $nonce): ResponseCollection
    {
        $client = new \Yubikey\Client();
        $pool = new \Yubikey\RequestCollection();

        // Make the requests for the host(s)
        $prefix = ($this->getUseSecure() === true) ? 'https' : 'http';
        foreach ($hosts as $host) {
            $link = $prefix.'://'.$host.$url;
            $pool->add(new \Yubikey\Request($link));
        }
        $responses = $client->send($pool);
        $responseCount = \count($responses);

        for ($i = 0; $i < $responseCount; ++$i) {
            $responses[$i]->setInputOtp($otp)->setInputNonce($nonce);

            if ($this->validateResponseSignature($responses[$i]) === false) {
                unset($responses[$i]);
            }
        }

        return $responses;
    }

    /**
     * Validate the signature on the response.
     *
     * @param \Yubikey\Response $response Response instance
     *
     * @return bool Pass/fail status of signature validation
     */
    public function validateResponseSignature(Response $response): bool
    {
        $params = [];
        foreach ($response->getProperties() as $property) {
            $value = $response->{$property};
            if ($value !== null) {
                $params[$property] = $value;
            }
        }
        ksort($params);

        $signature = $this->generateSignature($params);

        return hash_equals($signature, $response->getHash(true));
    }

    /**
     * Extract the yubikey ID from the OTP.
     */
    public function setYubikeyId(): self
    {
        $this->yubikeyid = substr($this->getOtp(), 0, -32);

        return $this;
    }

    /**
     * Get the yubikey ID from the OTP.
     *
     * @param string Optional OTP to extract the ID from
     *
     * @return string Yubikey ID string
     */
    public function getYubikeyId(?string $otp = '')
    {
        if (!empty($otp)) {
            return substr($otp, 0, -32);
        }

        return $this->yubikeyid;
    }
}
