<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Auth implements CheckInterFace {

    private $_redis_pre = '';
    private $_auth_pass = array();
    private $_ci;
    private $_error = 401;
    private $_header = '';

    public function __construct(){
        $this->_ci        = get_instance();
        $this->_redis_pre = $this->_ci->config->item('auth_pre');
        $this->_auth_pass = $this->_ci->config->item('auth_pass');
        $this->_header     = $this->_ci->input->request_headers();
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

    public function doCheck() {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $arr = $this->_ci->uri->segment('3');
        if(in_array($arr.'_'.$method,$this->_auth_pass)){
            return TRUE; //无需验证，进入下一个check
        }

        if(isset($this->_header['Token'])){
            $token_value = $this->_ci->redis_model->getToken($this->_redis_pre.$this->_header['Token']);
            if($token_value){
                return true; // 验证成功，进入下一个check
            }
        }
        return false;// 验证失败，报错
    }

    public function setError($error){
        $this->_error = $error;
    }

    public function getError(){
        return $this->_error;
    }

}