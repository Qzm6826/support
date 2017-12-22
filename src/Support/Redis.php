<?php

namespace Young\Support;

use Redis as BaseRedis;
use RedisException;
use Young\Support\Traits\StaticCall;

/**
 * Class Redis
 *
 * @package Young\Support
 */
class Redis
{
    use StaticCall;

    /**
     * @var array
     */
    private $config = array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'timeout' => 30,

        'db' => 0,
    );

    /**
     * @var bool
     */
    private $auth = false;

    /**
     * @var \Redis
     */
    private static $instance;

    /**
     * @param array $config
     * @return mixed
     * @throws RedisException
     */
    public function init(array $config = array())
    {
        $this->initConfig($config);
        $this->initAuth(!empty($this->config['password']));

        $instance = new BaseRedis();

        try {
            $instance->connect($this->config['host'], $this->config['port'], $this->config['timeout']);

            if ($this->auth) {
                $instance->auth($this->config['password']);
            }

            $instance->select($this->config['db']);
        } catch (RedisException $e) {
            throw $e;
        }

        return $instance;
    }

    /**
     * @param array $config
     *
     * @return mixed
     *
     * @throws RedisException
     */
    public static function getInstance(array $config = array())
    {
        $self = new self();

        return self::$instance ?: self::$instance = $self->init($config);
    }

    /**
     * @param array $config
     */
    protected function initConfig($config)
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * @param bool $auth
     */
    protected function initAuth($auth = false)
    {
        $this->auth = $auth;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $expire_at unix timestamp
     * @return bool
     */
    public function staticPut($key, $value, $expire_at = 0)
    {
        $result = self::$instance->set($key, $this->encode($value));
        if ($result && $expire_at) {
            return self::$instance->expireAt($key, $expire_at);
        }
        return $result;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function staticRetrieve($key)
    {
        $data = self::$instance->get($key);
        return $this->decode($data);
    }

    /**
     * @param $data
     * @return string
     */
    protected function encode($data)
    {
        return serialize($data);
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function decode($data)
    {
        return unserialize($data);
    }
}
