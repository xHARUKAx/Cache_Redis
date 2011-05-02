Cache_Redis
=============

Cache_Redis is a simple cache system for PHP which uses redis / owlient-phpredis.

This code is maintained by [xHARUKAx](https://github.com/xHARUKAx).
You can send comments, patches, questions here on github.

Installing/Configuring
----------------------

<pre>
pear install ./Cache_Redis-0.0.3.tgz
</pre>

Requirements
------------

* [Redis](http://redis.io/)
* [PhpRedis](https://github.com/nicolasff/phpredis "nicolasff / phpredis")

Methods
-------

### Cache_Redis::__construct
#### *Description*

Creates a Cache_Redis instance

#### *Parameters*

##### options

* lifeTime (integer / Default: 3600)

    Cache life time in seconds. Default is 3600 sec.

* automaticSerialization (boolean / Default: true)

    Auto serialize data before set and unserialize after get.
    This means you don't have to serialize/unserialize in the code.

* master (hash)

    Connection informations array to master redis server.
    Elements are the followings:
    * host
    * password
    * port

* slave (hash)

    Connection informations array to slave redis server.
    If you don't set, use master information to get data.
    Elements are the same as "master".

* iniFile (string / Default: pear_data_dir/etc/cache_redis.ini)

    .ini file to get configurations master/slave redis server.
    When you don't set master, try to read this option.

#### *Example*

<pre>
$cache = new Cache_Redis();
</pre>

### Cache_Redis::get
#### *Description*

Get data from slave redis server.
If slave isn't set, read from master.

### Cache_Redis::save
#### *Description*

Save data to master redis server.

Current Limitaions
------------------

* Cannot extend life time.
