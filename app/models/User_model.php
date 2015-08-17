<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

Class User_model extends CI_Model{

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->tb_user = 'user';
        $this->tb_device_wxuser =$this->db->dbprefix('device_wxuser');
        $this->tb_feedback = $this->db->dbprefix('feedback');
        $this->tb_feedback_reply = $this->db->dbprefix('feedback_reply');
        $this->load->helper('check');
    }

    public function addUser($user) {
        $user['salt'] = substr(sha1(time()), -10);
        $user['login_pwd'] = sha1($user['login_pwd'] . $user['salt']);
        $result= $this->db->insert($this->tb_user, $user);
        return $result;
    }

    public function getUser($user) {
        $this->db->select('user_id,app_id,login_id,login_pwd,salt,user_name,user_img,user_email,user_phone,notice_pm,notice_pm_value,notice_filter,user_prefer');
        $query=$this->db->get_where($this->tb_user,$user);

        $result=resultFilter($query->result_array());
        return $result;
    }

    public function updatePasswd($user,$passwd){
        $this->db->select('salt');
        $query=$this->db->get_where($this->tb_user,$user);
        $result=$query->result();
        $passwd = sha1($passwd . $result[0]->salt);
        if(isset($user['user_id'])){
            $this->db->where('user_id', $user['user_id']);
        }else{
            $this->db->where('login_id', $user['login_id'])->where('app_id',$user['app_id']);
        }
        $result=$this->db->update($this->tb_user,array('login_pwd' => $passwd));
        return $result;
    }

    public function updateUser($user,$user_id){
        $this->db->update($this->tb_user,$user,array('user_id' =>$user_id));
        $result=$this->db->affected_rows();
        return $result;
    }

    public function getToken($user,$passwd){
        $this->load->helper('encrypt');
        $key=$this->config->item('aes_key');
        $query=$this->db->get_where($this->tb_user,$user);
        $result=$query->result();
        if ($result[0]->login_pwd == sha1($passwd . $result[0]->salt)){
            $token=encrypt($result[0]->user_id,$key);
            $data['token'] = $token;
            return $token;
        }else{
            return 0;
        }
    }

    public function getRegion($condition){
        $this->db->select('region_id,region_name');
        $query=$this->db->get_where($this->tb_region,$condition);
        $result=$query->result();
        return $result;
    }

    public function getSmsCode($phone,$tpl_id,$tpl_value){
        $this->load->helper('sharesdk');
        $api=$this->config->item('sms_api');
        $key=$this->config->item('sms_apikey');
        $response = postRequest( $api, array(
            'apikey' => $key,
            'mobile' => $phone,
            'tpl_id' => $tpl_id,
            'tpl_value' => $tpl_value
        ));
        return $response;
    }

    public function getWxuser($openid,$device_id){
        return $this->db->from($this->tb_device_wxuser)->where('open_id',$openid)->where('device_id',$device_id)->get()->row();
    }

    public function addBangding($array){
        $this->db->insert($this->tb_device_wxuser,$array);
    }

    public function delRelieve($id){
        $strSql = 'delete from '.$this->tb_device_wxuser.' where id = '.$id;
        $this->db->query($strSql);
    }

    public function getFeedback($feed){
        $query=$this->db->get_where($this->tb_feedback,$feed);
        return $query->result();
    }

    public function getFeedbackReply($feed){
        $query=$this->db->get_where($this->tb_feedback_reply,$feed);
        return $query->result();
    }

    public function updateFeedback($feed,$where){
        return $this->db->update($this->tb_feedback,$feed,$where);
    }

    public function addFeedback($feed){
        return $this->db->insert($this->tb_feedback, $feed);
    }

    public function addFeedbackReply($feed){
        return $this->db->insert($this->tb_feedback_reply, $feed);
    }

    public function getthispwd($login_pwd,$salt){
        return sha1($login_pwd . $salt);
    }
}
