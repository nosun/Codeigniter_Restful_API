<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| HTTP protocol
|--------------------------------------------------------------------------
|
| Set to force the use of HTTPS for REST API calls
|
*/
$config['force_https'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST Format
|--------------------------------------------------------------------------
|
| The default format of the response, generally we just use json and xml
|
| 'array':      Array data structure
| 'csv':        Comma separated file
| 'json':       Uses json_encode(). Note: If a GET query string
|               called 'callback' is passed, then jsonp will be returned
| 'html'        HTML using the table library in CodeIgniter
| 'php':        Uses var_export()
| 'serialized':  Uses serialize()
| 'xml':        Uses simplexml_load_string()
|
*/
$config['rest_default_format'] = 'json';

/*
|--------------------------------------------------------------------------
| REST Status Field Name
|--------------------------------------------------------------------------
|
| The field name for the status inside the response
|
*/
$config['rest_status_field_name'] = 'status';

/*
|--------------------------------------------------------------------------
| REST Message Field Name
|--------------------------------------------------------------------------
|
| The field name for the message inside the response
|
*/
$config['rest_message_field_name'] = 'message';

/*
|--------------------------------------------------------------------------
| Enable Emulate Request
|--------------------------------------------------------------------------
|
| Should we enable emulation of the request (e.g. used in Mootools request)
|
*/

$config['enable_emulate_request'] = TRUE;

/*
|--------------------------------------------------------------------------
| REST Ignore HTTP Accept
|--------------------------------------------------------------------------
|
| Set to TRUE to ignore the HTTP Accept and speed up each request a little.
| Only do this if you are using the $this->rest_format or /format/xml in URLs
|
*/
$config['rest_ignore_http_accept'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST Check
|--------------------------------------------------------------------------
|
| 1. blackList check
| Prevent connections to the REST server from blacklisted IP addresses
| 2. whiteList check
| Pass the request if the client in WhiteList
| 3. Signature check
|    if enable signature，server will check the signature passed from header,
|    this need client do the same md5 signature。
| 2、rest_signature_key is the salt for md5 signature
| 4. Auth check
|    1)if enable auth_check, server will check the token passed from header
|    2)need user login, and then give token to client,at the same time save the token
|      to redis。
|    3)when check auth, get token from redis,if exist, get the user pass.
|    4)the token in fact is Identity of the user, you can use it in your function too.
|    5)the auth check function you can override it in your controller
|    6)the auth function for get token you must implement yourself.
| 5. Limit check : control the access limit
|
| you can set the Check Class in an array like below example.
| and you also can add new Class in library => Auth file, then add it in config.
| eg. $config['check_class'] = ['BlackList','WhiteList','Signature','Auth','Limit'];
|
*/


$config['check_class'] = ['BlackList','WhiteList','Signature','Auth','Limit'];

// BlackList
$config['rest_ip_blacklist'] = '987.654.32.1';

// whiteList
$config['rest_ip_whitelist'] = '0.0.0.0';

// signature salt key
$config['signature_key'] = 'skyware';

// redis pre for auth
$config['auth_pre'] = 'token_';
// Api method which is public, without token auth.
$config['auth_pass']= ['token_post','login_id_get','user_post',
       'passwd_post' ,'app_get','appHost_get','wpm_post','deviceMac_post',
       'testSpeed_get','testHttp_get','testDelay_get',
       ];

// limit check relation
$config['limits_check_enable'] = TRUE;
$config['limits_rate'] = 5;
$config['limits_time'] = 8;
$config['limits_cache'] = 'redis';
$config['limits_model'] = 'redis_model';
$config['limits_pre'] = 'limit_';