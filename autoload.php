<?php

spl_autoload_register(function($class) {
    if (false !== stripos($class, 'Young')) {
        require_once __DIR__ ."/src/". str_replace('\\', '/', substr($class, 6)) . ".php";
    }
});