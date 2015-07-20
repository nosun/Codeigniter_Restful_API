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
| Enable signature
|--------------------------------------------------------------------------
|
| 1、if enable signature，server will check the signature passed from header,
|    this need client do the same md5 signature。
| 2、rest_signature_key is the salt for md5 signature
|
*/

$config['signature_check_enable'] = TRUE;
$config['signature_key'] = 'skyware';

/*
|--------------------------------------------------------------------------
| Enable auth_check
|--------------------------------------------------------------------------
|
| 1、if enable auth_check, server will check the token passed from header
| 2、need user login, and then give token to client,at the same time save the token
|   to redis。
| 3、when check auth, get token from redis,if exist, get the user pass.
| 4、the token in fact is Identity of the user, you can use it in your function too.
| 5、the auth check function you can override it in your controller
| 6、the auth function for get token you must implement yourself.
|
*/

$config['auth_check_enable']= TRUE;

/*
|--------------------------------------------------------------------------
| The method ignore auth check
|--------------------------------------------------------------------------
|
| Api method which is public, without token auth.
|
*/
$config['auth_pass']= ['index_get'];

/*
|--------------------------------------------------------------------------
| Global IP Whitelisting
|--------------------------------------------------------------------------
|
| Limit connections Just For Testing
|
| Usage:
| 1. will not need signature check, direct pass
| 2. will not need limit check, direct pass
|
*/

$config['rest_ip_whitelist_enabled'] = TRUE;

/*
|--------------------------------------------------------------------------
| REST IP Whitelist
|--------------------------------------------------------------------------
|
| Limit connections to your REST server with a comma separated
| list of IP addresses
|
| e.g: '123.456.789.0, 987.654.32.1'
|
| 127.0.0.1 and 0.0.0.0 are allowed by default
|
*/

$config['rest_ip_whitelist'] = '0.0.0.0';

/*
|--------------------------------------------------------------------------
| Global IP Blacklisting
|--------------------------------------------------------------------------
|
| Prevent connections to the REST server from blacklisted IP addresses
|
| Usage:
| 1. Set to TRUE and add any IP address to 'rest_ip_blacklist'
|
*/
$config['rest_ip_blacklist_enabled'] = TRUE;

/*
|--------------------------------------------------------------------------
| REST IP Blacklist
|--------------------------------------------------------------------------
|
| Prevent connections from the following IP addresses
|
| e.g: '123.456.789.0, 987.654.32.1'
|
*/
$config['rest_ip_blacklist'] = '987.654.32.1';


/*
|--------------------------------------------------------------------------
| REST Ignore HTTP Accept
|--------------------------------------------------------------------------
|
| Set True to open limit control;
| use Redis to save limit data by default;
|
*/

$config['limits_check_enable'] = TRUE;
$config['limits_rate'] = 5;
$config['limits_time'] = 8;
$config['limits_cache'] = 'redis';
$config['limits_model'] = 'redis_model';
$config['limits_pre'] = 'limit_';

$config['auth_pre'] = 'token_';

$config['check_class'] = ['Auth','Limit'];


