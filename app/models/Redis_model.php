<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Redis_Model  extends CI_Model {

    private $redis;
    static  $prefix = "yun_";
    static  $fd ='f_';
    static  $mac ='m_';
    static  $dv_attr ='a_';  //device attr
    static  $dv_data ='d_';  //device data

    public function __construct($host = '127.0.0.1', $port = 6379, $timeout = 0.0){
        $redis = new \redis;
        $redis->connect($host, $port, $timeout);
        $this->redis = $redis;
    }

    public function setDevice($key,array $data){
        $this->redis->hMset(self::$prefix.self::$dv_attr.$key,$data);
    }

    public function getDevice($key){
        return $this->redis->hGetAll(self::$prefix.self::$dv_attr.$key);
    }

    public function checkDevice($key){
        return $this->redis->exists(self::$prefix.self::$dv_attr.$key);
    }

    public function getDeviceAttr($key,$field){
        return $this->redis->hget(self::$prefix.self::$dv_attr.$key,$field);
    }

    public function getDeviceData($key){
        return $this->redis->hGetAll(self::$prefix.self::$dv_data.$key);
    }

    public function setDeviceData($key,array $value){
        $this->redis->hMset(self::$prefix.self::$dv_data.$key,$value);
    }

    public function setLimit($key,array $value){
        $this->redis->hMset($key,$value);
    }

    public function getLimit($key,$field){
        return $this->redis->hget($key,$field);
    }

    public function setToken($key,$value){
        $this->redis->set($key,$value);
    }

    public function getToken($key){
        $this->redis->get($key);
    }

}