<?php

namespace Young\Support;

/**
 * 数据库操作类
 * 使用方法：
 * DB::getInstance($conf)->query('select * from table');
 * 其中$conf是一个关联数组，需要包含以下key：
 * DB_HOST DB_USER DB_PWD DB_NAME
 * 可以用DB_PORT和DB_CHARSET来指定端口和编码，默认3306和utf8
 */
class DB
{
    /**
     * 数据库链接
     * @var resource
     */
    private $_db;
    /**
     * 保存最后一条sql
     * @var string
     */
    private $_lastSql;
    /**
     * 上次sql语句影响的行数
     * @var int
     */
    private $_rows;
    /**
     * 上次sql执行的错误
     * @var string
     */
    private $_error;
    /**
     * 实例数组
     * @var array
     */
    private static $_instance = array();

    /**
     * 构造函数
     * @param array $dbConf 配置数组
     */
    private function __construct($dbConf)
    {
        if (!isset($dbConf['DB_CHARSET'])) {
            $dbConf['DB_CHARSET'] = 'utf8';
        }
        $this->_db = mysql_connect($dbConf['DB_HOST'] . ':' . $dbConf['DB_PORT'], $dbConf['DB_USER'], $dbConf['DB_PWD']);
        if ($this->_db === false) {
            halt(mysql_error());
        }
        $selectDb = mysql_select_db($dbConf['DB_NAME'], $this->_db);
        if ($selectDb === false) {
            halt(mysql_error());
        }
        mysql_set_charset($dbConf['DB_CHARSET']);
    }

    private function __clone()
    {
    }

    /**
     * 获取DB类
     * @param array $dbConf 配置数组
     * @return DB
     */
    static public function getInstance($dbConf)
    {
        if (!isset($dbConf['DB_PORT'])) {
            $dbConf['DB_PORT'] = '3306';
        }
        $key = $dbConf['DB_HOST'] . ':' . $dbConf['DB_PORT'];
        if (!isset(self::$_instance[$key]) || !(self::$_instance[$key] instanceof self)) {
            self::$_instance[$key] = new self($dbConf);
        }
        return self::$_instance[$key];
    }

    /**
     * 转义字符串
     * @param string $str 要转义的字符串
     * @return string 转义后的字符串
     */
    public function escape($str)
    {
        return mysql_real_escape_string($str, $this->_db);
    }

    /**
     * 查询，用于select语句
     * @param string $sql 要查询的sql
     * @return bool|array 查询成功返回对应数组，失败返回false
     */
    public function query($sql)
    {
        $this->_rows = 0;
        $this->_error = '';
        $this->_lastSql = $sql;
        $this->logSql();
        $res = mysql_query($sql, $this->_db);
        if ($res === false) {
            $this->_error = mysql_error($this->_db);
            $this->logError();
            return false;
        } else {
            $this->_rows = mysql_num_rows($res);
            $result = array();
            if ($this->_rows > 0) {
                while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
                    $result[] = $row;
                }
                mysql_data_seek($res, 0);
            }
            return $result;
        }
    }

    /**
     * 查询，用于insert/update/delete语句
     * @param string $sql 要查询的sql
     * @return bool|int 查询成功返回影响的记录数量，失败返回false
     */
    public function execute($sql)
    {
        $this->_rows = 0;
        $this->_error = '';
        $this->_lastSql = $sql;
        $this->logSql();
        $result = mysql_query($sql, $this->_db);
        if (false === $result) {
            $this->_error = mysql_error($this->_db);
            $this->logError();
            return false;
        } else {
            $this->_rows = mysql_affected_rows($this->_db);
            return $this->_rows;
        }
    }

    /**
     * 获取上一次查询影响的记录数量
     * @return int 影响的记录数量
     */
    public function getRows()
    {
        return $this->_rows;
    }

    /**
     * 获取上一次insert后生成的自增id
     * @return int 自增ID
     */
    public function getInsertId()
    {
        return mysql_insert_id($this->_db);
    }

    /**
     * 获取上一次查询的sql
     * @return string sql
     */
    public function getLastSql()
    {
        return $this->_lastSql;
    }

    /**
     * 获取上一次查询的错误信息
     * @return string 错误信息
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * 记录sql到文件
     */
    private function logSql()
    {
        Log::sql($this->_lastSql);
    }

    /**
     * 记录错误日志到文件
     */
    private function logError()
    {
        $str = '[SQL ERR]' . $this->_error . ' SQL:' . $this->_lastSql;
        Log::warn($str);
    }
}
