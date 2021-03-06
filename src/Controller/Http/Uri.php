<?php

namespace Aloefflerj\YetAnotherController\Controller\Http;

use Aloefflerj\YetAnotherController\Controller\PSR\UriInterface;

class Uri
// class Uri implements UriInterface
{
    use Schemes;

    private string $completeUri;
    private string $scheme;
    private string $authority;
    private string $userInfo;
    private string $host;
    private ?int $port;
    private string $path;
    private string $query;
    private string $fragment;

    /**
     * @throws \Exception
     */
    public function __construct(string $uri = "")
    {
        $validUri = preg_match("/\w+:(\/?\/?)[^\s]+/", $uri);

        if (!$validUri) {
            throw new \Exception('This is not a valid uri');
        }

        $this->completeUri = $uri;
        $this->split();
    }

    public function __toString()
    {
        return $this->glueElements();
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @throws \InvalidArgumentException 
     */
    public function withScheme(string $scheme): self
    {
        if (!preg_match(
            '/^[\da-z][\da-z\-]{1,20}$/',
            $scheme
        )) {
            throw new \InvalidArgumentException('This scheme in not valid');
        }

        if (!in_array($scheme, $this->getValidSchemes())) {
            throw new \InvalidArgumentException('This scheme is not supported');
        }

        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    public function withUserInfo(string $user, ?string $password): self
    {
        $clone = clone $this;
        $clone->userInfo = "{$user}:{$password}";

        return $clone;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function withHost(string $host): self
    {
        if (!preg_match(
            '/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/',
            $host
        )) {
            throw new \InvalidArgumentException('Invalid host');
        }

        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function withPort(?int $port): self
    {
        if (!is_null($port) && !preg_match('/^\d{1,4}$/', $port)) {
            throw new \InvalidArgumentException('Invalid uri port');
        }

        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function withPath(string $path): self
    {
        if (!preg_match('/(\/[a-z0-9]*).*/', $path)) {
            throw new \InvalidArgumentException('Invalid uri path');
        }

        $clone = clone $this;
        $clone->path = $path;

        return $clone;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function withQuery(string $query): self
    {
        if (!preg_match('/^([^=]+=[^=]+&)+[^=]+(=[^=]+)?$/', $query)) {
            throw new \InvalidArgumentException('Invalid uri query');
        }

        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    public function withFragment(string $fragment): self
    {
        $fragment = filter_var($fragment);

        $clone = clone $this;
        $clone->fragment = $fragment;

        return $clone;
    }

    public function getAuthority(): string
    {
        return $this->authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    # HELPER FUNCTIONS #

    private function split(): void
    {
        $this->scheme       = explode(':', $this->completeUri)[0];
        $this->authority    = $this->splitToAuthority();
        $this->userInfo     = $this->splitToUserInfo();
        $this->host         = $this->splitToHost();
        $this->port         = $this->splitToPort();
        $this->path         = $this->splitToPath();
        $this->query        = $this->splitToQuery();
        $this->fragment     = $this->splitToFragment();
        $this->completeUri  = $this->glueElements();
    }

    private function splitToAuthority(): string
    {
        $authorityArr = explode(':', $this->completeUri);
        $authority = "$authorityArr[1]";

        if (isset($authorityArr[2])) {
            $authority .= ":" . $authorityArr[2];
        }

        $authority = str_replace('//', '', $authority);

        if (strpos($authority, '/')) {
            $authority = explode('/', $authority)[0];
        }

        return $authority;
    }

    private function splitToUserInfo(): string
    {
        $userInfo = '';
        if (strpos($this->completeUri, '@')) {
            $userInfo = explode('@', $this->authority)[0];
        }

        return $userInfo;
    }

    private function splitToHost(): string
    {
        $host = '';
        $authority = $this->authority;
        $host = $authority;

        if (strpos($authority, '@')) {
            $host = explode('@', $authority)[1];
            return strtolower($host);
        }

        if (strpos($authority, ':')) {
            $host = explode(':', $authority)[0];
        }

        return strtolower($host);
    }

    private function splitToPort(): ?int
    {
        $port = null;
        $authority = $this->authority;

        if (strpos($authority, ':') && !strpos($authority, '@')) {
            $port = explode(':', $authority)[1];
            if (strpos($port, '/')) {
                $port = explode('/', $port)[0];
            }
        }

        return $port;
    }

    private function splitToPath(): string
    {
        $pathArr = explode('/', $this->completeUri);
        $pathArr = array_slice($pathArr, 3);
        $path = '/' . implode('/', $pathArr);

        if (strpos($path, '?')) {
            $path = explode('?', $path)[0];
        }

        return $path;
    }

    private function splitToQuery(): string
    {
        $uri = $this->completeUri;
        if (!strpos($uri, '?')) {
            return '';
        }

        if (strpos($uri, '#')) {
            $uri = explode('#', $uri)[0];
        }

        $query = explode('?', $uri)[1];
        return $query;
    }

    private function splitToFragment(): string
    {
        if (!strpos($this->completeUri, '#')) {
            return '';
        }

        return explode('#', $this->completeUri)[1];
    }

    private function glueElements(): string
    {
        $scheme     = $this->getScheme() ?? '';
        $authority  = $this->getAuthority() ?? '';
        $path       = $this->getPath() ?? '';
        $query      = $this->getQuery() ? "?{$this->getQuery()}" : '';
        $fragment   = $this->getFragment() ? "#{$this->getFragment()}" : '';

        return "{$scheme}://{$authority}{$path}{$query}{$fragment}";
    }
}
