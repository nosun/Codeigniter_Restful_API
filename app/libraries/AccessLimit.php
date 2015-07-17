<?php defined('BASEPATH') OR exit('No direct script access allowed');

class AccessLimit {

    private $_rate;
    private $_time;
    private $_redis_pre;
    private $_ci;

    public function __construct(){
        $this->_ci        = get_instance();
        $this->_rate      = $this->_ci->config->item('limits_rate');
        $this->_time      = $this->_ci->config->item('limits_time');
        $this->_redis_pre = $this->_ci->config->item('limits_pre');
        $this->_ci->load->model($this->_ci->config->item('limits_model'));
    }

    /**
     * Limit override check
     * 客户端访问频率限制
     * being called.
     *
     * @access protected
     * @return bool
     */

    public function check() {
        $ip         = $this->_ci->input->ip_address();
        $last_check = $this->_ci->redis_model->getlimit($this->_redis_pre.$ip,'check_time');
        $allowance  = $this->_ci->redis_model->getlimit($this->_redis_pre.$ip,'allow_times');

        if(!isset($allowance)){
            $allowance = $this->_rate;
        }

        if(!isset($last_check)){
            $last_check = time();
        }

        $current = time();
        $time_passed = $current - $last_check;
        $last_check = $current;
        $allowance += $time_passed * ($this->_rate / $this->_time);

        if ($allowance > $this->_rate){
            $allowance = $this->_rate;
        }

        if ($allowance < 1.0) {
            return FALSE;
        }

        $allowance -= 1.0;
        $this->_ci->redis_model->setlimit($ip,['check_time'=>$last_check,'allow_times'=>$allowance]);

        return TRUE;
    }





}