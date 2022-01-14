<?php

namespace Aloefflerj\YetAnotherController\Controller\Helpers;

trait HttpHelper
{
    public function httpArrayMethods(): array
    {
        return ['get', 'post', 'put', 'delete'];
    }
}