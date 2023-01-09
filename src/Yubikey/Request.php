<?php

namespace Yubikey;

class Request
{
    /**
     * Request HTTP type (verb).
     */
    private string $type = 'GET';

    /**
     * Request URL location.
     */
    private ?string $url = null;

    /**
     * Init the object and set the URL if given.
     *
     * @param string|null $url URL to request
     */
    public function __construct(?string $url = null)
    {
        if ($url !== null) {
            $this->setUrl($url);
        }
    }

    /**
     * Get the type of request.
     *
     * @return string HTTP verb type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the type of the request.
     *
     * @param string $type HTTP verb type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the current request URL location.
     *
     * @return string|null URL location
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set the URL location for the request.
     *
     * @param string $url URL location
     */
    public function setUrl(string $url): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) !== $url) {
            throw new \Exception('Invalid URL: '.$url);
        }
        $this->url = $url;
    }
}
