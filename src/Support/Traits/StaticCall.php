<?php

namespace Young\Support\Traits;

use Exception;

Trait StaticCall
{
    /**
     * @param string $method
     * @param array $args
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function __callStatic($method, $args)
    {
        $method = 'static'.ucfirst($method);

        $instance = new static();
        if(!method_exists(get_class($instance), $method)) {
            throw new Exception(sprintf('not found method %s in instance %s', $method, $instance));
        }
        return call_user_func_array(array($instance, $method), $args);
    }
}