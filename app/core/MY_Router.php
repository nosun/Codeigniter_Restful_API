<?php defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Router extends CI_Router{

    private $version;

    public function __construct(){
        if(isset($_SERVER['HTTP_APIVER'])){
            $this->version = $_SERVER['HTTP_APIVER'];
        }
        parent::__construct();
    }

    public function set_class($class)
    {
        $this->class = str_replace(array('/', '.'), '', $class);
        if($this->version){
            $this->class.= '_'.$this->version;
        }
    }
}