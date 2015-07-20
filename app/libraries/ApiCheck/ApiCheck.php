<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once 'CheckInterFace.php';

class ApiCheck {

    private $_filter = array('BlackList');
    private $_field_name;
    private $_ci;
    private $_ex_error = 500;


    public function __construct(array $_filter){
        $this->_filter     = $_filter;
        $this->_ci         = &get_instance();
        $this->_field_name = $this->_ci->config->item('rest_message_field_name');
    }

    public function doCheckFlow(){
        foreach ($this->_filter as $class){
            $this->loadClass($class);
            $auth = new $class();
            try{
                if($auth->doCheck() === FALSE){
                    return $auth->getError();
                }
            }catch (Exception $ex)
            {
                return $this->_ex_error;
            }
        }
        return TRUE;
    }

    public function loadClass($class){
        require $class.'.php';
    }

}