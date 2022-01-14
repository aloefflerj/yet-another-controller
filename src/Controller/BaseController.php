<?php

namespace Aloefflerj\YetAnotherController\Controller;

use Aloefflerj\YetAnotherController\Controller\Helpers\HttpHelper;
use Aloefflerj\YetAnotherController\Controller\Helpers\StringHelper;
use Aloefflerj\YetAnotherController\Controller\Helpers\UrlHelper;
use Aloefflerj\YetAnotherController\Controller\Routes\RouteFunctionMethodsInterface;
use Aloefflerj\YetAnotherController\Controller\Routes\Routes;
use Aloefflerj\YetAnotherController\Controller\Url\UrlHandler;

class BaseController
// class BaseController implements ControllerInterface
{
    use StringHelper;
    use UrlHelper;
    use HttpHelper;

    public Routes $routes;
    private array $routeFunctions;
    private array $data;
    private UrlHandler $urlHandler;
    private \Exception $error;

    public function __construct()
    {
        $this->urlHandler = new UrlHandler();
        $this->routes = new Routes();
        $this->routeFunctions = $this->httpArrayMethods();
    }

    function __call($name, array $params)
    {
        if (!in_array($name, $this->routeFunctions)) {
            throw new \Error('Call to undefined method');
        }

        if (count($params) === 3) {
            [$uri, $output, $functionParams] = $params;
        } else {
            [$uri, $output] = $params;
            $functionParams = null;
        }

        if (!is_string($uri)) {
            throw new \TypeError(
                $this->typeErrorMsg($name, 'string', 1, $uri)
            );
            return;
        }

        if (!is_callable($output)) {
            throw new \TypeError(
                $this->typeErrorMsg($name, 'closure', 2, $output)
            );
            return;
        }

        if ($functionParams && !is_array($functionParams)) {
            throw new \TypeError(
                $this->typeErrorMsg($name, 'array', 3, $functionParams)
            );
            return;
        }

        $routes = $this->routes->$name($uri, $output, $functionParams)->add();
        if ($routes->error()) {
            $this->error = $routes->error();
        }
        return $this;

    }

    public function get(string $uri, \closure $output, ?array $functionParams = null): BaseController
    {
        $routes = $this->routes->get($uri, $output, $functionParams)->add();
        if ($routes->error()) {
            $this->error = $routes->error();
        }
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function dispatch(): BaseController
    {
        if ($this->error()) {
            echo $this->error()->getMessage();
            die();
        }

        $currentRouteName = $this->getCurrentRouteName();

        // Check if it is mapped
        if (!$this->routeExists($currentRouteName)) {
            $this->error = new \Exception("Error 404", 404);
            return $this;
        }

        $currentRoute = $this->routes->getRouteByName($currentRouteName);

        $dispatch = $this->routes->dispatchRoute($currentRoute);
        if (!$dispatch) {
            $this->error = new \Exception("Error 405", 405);
            return $this;
        }

        return $this;
    }

    public function error(): ?\Exception
    {
        return $this->error ?? null;
    }

    /**
     * @param string $name
     * @param string $type
     * @param mixed $param
     * @return string
     */
    private function typeErrorMsg(string $functionName, string $type, int $argNumber, $param): string
    {
        $class = __CLASS__;
        $wrongType = gettype($param);

        return <<<ERROR
            Argument $argNumber passed to $class::$functionName() must be of the type $type, $wrongType given;
        ERROR;
    }

    /**
     * ||================================================================||
     *                          HELPER FUNCTIONS
     * ||================================================================||
     * 
     */

    /**
     * @return Routes[]|null
     */
    public function getRoutes()
    {
        return $this->routes ?? null;
    }

    private function getCurrentRouteName(): ?string
    {
        $currentUri = $this->urlHandler->getUriPath();

        $currentRoute = $this->routes->getCurrent($currentUri);

        return $currentRoute;
    }

    private function routeExists(string $currentRoute): bool
    {
        $requestMethod = $this->getRequestMethod();
        return array_key_exists($currentRoute, $this->routes->$requestMethod);
    }
}
