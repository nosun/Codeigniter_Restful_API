<?php defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH.'/libraries/REST_Controller.php';

class WxApi extends REST_Controller
{

	function __construct()
    {
        parent::__construct();
    }

    //检查设备是否存在，true 表示存在
    function device_get()
    {
        $type    = $this->uri->segment('3');
        $value   = $this->uri->segment('4');

        if(empty($type) || empty($value)){
            $this->response(array('message'=>400),200);
        }

        if(!in_array($type,array('mac','sn','id','link'))){
            $this->response(array('message'=>400),200);
        }

        $type = 'device_'.$type;
        $condition=array( $type => $value);

        $this->load->model('device_model');
        $this->load->model('service_model');

        $result=$this->device_model->getDevice($condition);
        if($result) {
            $resultp=$this->device_model->getAppId($result['product_id']);
            $resulta = $this->device_model->getApp($resultp['0']['app_id']);
            $result['product_name'] = $resultp['0']['pid'];
            $result['app_name'] = $resulta['0']['app_name'];
            if($result['device_lock'] == 0){
                $this->response(array('result'=>$result,'message' => 401), 200);
            }
            $this->response(array('result'=>$result,'message' => 200), 200);
        }else{
            $this->response(array('message' => 404), 200);
        }
    }

    //修改设备
    function device_post()
    {
        $device_sn = $this->uri->segment('3');

        if (empty($device_sn)){
            $this->response(array('message'=>400), 200);
        }

        $condition = array('device_sn'=>$device_sn);
        $device    = array();

        $post =$this->post();
        if(isset($post['device_name']))   $device['device_name']  = $this->post('device_name');
        if(isset($post['device_state']))  $device['device_state']  = $this->post('device_state');
        if(isset($post['device_sn']))     $device['device_sn']    = $this->post('device_sn');
        if(isset($post['device_lock']))  $device['device_lock']  = $this->post('device_lock');
                                          $device['update_time']   = time();
        $this->load->model('device_model');

        $result=$this->device_model->updateDevice($device,$condition);

        if($result){
            $device = $this->device_model->getDevice($condition);
            $device_id = $device['device_id'];
            $this->response(array('result'=>$device_id,'message' => 200), 200);
        }else{
            $this->response(array('message' => 500), 200);
        }
    }

    public function cmd_post(){
        $openid   = $this->post('open_id');
        $device_id = $this->post('device_id');
        $cmd = $this->post('commandv');

        if(empty($openid) or empty($device_id) or empty($cmd)){
            $this->response(array('message'=>400), 200);
        }

        $this->load->model('device_model');
        $this->load->model('user_model');
        $check = $this->user_model->getWxuser($openid,$device_id);

        if(!empty($check)){
            $result = $this->device_model->pushMsgToDevice($device_id,$cmd);
            $this->response(array('message' => $result), 200);
        }else{
            $this->response(array('message'=>404), 200);
        }
    }

    public function bind_post(){
        $sn         = $this->post('sn');
        $openid     = $this->post('open_id');
        if(!isset($sn) || $sn == '' ){
            $this->response(array('message'=>400),200);
        }
        $this->load->model('user_model');
        $this->load->model('device_model');
        $device = $this->device_model->getIdBySn($sn);
        if(!isset($device->device_id)){
            $this->response(array('message'=>401),200);
        }
        $device_id = $device->device_id;
        $array=array(
            'open_id'=>$openid,
            'device_id'=>$device_id,
            'add_time'=>time()
        );

        $wxuser = $this->user_model->getWxuser($openid,$device_id);
        if(isset($wxuser->id)){
            $this->response(array('message'=>402),200);
        }
        $this->user_model->addBangding($array);
        $this->response(array('message'=>200),200);
    }

    public function unbind_post(){
        $sn         = $this->post('sn');
        $openid     = $this->post('open_id');
        $this->load->model('user_model');
        $this->load->model('device_model');
        $device = $this->device_model->getIdBySn($sn);
        if(!isset($device->device_id)){
            $this->response(array('message'=>401),200);
        }
        $device_id = $device->device_id;
        $wxuser = $this->user_model->getWxuser($openid,$device_id);

        if(!isset($wxuser->id)){
            $this->response(array('message'=>402),200);
        }

        $this->user_model->delRelieve($wxuser->id);
        $this->response(array('message'=>200),200);
    }

}
