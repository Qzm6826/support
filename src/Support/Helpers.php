<?php

namespace Young\Support;

/**
 * Class Helpers
 * @package Young\Support
 */
/**
 * Class Helpers
 * @package Young\Support
 */
class Helpers
{
    /**
     * @param $seconds
     * @return mixed
     */
    public static function getSeconds($seconds)
    {
        return $seconds;
    }

    /**
     * @param $minutes
     * @return mixed
     */
    public static function getMinutes($minutes)
    {
        $seconds = $minutes * 60;
        return static::getSeconds($seconds);
    }

    /**
     * @param $hours
     * @return mixed
     */
    public static function getHours($hours)
    {
        $minutes = $hours * 60;
        return static::getMinutes($minutes);
    }

    /**
     * @param int $seconds
     * @param null $time
     * @return int|mixed
     */
    public static function timestampAddSeconds($seconds, $time = null)
    {
        $time = $time ?: time();
        return $time + static::getSeconds($seconds);
    }

    /**
     * @param int $hours
     * @return int|mixed
     */
    public static function getUnixExpireAt($hours = 2)
    {
        return static::timestampAddSeconds(
            static::getHours($hours)
        );
    }
}
