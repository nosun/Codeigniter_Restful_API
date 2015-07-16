<?php

Class Api_model extends CI_Model{

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->tb_weather='weather';
        $this->tb_region='region';
        $this->tb_pm='pm';
    }

    public function getRegionId($condition){
        $this->db->select('region_id');
        $query=$this->db->get_where($this->tb_region,$condition);
        $result=$query->result();
        return $result[0]->region_id;
    }
}
