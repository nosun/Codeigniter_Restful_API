<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

Class File_model extends CI_Model{

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->tb_file='file';
    }

    public function addFile($file){
        $this->db->insert($this->tb_file,$file);
        $result=$this->db->insert_id();
        return $result;
    }
}