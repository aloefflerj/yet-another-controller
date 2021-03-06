<?php

namespace Aloefflerj\YetAnotherController\Controller\Http;

use Aloefflerj\YetAnotherController\Controller\PSR\MessageInterface;
use Aloefflerj\YetAnotherController\Controller\PSR\StreamInterface;

class Message implements MessageInterface
{
    use Headers;

    /**
     * @var string|array[]
     */
    private $headers;
    private string $protocolVersion;
    private $body;

    public function __construct()
    {
        $this->protocolVersion = '1.0';
        $this->headers = [];
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): self
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        if (empty($name) || !array_key_exists($name, $this->headers)) {
            return false;
        }

        return true;
    }

    public function getHeader($name)
    {
        $name = strtolower($name);

        if (array_key_exists($name, $this->headers)) {
            return $this->headers[$name];
        }

        return [];
    }

    public function getHeaderLine($name)
    {
        $name = strtolower($name);

        if (array_key_exists($name, $this->headers)) {
            return is_array($this->headers[$name]) ? implode(',', $this->headers[$name]) : $this->headers[$name];
        }

        return '';
    }

    /**
     * @param string $name
     * @param string|string[] $value
     * @throws \InvalidArgumentException
     * @return self
     */
    public function withHeader($name, $value): self
    {
        if (!in_array(strtolower($name), $this->getValidHeaders())) {
            throw new \InvalidArgumentException('This header name does not exists');
        }

        $clone = clone $this;
        $clone->headers = [$name => $value];
        return $clone;
    }

    public function withAddedHeader($name, $value)
    {
        if (!in_array(strtolower($name), $this->getValidHeaders())) {
            throw new \InvalidArgumentException('This header name does not exists');
        }

        if (!isset($this->headers[$name])) {
            return $this->withHeader($name, $value);
        }

        $clone = clone $this;

        if (is_array($clone->headers[$name])) {
            if (is_string($value)) {
                $clone->headers[$name][] = $value;
            }
            
            if (is_array($value)) {
                $clone->headers[$name] = array_merge($clone->headers[$name], $value);
            }
        }

        if (is_string($clone->headers[$name])) {
            $headerOldValue = $clone->headers[$name];
            $clone->headers[$name] = [];
            $clone->headers[$name][] = $headerOldValue;
            $clone->headers[$name][] = $value;
        }

        return $clone;
    }

    public function withoutHeader($name)
    {
        $name = strtolower($name);

        if (empty($this->headers[$name])) {
            //throw new exception
        }

        $clone = clone $this;

        unset($clone->headers[$name]);

        return $clone;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body)
    {
        $clone = clone $this;

        $clone->body = $body;

        return $clone;
    }
}
