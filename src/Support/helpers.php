<?php

if (! function_exists('debug')) {
    defined('debug_log') or define('debug_log', 'log');
    function debug($callback, $filePath = '')
    {
        ob_start();
        if (is_callable($callback)) {
            $callback();
        } elseif(is_string($callback)) {
            echo($callback);
        } elseif(is_array($callback)) {
            var_export($callback);
        }
        $data = ob_get_contents();
        ob_end_clean();

        if ($filePath) {
            $filePath = rtrim($filePath, '/') . DIRECTORY_SEPARATOR . debug_log;
        } else {
            $filePath = debug_log;
        }
        return file_put_contents($filePath, $data."\n", FILE_APPEND);
    }
}
