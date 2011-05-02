<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * cache system using redis.
 *
 * Cache/Redis provides a fast, light and safe cache system.
 * It's replacement of Cache_Lite (file cache) to redis (memory cache).
 *
 * PHP version 5.3 or newer
 * reqiurement:
 *  redis            http://redis.io/
 *  owlient-phpredis http://github.com/owlient/phpredis
 *
 * @category  Cache/Redis
 * @package   Cache_Redis
 * @author    xHARUKAx <https://github.com/xHARUKAx>
 * @copyright 2011 xHARUKAx
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version   Git: $Id$
 * @see       https://github.com/nicolasff/phpredis nicolasff-phpredis
 * @link      Cache_Redis_Exception
 */

/*
 * files to require_once
 */
require_once 'Cache/Redis/Exception.php';

/**
 *
 * cache system using redis
 *
 * @category  Cache/Redis
 * @package   Cache_Redis
 * @author    xHARUKAx <https://github.com/xHARUKAx>
 * @since     Class avaiable since Release 0.0.1
 */
class Cache_Redis
{
  /** @var boolean debug mode */
  private $DEBUG     = false;

  /** @var int cache life time */
  private $_lifeTime = 3600;
  /** @var string ini file path */
  private $_iniFile  = '';
  /** @var array connection informations to master */
  private $_master   = false;
  /** @var array connection informations to slave */
  private $_slave    = false;
  /** @var boolean auto serialize */
  private $_automaticSerialization = true;

  /** @var string default host name */
  private $defaultHost   = 'localhost';
  /** @var int default port number */
  private $defaultPortNo = 6379;

  /** @var object redis instance to master */
  private $masterRedis = false;
  /** @var object redis instance to slave */
  private $slaveRedis  = false;
  /** @var string cache id used last */
  private $cacheId     = '';

  /**
   * constructor
   *
   * @since  0.0.1
   * @author xHARUKAx
   * @param array associative array to set options.
   */
  function __construct($options=array(null))
  {
    // check if phpredis is installed
    if (!class_exists('Redis')) {
      throw new Cache_Redis_Exception(
        get_class($this) . ' requires owlient-phpredis.'
      );
    }

    // check and set options
    if (is_array($options)) {
      foreach ($options as $k => $v) {
        $this->setOption($k, $v);
      }
    }

    // check redis connection informations
    if ($this->_master === false) {
      /** @var string ini file --  */
      $iniFile = '@DATA-DIR@' . '/etc/'
            . strtolower(get_class($this)) . '.ini';
      // check if ini file name is assigned
      if (isset($options['iniFile'])) {
        $iniFile = $options['iniFile'];
      }

      if (file_exists($iniFile)) {
        $config  = parse_ini_file($iniFile, true);
        if (is_array($config)) {
          foreach ($config as $k => $v) {
            $this->setOption($k, $v);
          }
        }
      }
    }

    // check slave connection informations.
    // if not set yet, copy master connection informations.
    $slaveIsMaster = false;
    if ($this->_slave === false) {
      $this->_slave  = $this->_master;
      $slaveIsMaster = true;
    }

    // make slave connection to read.
    // if neccessary, we connect to master later.
    try {
      $this->slaveRedis = $this->open($this->_slave);
      if ($slaveIsMaster === true) {
        $this->masterRedis =& $this->slaveRedis;
      }
    } catch (Exception $e) {
      ;
    }
  }

  /**
   * Test if a cache is available and return it.
   *
   * @since  0.0.1
   * @author xHARUKAx
   * @param  string cache id
   * @param  string name of the cache group
   * @return mixed data of the cache (or false if not available)
   */
  public function get($id, $group='default')
  {
    /** @var mixed data of the cache */
    $data = false;

    if ($this->slaveRedis instanceof Redis) {
      /** @var string cache id */
      $cacheId = $this->makeId($id, $group);
      /** @var mixed data of the cache */
      $data    = ($this->slaveRedis->exists($cacheId) === true)
        ? $this->slaveRedis->get($cacheId) : null;

      if ($this->_automaticSerialization === true) {
        $data = unserialize($data);
      }
      $this->cacheId = $id;
    }

    return $data;
  }

  /**
   * save Save some data in a cache
   *
   * @since  0.0.1
   * @author xHARUKAx
   * @param  mixed  data to put in a cache
   * @param  string cache id
   * @param  string name of the cache group
   * @return boolean true if no problem
   */
  public function save($data, $id=null, $group='default')
  {
    /** @var boolean function result */
    $ret = false;

    // if not connected, connect now.
    if (!($this->masterRedis instanceof Redis)) {
      $this->masterRedis = $this->open($this->_master);
    }

    if ($this->masterRedis instanceof Redis) {
      if (is_null($id) === true) {
        $id = $this->cacheId;
      }
      /** @var string cache id */
      $cacheId = $this->makeId($id, $group);

      if ($this->_automaticSerialization === true) {
        $data = serialize($data);
      }
      $ret = $this->masterRedis->setex(
        $cacheId, $this->_lifeTime, $data
      );
    }

    return $ret;
  }

  /**
   * remove a cache
   *
   * @since  0.0.1
   * @author xHARUKAx
   * @param  string cache id
   * @param  string name of the cache group
   */
  public function remove($id, $group='default')
  {
    // if not connected, connect now.
    if (!($this->masterRedis instanceof Redis)) {
      $this->masterRedis = $this->open($this->_master);
    }

    if ($this->masterRedis instanceof Redis) {
      /** @var string cache id */
      $cacheId = $this->makeId($id, $group);
      /** @var int number of records deleted */
      $num     = $this->masterRedis->delete($cacheId);
    }

    return $num;
  }

  /**
   * clear the specified cache
   *
   * @since  0.0.1
   * @author xHARUKAx
   * @param  string name of the cache group
   * @param  string flush cache mode
   */
  public function clean($group=false, $mode='')
  {
    // if not connected, connect now.
    if (!($this->masterRedis instanceof Redis)) {
      $this->masterRedis = $this->open($this->_master);
    }

    if ($this->masterRedis instanceof Redis) {
      /** @var string cache group prefix */
      $groupPrefix = ($group === false)
        ? '' : $this->makeGroup($group);

      $keys = $this->masterRedis->keys($groupPrefix . '*');
      if (is_array($keys)) {
        foreach ($keys as $k) {
          $this->masterRedis->delete($k);
        }
      }
    }
  }

  /**
   * test if a cache is available
   *
   * @since  0.0.1
   * @author xHARUKAx
   * @param  string cache id
   * @param  string cache group
   * @return boolean true if available (or false if not available)
   */
  public function exists($id, $group='default')
  {
    /** @var boolean available or not */
    $ret = false;

    if ($this->slaveRedis instanceof Redis) {
      /** @var string cache id */
      $cacheId = $this->makeId($id, $group);

      $ret = $this->slaveRedis->exists($cacheId);
    }

    return $ret;
  }

  /**
   * set to debug mode
   *
   * @since  0.0.1
   * @author xHARUKAx
   */
  public function setToDebug()
  {
    $this->DEBUG = true;
  }

  /**
   * set a new life time
   *
   * @since  0.0.1
   * @author xHARUKAx
   * @param  int new life time (in seconds)
   */
  public function setLifeTime($newLifeTime)
  {
    $this->setOption('lifeTime', (int)$newLifeTime);
  }

  /**
   * extend the life of a valid cache
   *
   * @since  0.0.1
   * @author xHARUKAx
   */
  public function extendLife()
  {
  }

  /**
   * make cache id
   *
   * @since  0.0.1
   * @author xHARUKAx
   * @param  string seed string
   * @param  string cache group
   * @return string cache id
   */
  private function makeId($seed, $group='default')
  {
    /** @var string key prefix */
    $prefix  = get_class($this);
    /** @var string cache id */
    $keyName = $prefix . '_' . sha1($seed);
    /** @var string key name */
    $cacheId = $this->makeGroup($group) . $keyName;

    return $cacheId;
  }

  /**
   * make cache group name
   *
   * @since  0.0.1
   * @author xHARUKAx
   * @param  string cache group
   * @return string cache id exclude id
   */
  private function makeGroup($group='default')
  {
    /** @var string key prefix */
    $prefix  = get_class($this);
    /** @var string key name */
    $cacheId = (!empty($group))
      ? ($prefix . '_' . $group . '/')
      : ($prefix . '_');

    return $cacheId;
  }

  /**
   * connect to redis
   *
   * @since  0.0.1
   * @author xHARUKAx
   * @param  array connection informations
   * @return object redis instance. false if error
   */
  private function open($connInfo)
  {
    /** @var object redis instance */
    $redis = new Redis();

    if ($redis instanceof Redis) {
      /** @var string host name */
      $hostName = (isset($connInfo['host']))
        ? $connInfo['host'] : $this->defaultHost;
      /** @var int port */
      $portNo   = (isset($connInfo['port']))
        ? (int)$connInfo['port'] : 0;
      /** @var string password */
      $password = (isset($connInfo['password']))
        ? $connInfo['password'] : '';
      if ($portNo < 1 || $portNo > 65535) {
        $portNo = $this->defaultPortNo;
      }
      // connect to redis
      if (method_exists($redis, 'pconnect')) {
        $ret = $redis->pconnect($hostName, $portNo);
      } else {
        $ret = $redis->connect($hostName, $portNo);
      }
      // if not empty password, authorize now.
      if ($ret === true && !empty($password)) {
        $ret = $redis->auth($password);
      }

      // if anything wrong, make result false.
      if ($ret !== true) {
        $redis = false;
      }
    } else {
      $redis = false;
    }

    return $redis;
  }

  /**
   * set options if property exists
   *
   * @since  0.0.1
   * @author xHARUKAx
   * @param  string property name
   * @param  mixed  property value
   */
  private function setOption($prop, $value)
  {
    $propName = '_' . $prop;
    if (property_exists($this, $propName)) {
      $this->$propName = $value;
    }
  }
}

?>
