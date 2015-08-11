<?php defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH.'/libraries/REST_Controller.php';

class Api_v1 extends REST_Controller
{
    private $user_id = null;
    function __construct()
    {
        parent::__construct('rest');
        $header = $this->input->request_headers();
        $this->load->model('redis_model');
        if(isset($header['Token'])) {
            $this->user_id = $this->redis_model->getToken($this->config->item('auth_pre').$header['Token']);
        }
    }

    //检查用户是否存在，true 表示存在
    function login_id_get()
    {
        $this->load->model('user_model');
        $app_id=$this->uri->segment('3');
        $login_id=$this->uri->segment('4');
        if(empty($login_id) || empty($app_id)){
            $this->response(array('message'=>400),200);
        }
        $user=array(
            'login_id'=>$login_id,
            'app_id'=>$app_id
        );
        $result=$this->user_model->getUser($user);
        if($result) {
            $this->response(array('message' => 200), 200);
        }else{
            $this->response(array('message' => 404), 200);
        }
    }

    //用户注册
    function user_post()
    {
        $this->load->model('user_model');
        $user['login_id'] = $this->post('login_id');
        $user['login_pwd'] = $this->post('login_pwd');
        $user['app_id'] = $this->post('app_id');
        $user['reg_time'] = time();
        $user['user_type'] = 1;
        $user['user_phone'] = $user['login_id'];

        if(empty($user['app_id']) || empty($user['login_id']) || empty($user['login_pwd'])){
            $this->response(array('message'=>400),200);
        }

        $check = $this->user_model->getUser(array('login_id'=>$user['login_id'],'app_id'=>$user['app_id']));

        if($check){
            $this->response(array('message'=>404),200);
        }else{
            $result=$this->user_model->addUser($user);

            if($result) {
                $message = array('login_id' => $this->post('login_id'), 'message' => 200);
                $this->response($message, 200);
            }else{
                $message = array('login_id' => $this->post('login_id'), 'message' => 500);
                $this->response($message, 200);
            }
        }

    }

    //获取用户信息 需要登录才能获取用户信息，否则返回403
    function user_get()
    {
        $user_id =$this->user_id;
        $this->load->model('user_model');
        $user=array(
            'user_id'=>$user_id,
        );
        $result=$this->user_model->getUser($user);
        if($result) {
            $this->response(array('result' => $result, 'message' => 200), 200);
        }else{
            $this->response(array('message' => 404), 200);
        }
    }

    //修改用户信息
    function user_put()
    {
        $user_id = $this->user_id;
        $user=array();
        if ($this->put('user_name')) $user['user_name']=$this->put('user_name');
        if ($this->put('user_phone')) $user['user_phone']=$this->put('user_phone');
        if ($this->put('user_email')) $user['user_email']=$this->put('user_email');
        if ($this->put('user_img')) $user['user_img']=$this->put('user_img');
        if ($this->put('user_prefer')) $user['user_prefer']=$this->put('user_prefer');

        if (empty($user_id) or empty($user)){
            $this->response(array('message'=>400),200);
        }

        $this->load->model('user_model');
        $result=$this->user_model->updateUser($user,$user_id);

        if($result) {
            $this->response(array('message' => 200), 200);
        }else{
            $this->response(array('message' => 200), 200);
        }
    }

    //修改密码
    function  passwd_put(){
        $user       = $this->getUserById($this->user_id);
        $passwd     = $this->put('login_pwd');
        $passwd_old = $this->put('login_pwd_old');
        $user_id   = $user['user_id'];

        if(empty($user_id) or empty($passwd) or empty($passwd_old)){
            $this->response(array('message'=>400),200);
        }

        $user=array(
            'user_id' => $user_id
        );

        $this->load->model('user_model');

        $res=$this->user_model->getUser($user);
        if($res){
            $result=$this->user_model->updatePasswd($user,$passwd);
            if($result){
                $this->response(array('message' => 200), 200);
            }else{
                $this->response(array('message' => 500), 200);
            }
        }else{
            $this->response(array('message' => 404), 200);
        }
    }

    //找回密码
    function  passwd_post(){

        $passwd   = $this->post('login_pwd');
        $login_id = $this->post('login_id');
        $app_id	  = $this->post('app_id');

        if(empty($login_id) || empty($passwd) || empty($app_id)){
            $this->response(array('message'=>400), 200);
        }

        $user=array(
            'login_id'  => $login_id,
            'app_id'	=> $app_id
        );

        $this->load->model('user_model');
        $result=$this->user_model->updatePasswd($user,$passwd);

        if($result){
            $this->response(array('message' => 200), 200);
        }else{
            $this->response(array('message' => 500), 200);
        }
    }

    //登录操作,返回user_id
    function token_post(){
        $this->load->model('user_model');
        $this->load->model('redis_model');
        $login_id   = $this->post('login_id');
        $login_pwd  = $this->post('login_pwd');
        $app_id	    = $this->post('app_id');
        //$login_type = $this->post('login_type');

        if(empty($login_id) or empty($login_pwd) or empty($app_id)){
            $this->response(array('message'=>400),200);
        }

        $user=array(
            'login_id'=>$login_id,
            'app_id'=>$app_id
        );

        $res=$this->user_model->getUser($user);
        if($res){
            $key = md5($res[0]['user_id'].time());
            $this->redis_model->setToken($this->config->item('auth_pre').$key,$res[0]['user_id']);
//            $this->redis_model->setToken($res[0]['user_id'],$key);
            if($key) {
                $this->response(array('token'=>$key,'message' => 200), 200);
            }else{
                $this->response(array('message' => 500), 200);
            }
        }else{
            $this->response(array('message' => 404), 200);
        }
    }

    //用户退出，删除token
    function token_delete(){
        $this->load->model('redis_model');
        $header = $this->input->request_headers();
        $res = $this->redis_model->delToken($this->config->item('auth_pre').$header['Token']);
        if($res){
            $this->response(array('message' => 200), 200);
        }else{
            $this->response(array('message' => 500), 200);
        }
    }

    //上传用户头像
    function file_post(){
        $user = $this->getUserById($this->user_id);
        if(empty($user)){
            $this->response(array('message'=>400),200);
        }
        $config['allowed_types'] = 'gif|jpg|jpeg|png|jpe';
        $config['max_size'] = '512';
        $config['max_width'] = '2048';
        $config['max_height'] = '1500';
        $config['encrypt_name'] = TRUE;
        $config['remove_spaces'] = TRUE;

        $dir = 'uploads/'.date('Ym',time());
        $path = $_SERVER['DOCUMENT_ROOT'].'/'.$dir;
        if ( !is_dir($path)) //if the path not exist,create it.
        {
            if (!mkdir($path,0777,true)) {
                $this->response(array('message' => 501), 200);
            }
        }

        $config['upload_path'] = $path;
        $this->load->library('upload',$config);
        if ( ! $this->upload->do_upload('file'))
        {
            $error = array('result' => $this->upload->display_errors());
            $this->response($error, 200);
        }else{
            $data = $this->upload->data();
            $file =array(
                "file_name"=> $data['file_name'],
                "file_path"=> $dir,
                "file_class"=> 1,
                "file_size"=> $data['file_size'],
                "file_time"=> time(),
                "is_image"=> $data['is_image'],
                "image_width"=> $data['image_width'],
                "image_height"=> $data['image_height'],
                "orig_name"=> $data['orig_name']
            );
            $this->load->model('file_model');
            $result=$this->file_model->addFile($file);
            if($result) {
                $url= base_url().$dir.'/'.$data['file_name'];
                $this->response(array('result'=>$url,'message' => 200), 200);
            }else{
                $this->response(array('message' => 500), 200);
            }
        }
    }

    //上传日志
    function log_post(){
        $user = $this->getUserByToken($this->user_id);
        $path  = $this->post('path');
        if(empty($user)){
            $this->response(array('message'=>400),200);
        }
        $config['allowed_types'] = 'log';
        $config['max_size'] = '1024';

        $dir = 'uploads/log/'.$path;
        $path = $_SERVER['DOCUMENT_ROOT'].'/'.$dir;
        if ( !is_dir($path)) //if the path not exist,create it.
        {
            if (!mkdir($path,0777,true)) {
                $this->response(array('message' => 501), 200);
            }
        }

        $config['upload_path'] = $path;
        $this->load->library('upload',$config);
        if ( ! $this->upload->do_upload('file'))
        {
            $error = array('result' => $this->upload->display_errors());
            $this->response($error, 200);
        }else{
            $data = $this->upload->data();
            if($data) {
                $url= base_url().$dir.'/'.$data['file_name'];
                $this->response(array('result'=>$url,'message' => 200), 200);
            }else{
                $this->response(array('message' => 500), 200);
            }
        }
    }

    //检查设备是否存在，true 表示存在，可以注册
    function device_get()
    {
        $user_id   = $this->user_id;
        $type    = $this->uri->segment('3');
        $value   = $this->uri->segment('4');

        if(empty($user_id)|| empty($type) || empty($value)){
            $this->response(array('message'=>400),200);
        }

        if(!in_array($type,array('mac','sn','id','link'))){
            $this->response(array('message'=>400),200);
        }
        $type = 'device_'.$type;

//        if($type == "link"){
//            $value = urlencode($value);
//        }

        $condition=array( $type => $value );

        $this->load->model('device_model');
        $this->load->model('service_model');

        $result=$this->device_model->getDevice($condition);
        if($result) {
            $resultp=$this->device_model->getAppId($result['product_id']);
            $resulta = $this->device_model->getApp($resultp['0']['app_id']);
            $result['product_name'] = $resultp['0']['pid'];
            $result['app_name'] = $resulta['0']['app_name'];
            $this->response(array('result'=>$result,'message' => 200,'time'=>time()), 200);
        }else{
            $this->response(array('message' => 404), 200);
        }
    }

    //检查sn是否存在
    public function deviceSn_get(){
        $this->load->model('device_model');
        $app_id = $this->uri->segment('3');
        $sn=$this->uri->segment('4');

        if(empty($sn) || empty($app_id)){
            $this->response(array('message'=>400), 200);
        }

        $pid = $this->device_model->getPid($app_id);

        if(empty($pid)){
            $this->response(array('message' => 404), 200);
        }

        $result=$this->device_model->getDeviceSn($sn, $pid);
        if($result){
            $this->response(array('message' => 200), 200);
        }else{
            $this->response(array('message' => 404), 200);
        }
    }

    public function deviceMac_Post(){
        $mac  = $this->post('mac');
        $pass = $this->post('pass');
        $mid  = $this->post('pid');

        if(empty($mid) || $pass != 'sjwMac2015' || empty($mac)){
            $this->response(array('message'=>400), 200);
        }

        $mac_data = array(
            'module_id'  => $mid,
            'mac'        => $mac,
            'addtime'    => time()
        );

        $this->load->model('device_model');
        $check = $this->device_model->getMac(array('mac'=>$mac));

        if(!empty($check)){
            $this->response(array('message'=>501), 200);
        }

        $res = $this->device_model->addMac($mac_data);
        if($res){
            $this->response(array('message' => 200), 200);
        }else{
            $this->response(array('message'=>500), 200);
        }
    }

    //获取设备列表
    function devices_get(){
        $user_id = $this->user_id;
        if(empty($user_id)){
            $this->response(array('message'=>400),200);
        }
        $this->load->model('device_model');
        $result=$this->device_model->getDeviceByUser($user_id);

        if($result){
            $this->response(array('result' => $result,'message' => 200,'time'=>time()), 200);
        }else{
            $this->response(array('message' => 404), 200);
        }
    }


    //修改设备
    function device_put()
    {
        $user_id    = $this->user_id;
        $device_mac = $this->uri->segment('3');
        if (empty($user_id) || empty($device_mac)){
            $this->response(array('message'=>400), 200);
        }

        $condition = array('device_mac'=>$device_mac);
        $device    = array();

        $device_lock = $this->put('device_lock');
        $longitude = $this->put('longitude');
        $latitude = $this->put('latitude');

        if($this->put('province'))      $device['province']     = $this->put('province');
        if($this->put('city'))          $device['city']         = $this->put('city');
        if($this->put('district'))      $device['district']     = $this->put('district');
        if($this->put('device_name'))   $device['device_name']  = $this->put('device_name');
        if($this->put('device_sn'))     $device['device_sn']    = $this->put('device_sn');
        if(isset($device_lock)) $device['device_lock']  = $this->put('device_lock');
        if(isset($longitude)) $device['longitude']    = $this->put('longitude');
        if(isset($latitude)) $device['latitude']      = $this->put('latitude');
        if($this->put('radius'))        $device['radius']       = $this->put('radius');
        if($this->put('area_id'))       $device['area_id']      = $this->put('area_id');
        if($this->put('device_address'))$device['device_address'] = $this->put('device_address');
        if($this->put('pm_id'))         $device['pm_id']        = $this->put('pm_id');

        $device['update_time']   = time();

        if(empty($device['district'])){
            $device['district'] = null;
        }

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


//    public function devices_put(){
//        $user_id    = $this->getUserByToken($this->token)['user_id'];
//        $device_id  = $this->put('device_id');// may more than one devices
//
//        if (empty($user_id) || empty($device_id)){
//            $this->response(array('message'=>400), 200);
//        }
//
//        $device    = array();
//
//        if($this->put('pmv')) $device['pmv']  = $this->put('pmv');
//        $device['update_time']   = time();
//
//        $this->load->model('device_model');
//        $result=$this->device_model->updateDevices($device,$device_id);
//
//        if($result){
//            $this->response(array('result'=>$result,'message' => 200), 200);
//        }else{
//            $this->response(array('message' => 500), 200);
//        }
//    }

    function bind_post(){
        $user_id    = $this->user_id;
        $device_id  = $this->post('device_id');
        $device_mac = $this->post('device_mac');

        if(empty($user_id) or (empty($device_id) and empty($device_mac))){
            $this->response(array('message'=>400), 200);
        }

        if($device_mac){
            $condition = array(
                'device_mac'=>$device_mac
            );
        }else{
            $condition = array(
                'device_id'=>$device_id
            );
        }

        $this->load->model('device_model');
        $device = $this->device_model->getDevice($condition);
        if($device){
            $bind= array(
                'device_id'=>$device['device_id'],
                'user_id'  =>$user_id,
                'bind_time'=>time()
            );
            $result=$this->device_model->addBind($bind);
            if($result) {
                $res = $this->device_model->getBind(array('user_id'=>$user_id));
                $num = count($res);
                $this->response(array('result'=>$num,'message' => 200), 200);
            }else{
                $this->response(array('message' => 500), 200);
            }
        }else{
            $this->response(array('message' => 404), 200);
        }
    }

    function bind_delete(){
        $user_id   = $this->user_id;
        $device_id = $this->uri->segment('3');

        if(empty($user_id) or empty($device_id)){
            $this->response(array('message'=>400), 200);
        }

        $this->load->model('device_model');
        //增加判断，设备的主人数量
        $binds = $this->device_model->getBind(array('device_id'=>$device_id));

        if(count($binds) == 1){
            $this->device_model->updateDevice(array('device_lock' => 1),array('device_id'=>$device_id));
        }
        $result=$this->device_model->delBind($user_id,$device_id);
        if($result) {
            $this->response(array('result'=>$result,'message' => 200), 200);
        }else{
            $this->response(array('message' => 500), 200);
        }
    }

    public function cmd_post(){
        $user_id   = $this->user_id;
        $device_id = $this->post('device_id');
        $cmd = $this->post('commandv');

        if(empty($user_id) or empty($device_id) or empty($cmd)){
            $this->response(array('message'=>400), 200);
        }
        $this->load->model('device_model');
        $this->load->model('redis_model');
        $check = $this->device_model->getBind(array('user_id'=>$user_id,'device_id'=>$device_id));

        if(!empty($check)){
            $result = $this->device_model->pushMsgToDevice($device_id,$cmd);
            $this->response(array('message' => $result), 200);
        }else{
            $this->response(array('message'=>404), 200);
        }
    }

    public function app_get(){
        $app_id = $this->uri->segment('3');
        if(empty($app_id)){
            $this->response(array('message'=>400), 200);
        }

        $this->load->model('service_model');
        $result=$this->service_model->getLatestApp(array('app_id'=>$app_id));

        if($result) {
            $this->response(array('result'=>$result[0],'message' => 200), 200);
        }else{
            $this->response(array('message' => 404), 200);
        }
    }

    public function appHost_get(){
        $app_id = $this->uri->segment('3');
        if(empty($app_id)){
            $this->response(array('message'=>400), 200);
        }

        $this->load->model('service_model');
        $result = $this->service_model->getHost($app_id);

        if(!empty($result)) {
            $this->response(array('result'=>$result[0],'message' => 200), 200);
        }else{
            $this->response(array('message' => 404), 200);
        }
    }

    public function company_get(){
        $company_id = $this->uri->segment('3');

        if(empty($company_id)){
            $this->response(array('message'=>400), 200);
        }

        $this->load->model('service_model');
        $result=$this->service_model->getCompany(array('id'=>$company_id));

        if($result) {
            $this->response(array('result'=>$result[0],'message' => 200), 200);
        }else{
            $this->response(array('message' => 404), 200);
        }
    }

    //just a redis test
    public function testSpeed_get(){
        $num = $this->uri->segment('3');
        $num = empty($num)?1:$num;
        $str =str_repeat(1,$num*1024);
        $this->response(array('result'=>$str,'message' => 200), 200);
    }

    //just a redis test
    public function testHttp_get(){
        //var_dump($this->input->get_request_header('test', TRUE));
        $code = $this->uri->segment('3');
        //返回值
        if($code){
            $this->response(array('result'=>'ok'), $code);
        }
    }

    //just a redis test
    public function testDelay_get(){
        $time = $this->uri->segment('3');
        sleep($time);
        $this->response(array('result'=>'ok'), 200);
    }

    private function getUserById($user_id){
        if($user_id){
            $this->load->model('user_model');
            $user = $this->user_model->getUser(array('user_id'=>$user_id));
            return $user[0];
        }else{
            return 0;
        }
    }

    public function wpm_post(){
        $province  = $this->post('province');
        $city      = $this->post('city');
        $district  = $this->post('district');
        $this->load->model('api_model');

        if(empty($province) || empty($city) ){
            $this->response(array('message'=>400),200);
        }
        $array = array('省','市','特别行政区','自治区','区','县',);
        //根据城市查询pm
        $area = $this->api_model->chaxun_air_log('round(avg(pm25)) as pm,round(avg(aqi)) as aqi',"area_name = '$city'");
        //上边没有查出来时，对城市名称处理后进行查询
        if(!empty($area)){
            $str=str_replace($array,'',$city);
            $area_del = $this->api_model->chaxun_air_log('round(avg(pm25)) as pm,round(avg(aqi)) as aqi',"area_name like '$str'");
        }

        //根据省市区查询area_id
        $strprovince=str_replace($array,'',$province);
        $strcity=str_replace($array,'',$city);
        $strdistrict=str_replace($array,'',$district);

        $area_v2 = $this->api_model->chaxun_area_v2('area_id,area_name,district_name,province_name',"area_name = '$strdistrict' and district_name = '$strcity' and province_name = '$strprovince'");

        if(empty($area_v2)){
            $area_v2 = $this->api_model->chaxun_area_v2('area_id,area_name,district_name,province_name',"area_name = '$strcity' and district_name = '$strcity' and province_name = '$strprovince'");
        }

        foreach($area_v2 as $v){
            $id = $v['area_id'];
            $area_id = $this->api_model->chaxun_log_sk('temperature,wind_direct,wind_power,humidity',"area_id = '$id'");
        }

        //将查出的结果放入数组中
        if(!empty($area_id)){
            $data = array(
                'temperature' =>$area_id[0]['temperature'],
                'humidity' => $area_id[0]['humidity']
            );
        }

        else{
            if(!empty($district)){
                $area_error = $this->api_model->chaxun_area_error("area_name = '$district'");
                if(empty($area_error)){
                    $this->api_model->charu(array('area_name'=>$district,'district_name'=>$city,'province_name'=>$province));
                }
            }
            //天气信息查询失败  统一返回查询失败
            $this->response(array('message' => 404), 200);
        }

        if(isset($area_del)){
            $data['pm'] = $area_del[0]['pm'];
            $data['aqi'] = $area_del[0]['aqi'];
        }else if(isset($area)){
            $data['pm'] = $area[0]['pm'];
            $data['aqi'] = $area[0]['aqi'];
        }else{
            //pm信息查询失败  统一返回查询失败
            $this->response(array('message' => 404), 200);
        }

        //返回值
        if($data){
            $this->response(array('result'=>$data,'message' => 200), 200);
        }else{
            $this->response(array('message' => 404), 200);
        }
    }

    //新增用户反馈
    public function feedback_post(){
        $user       = $this->getUserById($this->user_id);
        $title      = $this->post('title');
        $content    = $this->post('content');
        $product_id = $this->post('product_id');
        $category   = $this->post('category');

        if(empty($user) or empty($title) or empty($content) or empty($product_id) or empty($category)){
            $this->response(array('message'=>400), 200);
        }

        $this->load->model('user_model');

        $feed = array(
            'title'         => $title,
            'content'       => $content,
            'user_name'     => $user['user_name'],
            'category'      => $category,
            'product_id'    => $product_id,
            'status'        => '1'
        );
        if($this->user_model->getFeedback($feed)){
            $this->response(array('message'=>404), 200);
        }

        $feed['addtime'] = time();
        if($this->user_model->addFeedback($feed)){
            $this->response(array('message' => 200), 200);
        }else{
            $this->response(array('message' => 500), 200);
        }
    }

    //用户对反馈的回复
    public function feedback_reply_post(){
        $user       = $this->getUserById($this->user_id);
        $fid      = $this->post('fid');
        $content    = $this->post('content');

        if(empty($user) or empty($fid) or empty($content)){
            $this->response(array('message'=>400), 200);
        }
        $this->load->model('user_model');
        $feed = array(
            'user_name' =>$user['user_name'],
            'fid'       =>$fid,
            'content'   =>$content,
            'role'      =>'1'
        );

        if($this->user_model->getFeedbackReply($feed)){
            $this->response(array('message'=>404), 200);
        }
        $feed['addtime'] = time();
        $this->user_model->updateFeedback(array('status'=>'3'),array('id'=>$fid));
        if($this->user_model->addFeedbackReply($feed)){
            $this->response(array('message' => 200), 200);
        }else{
            $this->response(array('message' => 500), 200);
        }
    }

    //获取最新的固件地址
    public function newversion_get(){
        $this->load->model('device_model');
        $result=$this->device_model->getBind(array('user_id'=>$this->user_id));
        $arr = array();
        foreach($result as $v){
            $device = $this->device_model->getDevice(array('device_id'=>$v->device_id));
            $version = $device['device_wifi_firmware_version'];

            $newversionarr = $this->device_model->getNewVersion();
            $newversion = trim($newversionarr->firmware_version,"v");

            if($newversion > $version){
                $arr[]=array(
                    'id'=>$v->device_id,
                    'url'=>$newversionarr->firmware_w_url
                );
            }
        }

        if(!empty($arr)){
            $this->response(array('result'=>$arr,'message' => 200), 200);
        }else{
            $this->response(array('message' => 500), 200);
        }
    }

}
