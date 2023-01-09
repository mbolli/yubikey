<?php

namespace Yubikey;

class RequestCollection implements \Countable, \Iterator, \ArrayAccess
{
    /**
     * Set of \Yubikey\Request objects.
     */
    private array $requests = [];

    /**
     * Current array position.
     */
    private int $position = 0;

    /**
     * Init the collection and set requests data if given.
     *
     * @param array $requests Set of \Yubikey\Requests objects
     */
    public function __construct(array $requests = [])
    {
        if (!empty($requests)) {
            foreach ($requests as $request) {
                $this->add($request);
            }
        }
    }

    /**
     * Add the given request to the set.
     *
     * @param \Yubikey\Request $request Request object
     */
    public function add(Request $request): void
    {
        $this->requests[] = $request;
    }

    /**
     * For Countable.
     *
     * @return int Count of current Requests
     */
    public function count(): int
    {
        return \count($this->requests);
    }

    /**
     * For Iterator.
     *
     * @return Request Current Request object
     */
    public function current(): Request
    {
        return $this->requests[$this->position];
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
     * For iterator, move forward to next element.
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
        return isset($this->requests[$this->position]);
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
        return isset($this->requests[$offset]);
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
        return $this->requests[$offset];
    }

    /**
     * For ArrayAccess.
     *
     * @param mixed $offset Offset to use in data set
     * @param mixed $data   Data to assign
     */
    public function offsetSet(mixed $offset, mixed $data): void
    {
        $this->requests[$offset] = $data;
    }

    /**
     * For ArrayAccess.
     *
     * @param mixed $offset Offset to remove
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->requests[$offset]);
    }
}
