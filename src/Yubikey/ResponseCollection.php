<?php

namespace Yubikey;

class ResponseCollection implements \Countable, \Iterator, \ArrayAccess
{
    /**
     * Set of \Yubikey\Response objects.
     */
    private array $responses = [];

    /**
     * Position in the data set (for Iterator).
     */
    private int $position = 0;

    /**
     * Init the object and set the Response objects if provided.
     *
     * @param array $responses Response object set
     */
    public function __construct(array $responses = [])
    {
        if (!empty($responses)) {
            foreach ($responses as $response) {
                $this->add($response);
            }
        }
    }

    /**
     * Determine, based on the Response status (success)
     *     if the overall operation was successful.
     *
     * @param mixed $first
     *
     * @return bool Success/fail status
     */
    public function success($first = false)
    {
        $success = false;
        if ($first === true) {
            // Sort them by timestamp, pop the first one and return pass/fail
            usort($this->responses, fn (\Yubikey\Response $r1, \Yubikey\Response $r2) => $r1->getMt() > $r2->getMt());
            $response = $this->responses[0];

            return $response->success();
        }
        foreach ($this->responses as $response) {
            if ($response->success() === false
                && $response->status !== Response::REPLAY_REQUEST) {
                return false;
            }
            if ($response->success()) {
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Add a new Response object to the set.
     *
     * @param \Yubikey\Response $response Response object
     */
    public function add(Response $response): void
    {
        $this->responses[] = $response;
    }

    /**
     * For Countable.
     *
     * @return int Count of current Requests
     */
    public function count(): int
    {
        return \count($this->responses);
    }

    /**
     * For Iterator.
     *
     * @return Response Current Request object
     */
    public function current(): Response
    {
        return $this->responses[$this->position];
    }

    /**
     * For Iterator.
     *
     * @return int Current position in set
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * For Iterator, move forward to next element.
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * For Iterator, rewind set location to beginning.
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * For Iterator, check to see if set item is valid.
     *
     * @return bool Valid/invalid result
     */
    public function valid(): bool
    {
        return isset($this->responses[$this->position]);
    }

    /**
     * For ArrayAccess.
     *
     * @param mixed $offset Offset identifier
     *
     * @return bool Found/not found result
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->responses[$offset]);
    }

    /**
     * For ArrayAccess.
     *
     * @param mixed $offset Offset to locate
     *
     * @return \Yubikey\Request object if found
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->responses[$offset];
    }

    /**
     * For ArrayAccess.
     *
     * @param mixed $offset Offset to use in data set
     * @param mixed $data   Data to assign
     */
    public function offsetSet(mixed $offset, mixed $data): void
    {
        $this->responses[$offset] = $data;
    }

    /**
     * For ArrayAccess.
     *
     * @param mixed $offset Offset to remove
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->responses[$offset]);
    }
}
