<?php

namespace Yubikey;

class Response
{
    /**
     * Returned value of the hash given in the request.
     *
     * @var string
     */
    private $h;

    /**
     * Timestamp in UTC of request.
     *
     * @var string
     */
    private $t;

    /**
     * OTP given in request.
     *
     * @var string
     */
    private $otp;

    /**
     * Nonce given in the request.
     *
     * @var string
     */
    private $nonce;

    /**
     * Percent of validation servers that replied with "success" (0% - 100%).
     *
     * @var int
     */
    private $sl;

    /**
     * Status of the response (see constants below).
     *
     * @var string
     */
    private $status;

    /**
     * OTP given by the user (used for verification).
     *
     * @var string
     */
    private $inputOtp;

    /**
     * Nonce used in the request (used for verification).
     *
     * @var string
     */
    private $inputNonce;

    /**
     * Timestamp returned from the response.
     *
     * @var string
     */
    private $timestamp;

    /**
     * Session counter.
     *
     * @var int
     */
    private $sessioncounter;

    /**
     * Session use #.
     *
     * @var int
     */
    private $sessionuse;

    /**
     * Hostname request was made to.
     *
     * @var string
     */
    private $host;

    /**
     * Microtime difference taken to get response.
     *
     * @var int
     */
    private $mt;

    /**
     * Define constants for the return status from API.
     */
    public const SUCCESS = 'OK';
    public const REPLAY_OTP = 'REPLAYED_OTP';
    public const REPLAY_REQUEST = 'REPLAYED_REQUEST';
    public const MISSING_PARAMETER = 'MISSING_PARAMETER';
    public const NO_CLIENT = 'NO_SUCH_CLIENT';
    public const BAD_OTP = 'BAD_OTP';
    public const BAD_SIGNATURE = 'BAD_SIGNATURE';
    public const OP_NOT_ALLOWED = 'OPERATION_NOT_ALLOWED';
    public const BACKEND_ERROR = 'BACKEND_ERROR';
    public const NOT_ENOUGH_ANSWERS = 'NOT_ENOUGH_ANSWERS';

    /**
     * Init the object and set the data into the response.
     *
     * @param array $data Data from the Yubi API response
     */
    public function __construct($data = null)
    {
        if ($data !== null) {
            $this->load($data);
        }
    }

    /**
     * Load the data into the object.
     *
     * @param array $data Data from the object
     *
     * @return bool True when loading is done
     */
    public function load($data)
    {
        foreach ($data as $index => $value) {
            if (property_exists($this, $index)) {
                $this->{$index} = trim($value);
            }
        }

        return true;
    }

    /**
     * Parse the return data from the request and
     *     load it into the object properties.
     *
     * @param string $data API return data
     */
    public function parse($data): void
    {
        $result = [];
        $parts = explode("\n", $data);

        foreach ($parts as $index => $part) {
            $kv = explode('=', $part);
            if (!empty($kv[1])) {
                $result[$kv[0]] = $kv[1];
            }
        }

        $this->load($result);
    }

    /**
     * Get the time value for the response.
     *
     * @return string Date/time string
     */
    public function getTime()
    {
        return $this->t;
    }

    /**
     * Return the time to execute (microtime).
     *
     * @return int Time result
     */
    public function getMt()
    {
        return $this->mt;
    }

    /**
     * Set the OTP used in the request.
     *
     * @param string $otp OTP string (from key)
     */
    public function setInputOtp($otp)
    {
        $this->inputOtp = $otp;

        return $this;
    }

    /**
     * Get the OTP used in the request.
     *
     * @return string OTP string
     */
    public function getInputOtp()
    {
        return $this->inputOtp;
    }

    /**
     * Set the nonce used in the request.
     *
     * @param string $nonce Nonce from request
     */
    public function setInputNonce($nonce)
    {
        $this->inputNonce = $nonce;

        return $this;
    }

    /**
     * Get the nonce used in the request.
     *
     * @return string Nonce from request
     */
    public function getInputNonce()
    {
        return $this->inputNonce;
    }

    /**
     * Set response hostname.
     *
     * @param string $host Hostname
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get the current hostname.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get the hash from the response.
     *
     * @param bool $encode "Encode" the data (replace + with %2B)
     *
     * @return string Hash value
     */
    public function getHash($encode = false)
    {
        $hash = $this->h;
        if (!str_ends_with($hash, '=')) {
            $hash .= '=';
        }
        if ($encode === true) {
            $hash = str_replace('+', '%2B', $hash);
        }

        return $hash;
    }

    /**
     * Get the properties of the response.
     *
     * @return array Response property list
     */
    public function getProperties()
    {
        return ['t', 'otp', 'nonce', 'sl', 'status', 'timestamp', 'sessioncounter', 'sessionuse'];
    }

    /**
     * Magic method to get access to the private properties.
     *
     * @param string $name Property name
     *
     * @return null|string If found, returns teh value. If not, null
     */
    public function __get($name)
    {
        return (property_exists($this, $name)) ? $this->{$name} : null;
    }

    /**
     * Check the success of the response
     *     Validates: status, OTP and nonce.
     *
     * @return bool Success/fail of request
     */
    public function success()
    {
        $inputOtp = $this->getInputOtp();
        $inputNonce = $this->getInputNonce();

        if ($inputNonce === null || $inputOtp === null) {
            return false;
        }

        return $inputOtp === $this->otp
        && $inputNonce === $this->nonce
        && $this->status === self::SUCCESS;
    }
}
