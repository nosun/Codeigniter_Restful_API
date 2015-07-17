<?php defined('BASEPATH') OR exit('No direct script access allowed');


class Auth {

    private $_redis_pre = '';
    private $_auth_pass = array();
    private $_ci;

    public function __construct(){
        $this->_ci        = get_instance();
        $this->_redis_pre = $this->_ci->config->item('auth_pre');
        $this->_auth_pass = $this->_ci->config->item('auth_pass');
        $this->_ci->load->model('redis_model');
    }

    /**
     * Auth override check
     * 用户是否有权限访问
     * being called.
     *
     * @access protected
     * @return bool
     */

    public function check() {
        $method = $_SERVER['REQUEST_METHOD'];
        $arr = $this->_ci->uri->segment('3');
        if(in_array($arr.'_'.$method,$this->_auth_pass)){
            return TRUE;
        }
        $token = $this->_ci->input->request_headers()['Token'];
        if( isset($token)){
            $token_value = $this->redis_model->getToken($this->_redis_pre.$token);
            if($token_value){
                return true;
            }
        }
        return false;

    }
}