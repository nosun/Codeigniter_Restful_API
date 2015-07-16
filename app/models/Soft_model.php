<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

Class Soft_model extends CI_Model{

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->tb_app='app';
        $this->tb_app_version='app_version';
    }

    public function getLatestApp($condition){
        $this->db->select('*');
        $query=$this->db->order_by('version_code','desc')->limit(1,0)->get_where($this->tb_app_version,$condition);
        $result=$query->result();
        return $result;
    }

}