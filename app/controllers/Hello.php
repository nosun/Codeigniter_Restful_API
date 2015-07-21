<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Hello extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
    }

    function index(){
        echo "hello";
    }

}