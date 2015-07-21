<?php defined('BASEPATH') OR exit('No direct script access allowed');

class BlackList implements CheckInterFace {

    private $_ci;
    private $_blacklist;
    private $_error = 403;

    public function __construct(){
        $this->_ci        = &get_instance();
        $this->_blacklist = $this->_ci->config->item('rest_ip_blacklist');
    }

    public function doCheck() {

        $ipList = explode(',', $this->_blacklist);

        foreach ($ipList AS &$ip)
        {
            $ip = trim($ip);
        }

        if (in_array($this->_ci->input->ip_address(), $ipList) === TRUE)
        {
            return FALSE; // 在黑名单，报错！
        }

        return TRUE; // 不在黑名单，进入下一个check。
    }

    public function setError($error){
        $this->_error = $error;
    }

    public function getError(){
        return $this->_error;
    }

}