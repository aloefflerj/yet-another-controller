<?php

namespace Aloefflerj\YetAnotherController\Controller;

/**
 * @property $name 
 */
interface ControllerInterface
{
    function get(array $params);

    function post(array $params, $body);

    function put(array $params, $body);

    function patch(array $params, $body);

    function delete(array $params);

    function dispatch();

}