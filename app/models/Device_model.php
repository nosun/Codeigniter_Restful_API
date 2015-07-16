<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

Class Device_model extends CI_Model{

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->tb_device =$this->db->dbprefix('device');
        $this->tb_device_class =$this->db->dbprefix('device_class');
        $this->tb_bind =$this->db->dbprefix('relation_user_device');
        $this->tb_mac =$this->db->dbprefix('product_mac');
        $this->tb_sn =$this->db->dbprefix('product_sn');
        $this->tb_product =$this->db->dbprefix('product');
        $this->tb_app =$this->db->dbprefix('app');
        $this->load->helper('check');
    }

    public function addDevice($device){
        $this->db->insert($this->tb_device,$device);
        $result=$this->db->insert_id();
        return $result;
    }

    public function getDevice($condition){
        $this->db->select('*');
        $time = time()-50;
        if(isset($condition['device_link'])){
            $query=$this->db->order_by('setlink_time', 'desc')->where('setlink_time >',$time)->get_where($this->tb_device,$condition,1);
        }else{
            $query=$this->db->get_where($this->tb_device,$condition);
        }

        $result=$query->result_array();
        if($result){
            $data = $result[0];
            $this->load->model('redis_model');
            $data['device_online'] = $this->redis_model->getDeviceAttr($data['device_mac'],'online');
            $device_data           = $this->redis_model->getDeviceData($data['device_mac']);
            if($device_data) $data['device_data'] = $device_data;
        }else{
            return 0;
        }
        //var_dump($data);die;
            return paraFilter($data);
    }

    // 设备是否在线，设备的当前状态，需要通过redis查询
    public function getDeviceByUser($user_id){
        $sql = 'select device_id from '.$this->tb_bind.' where user_id ='.$user_id;
        $result = $this->db->query($sql)->result_array();
        $array = array();
        if($result){
            foreach($result as $row){
                $array[] = $this->getDevice(array('device_id'=>$row['device_id']));
            }
        }
        return resultFilter($array);
    }

    public function updateDevice($device,$condition){
        $this->db->update($this->tb_device,$device,$condition);
        $result=$this->db->affected_rows();
        return $result;
    }

//    public function updateDevices($device,$device_id){
//        $this->db->where_in('device_id',$device_id)->update($this->tb_device,$device);
//        $result=$this->db->affected_rows();
//        return $result;
//    }

    public function addBind($bind){
        $condition = array_slice($bind,0,2);
        $res       = $this->getBind($condition);
        if($res){
            return $res;
        }else{
            $this->db->insert($this->tb_bind,$bind);
            $result=$this->db->insert_id();
            return $result;
        }
    }

    public function getBind($condition){
        $query  = $this->db->get_where($this->tb_bind,$condition);
        $result = $query->result();
        return $result;
    }

    public function delBind($user_id,$device_id){
        $this->db->where('user_id',$user_id)->where_in('device_id',$device_id)->delete($this->tb_bind);
        $result=$this->db->affected_rows();
        return $result;
    }

    public function pushMsgToDevice($device_id,$msg){
        $device =$this->db->get_where($this->tb_device,array('device_id'=>$device_id))->result();
        $mac = $device[0]->device_mac;
        $msg = trim($msg,"\n");
        $data = "{\"cmd\":\"command\",\"mac\":\"$mac\",\"data\":$msg}";
        $this->load->library('SClient');
        $client = new SClient();
        if(false == $client->connect()){
            $result = 500; //connect fail
        }else{
            if(true == $client->send($data)){
                $result = 200; //send ok
            }else{
                $result = 501; //send fail
            }
        }
        $client->close();
        return $result;
    }


    public function addMac($mac_data){
        $this->db->insert($this->tb_mac,$mac_data);
        $result=$this->db->insert_id();
        return $result;
    }

    public function getMac($condition){
        $query=$this->db->get_where($this->tb_mac,$condition);
        $result=$query->result_array();
        return $result;
    }

    public function getDeviceSn($sn,$pid){
        $query=$this->db->where('sn',$sn)->where_in('product_id',$pid)->get($this->tb_sn);
        $result=$query->result_array();
        return $result;
    }

    public function  getPid($app_id){
        $query = $this->db->select('product_id')->get_where($this->tb_product,array('app_id'=>$app_id));
        $result = $query->result_array();
        $product_id = array();
        foreach($result as $row){
            $product_id[] = $row['product_id'];
        }
        return $product_id;
    }

    public function getAppId($product_id){
        $query = $this->db->select('app_id,pid')->get_where($this->tb_product,array('product_id'=>$product_id));
        $result = $query->result_array();
        return $result;
    }

    public function getApp($app_id){
        $query = $this->db->select('app_name')->get_where($this->tb_app,array('app_id'=>$app_id));
        $result = $query->result_array();
        return $result;
    }

    public function getIdBySn($sn){
	    return $this->db->from($this->tb_device)->where('device_sn',$sn)->order_by('device_id','desc')->limit(1)->get()->row();
    }
}
