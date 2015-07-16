<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

Class Api_model extends CI_Model{

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->tb_weather='weather';
        $this->tb_region='region';
        $this->tb_pm='pm';
        $this->db->set_dbprefix('api_');
        $this->tb_weather_log_sk=$this->db->dbprefix('weather_log');
        $this->tb_weather_area_v2=$this->db->dbprefix('weather_area_v2');
        $this->tb_air_log=$this->db->dbprefix('air_log');
        $this->tb_area_error=$this->db->dbprefix('area_error');
    }

    public function getRegionId($condition){
        $this->db->select('region_id');
        $query=$this->db->get_where($this->tb_region,$condition);
        $result=$query->result();
        return $result[0]->region_id;
    }

    public function getRegion($condition){
        if($condition['region_id']==0 or $condition == null){
            $condition['region_id']=1;
        }
        $this->db->select('region_name');
        $query=$this->db->get_where($this->tb_region,$condition);
        $result=$query->result();
        return $result[0]->region_name;
    }

    public function charu($array){
        $this->db->insert($this->tb_area_error,$array);
    }

    public function chaxun_area_error($area_name){
        $sql = 'select id from '.$this->tb_area_error.' where '.$area_name ;
        $result= $this->db->query($sql)->result_array();
        return $result;
    }

    public function chaxun_log_sk($data,$area_name){
        $sql = "select $data from $this->tb_weather_log_sk where $area_name order by settime desc";
        $result= $this->db->query($sql)->result_array();
        return $result;
    }

    public function chaxun_area_v2($data,$area_name){
        $sql = "select $data from $this->tb_weather_area_v2 where $area_name";
        $result= $this->db->query($sql)->result_array();
        return $result;
    }

    public function chaxun_air_log($data,$area_name){
        $sql = "select $data from $this->tb_air_log where $area_name";
        $result= $this->db->query($sql)->result_array();
        return $result;
    }
}
