<?php

namespace Young\Support;

class Log
{
    const LOG_PATH = __DIR__.'/../log';

    /**
     * 打日志，支持SAE环境
     *
     * @param string $logFile 日志文件名
     * @param string $msg 日志内容
     * @param string $level 日志等级
     */
    public static function write($logFile, $msg, $level='DEBUG'){
        if(function_exists('sae_debug')){ //如果是SAE，则使用sae_debug函数打日志
            $msg = "[{$level}]".$msg;
            sae_set_display_errors(false);
            sae_debug(trim($msg));
            sae_set_display_errors(true);
        }else{
            $msg = date('[ Y-m-d H:i:s ]')."[{$level}]".$msg."\r\n";
            $logPath = sprintf('%s/%s%s.log', self::LOG_PATH, $logFile, date('Ymd'));

            if (! is_dir(self::LOG_PATH)) {
                mkdir(self::LOG_PATH);
            }

            file_put_contents($logPath, $msg, FILE_APPEND);
        }
    }

    public static function remove($logFile)
    {
        $oldDate = date('Ymd', (time() - 5 * 86400));

        $logPath = sprintf('%s/%s%s.log', self::LOG_PATH, $logFile, $oldDate);
        if (is_file($logPath)) {
            unlink($logPath);
        }
    }
}
