## Codeigniter Restful API

### 说明

随着移动互联网的兴起，服务器端变的越来越轻，更多的转向API接口开发，如何能快速的开发出效率高，安全，能限速，便于做版本控制的API服务呢？这里提供了一种方案。

系统基于Codeigniter 3.0进行开发，参考了chriskacerguis/codeigniter-restserver项目，做了许多改进。

该方案能快速构架RESTFUL风格的API，提供Version 分发，Auth 验证，访问限速，黑名单过滤，白名单过滤，签名验证等多种功能，提供了接口服务应有的基本功能。

最重要的，该框架结构简单，易于理解，易于扩展，您即可以直接使用该框架，也可以根据自己的实际业务需求进行扩展。

Codeigniter 的优点是非常明显的，简单，轻便，文档细致，易于学习，效率高！

Codeigniter 的缺点也是非常明显的，设计理念是简单的MVC，自己开发的类库与系统类库耦合性高。

不过，因为API服务大部分是比较简单的，CI就显得非常适合了。

### 特点

- Speed：CI框架非常小巧，执行效率高，开发也同样快。
- RestFul: 非常容易进行控制器编写，支持Get，Post，Put，Delete，Head等多种方法。
- Format:  支持多种格式的输入输出，常规的为Json，XML。
- Version: 支持多个版本的api。
- Token:   Token机制，用户登录之后，分发一个Token，代表用户特定的资源，Token有时效性，过期需要重新获取。
- Signature: 签名机制，不合法的访问将直接被屏蔽。
- BlackList: 黑名单机制，被加入黑名单的IP地址，将直接被屏蔽，如果配合Iptables等防火墙,效果更佳。
- WhiteList: 白名单机制，跳过各种检查机制，便于测试。
- AccessLimit : 访问速率控制，同一个IP地址在一定的时间内，不能超过设定的访问次数。

### 系统要求
- CI3.0
- php5.4+
- Redis: 用于 Auth验证，Limit Control,用Mysql也可以，不过Reids效率更高。

### 安装

安装好CI之后，增加几个文件就好，一共不超过10个。

### 配置
配置config文件rest.php

### 如何使用
#### 全新安装 CI3.0 和redis数据库，安装predis扩展或者phpredis库
略
#### 拷贝以下文件到您的 APP目录

	/app/libraries/REST_Controller.php
	/app/libraries/Format.php
	/app/libraries/ApiCheck/
	
	/app/config/rest.php
	/app/core/MY_Router.php

#### 在app/controllers目录下新增您的api controller文件

如： api_v1.php


	<?php defined('BASEPATH') OR exit('No direct script access allowed');

	require APPPATH.'/libraries/REST_Controller.php';

	class Api_v1 extends REST_Controller
	{
		//
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
	        $app_id=$this->uri->segment('4');
	        $login_id=$this->uri->segment('5');
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
	}

#### 常规使用

#### header中需要传入的参数

header 中有三个必填参数，键名统一用小写。

- apiver（必填）   ：这里对应的是api接口的版本号，例如 v1；
- token（接口相关） ：这里对应的使用户的身份认证token，但不是所有的接口都有；
- signature（必填）: 访问签名信息；

#### 如何使用token auth

用户登录之后，随机生成token，并写入redis，设置有效时间，并返回结果。

需要授权的页面，通过redis验证token，如果没有，则要求重新登录以获取新的token。

#### 如何使用签名

应签名加密的要求，这里设置一个客户端和服务器约定好的key，规则可以自己设定，以下为例子

签名可以分为两种情况，构造方式如下：

- 接口不需要token  
对url中`api`之后的所有字符串`拼接`后进行`MD5加密`，然后`拼接`指定 `key` 第二次`MD5加密`，如：
    
        访问/api/login_id/1/18600364250
        
        key = 'skyware'; 
        apiver = 'v1';
        
        signature = MD5(MD5('login_id'.'1'.'18600364250').'v1'.'yourkey')。

> 其中`.`为字符串拼接符号，请根据具体的语言进行处理.    

- 接口需要token

        访问/api/devices
        
        key = 'skyware'; 
        apiver = 'v1';
        token = '19234'
    
        signature = MD5(MD5('devices').'v1'.'19234'.'yourkey')。

#### 如何使用限速访问



#### 如何使用白名单，黑名单





#### 常用的response code

    200：响应成功
    400：签名不正确或请求参数不正确
	401：token不存在
	403：禁止访问（黑名单）
    404：请求无结果
	405：方法不存在
	406：必须https
	429：访问速度过快
    500：服务器错误
	501：创建文件夹失败

