<?php defined('BASEPATH') OR exit('No direct script access allowed');

class WhiteList implements CheckInterFace {

    private $_ci;
    private $_ipList;
    private $_error = TRUE;
    private $_client_ip = '';

    public function __construct(){
        $this->_ci        = &get_instance();
        $this->_ipList    = $this->_ci->config->item('rest_ip_whitelist');
        $this->_client_ip = $this->_ci->input->ip_address();
    }

    public function doCheck() {

        $ipList = explode(',', $this->_ipList);

        array_push($ipList, '127.0.0.1', '0.0.0.0');

        foreach ($ipList AS &$ip)
        {
            $ip = trim($ip);
        }

        if (in_array($this->_client_ip, $ipList) === TRUE)
        {
            return FALSE; // 在白名单，显示FALSE，然后调getError，给返回 TRUE。
        }

        return TRUE; // 不在白名单，进入下一个循环！
    }

    public function setError($error){
        $this->_error = $error;
    }

    public function getError(){
        return $this->_error;
    }

}