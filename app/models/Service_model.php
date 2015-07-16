<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

Class Service_model extends CI_Model{

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->tb_order         = $this->db->dbprefix('order');
        $this->tb_app           = $this->db->dbprefix('app');
        $this->tb_app_version   = $this->db->dbprefix('app_version');
        $this->tb_company       = $this->db->dbprefix('company');
        $this->load->helper('check');

    }

    public function getLatestApp($condition){
        $this->db->select('*');
        $query=$this->db->order_by('version_code','desc')->limit(1,0)->get_where($this->tb_app_version,$condition);
        $result=resultFilter($query->result_array());
        return $result;
    }


    public function getHost($app_id){
        $this->db->select('server_login,server_api,server_mq');
        $query=$this->db->get_where($this->tb_app,array('app_id' => $app_id));
        $result=resultFilter($query->result_array());
        return $result;
    }

    public function getCompany($condition){
        $this->db->select('*');
        $query=$this->db->get_where($this->tb_company,$condition);
        $result=$query->result();
        return $result;
    }


}