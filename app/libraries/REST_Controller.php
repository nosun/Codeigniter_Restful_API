<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Rest Controller
 * A fully RESTful server implementation for CodeIgniter using one library, one config file and one controller.
 *
 * @package         CodeIgniter
 * @subpackage      Libraries
 * @category        Libraries
 * @author          Phil Sturgeon, Chris Kacerguis, nosun
 * @license         MIT
 * @link            https://github.com/nosun/Codeigniter_Restful_API
 * @version         1.0
 */

abstract class REST_Controller extends CI_Controller {
    /**
     * This defines the rest format.
     * Must be overridden it in a controller so that it is set.
     *
     * @var string|NULL
     */
    protected $rest_format = NULL;

    /**
     * Defines the list of method properties such as limit, log and level
     *
     * @var array
     */
    protected $methods = [];

    /**
     * List of allowed HTTP methods
     *
     * @var array
     */
    protected $allowed_http_methods = ['get', 'delete', 'post', 'put', 'options', 'patch', 'head'];

    /**
     * Contains details about the request
     * Fields: body, format, method, ssl
     * Note: This is a dynamic object (stdClass)
     *
     * @var object
     */
    protected $request = NULL;

    /**
     * Contains details about the response
     * Fields: format, lang
     * Note: This is a dynamic object (stdClass)
     *
     * @var object
     */
    protected $response = NULL;

    /**
     * Contains details about the REST API
     * Fields: db, ignore_limits, key, level, user_id
     * Note: This is a dynamic object (stdClass)
     *
     * @var object
     */
    protected $rest = NULL;

    /**
     * The arguments for the GET request method
     *
     * @var array
     */
    protected $_get_args = [];

    /**
     * The arguments for the POST request method
     *
     * @var array
     */
    protected $_post_args = [];

    /**
     * The insert_id of the log entry (if we have one)
     *
     * @var string
     */
    protected $_insert_id = '';

    /**
     * The arguments for the PUT request method
     *
     * @var array
     */
    protected $_put_args = [];

    /**
     * The arguments for the DELETE request method
     *
     * @var array
     */
    protected $_delete_args = [];

    /**
     * The arguments for the PATCH request method
     *
     * @var array
     */
    protected $_patch_args = [];

    /**
     * The arguments for the HEAD request method
     *
     * @var array
     */
    protected $_head_args = [];

    /**
     * The arguments for the OPTIONS request method
     *
     * @var array
     */
    protected $_options_args = [];

    /**
     * The arguments from GET, POST, PUT, DELETE request methods combined.
     *
     * @var array
     */
    protected $_args = [];

    /**
     * If the request is allowed based on the API key provided.
     *
     * @var bool
     */
    protected $_allow = TRUE;

    /**
     * Determines if output compression is enabled
     *
     * @var bool
     */
    protected $_zlib_oc = FALSE;

    /**
     * The start of the response time from the server
     *
     * @var string
     */
    protected $_start_rtime = '';

    /**
     * The end of the response time from the server
     *
     * @var string
     */
    protected $_end_rtime = '';

    /**
     * List all supported methods, the first will be the default format
     *
     * @var array
     */
    protected $_supported_formats = [
        'json'  => 'application/json',
        'jsonp' => 'application/javascript',
        'serialized' => 'application/vnd.php.serialized',
        'xml'  => 'application/xml'
    ];

    /**
     * Information about the current API user
     *
     * @var object
     */
    protected $_apiuser;

    /**
     * Enable XSS flag
     * Determines whether the XSS filter is always active when
     * GET, OPTIONS, HEAD, POST, PUT, DELETE and PATCH data is encountered.
     * Set automatically based on config setting.
     *
     * @var bool
     */
    protected $_enable_xss = FALSE;


    /**
     * Constructor for the REST API
     *
     * @access public
     *
     * @param string $config Configuration filename minus the file extension
     * e.g: my_rest.php is passed as 'my_rest'
     */
    public function __construct($config = 'rest')
    {
        parent::__construct();

        // Disable XML Entity (security vulnerability)

        libxml_disable_entity_loader(TRUE);

        // Set the default value of global xss filtering. Same approach as CodeIgniter 3
        $this->_enable_xss = ($this->config->item('global_xss_filtering') === TRUE);

        // Start the timer for how long the request takes
        $this->_start_rtime = microtime(TRUE);

        // Load the rest.php configuration file
        $this->load->config($config);

        // At present the library is bundled with REST_Controller 2.5+, but will eventually be part of CodeIgniter (no citation)
        $this->load->library('format');

        // Initialise the response, request and rest objects
        $this->request = new stdClass();
        $this->response = new stdClass();
        $this->rest = new stdClass();

        $this->_zlib_oc = @ini_get('zlib.output_compression');

        if ($this->config->item('rest_ip_blacklist_enabled') === TRUE) {
            if ($this->_check_blacklist_auth() === TRUE) {
                $this->response([$this->config->item('rest_message_field_name') => 402], 200);
            }
        }

        // Determine whether the connection is HTTPS
        $this->request->ssl = is_https();

        // How is this request being made? GET, POST, PATCH, DELETE, INSERT, PUT, HEAD or OPTIONS
        $this->request->method = $this->_detect_method();

        // Create an argument container if it doesn't exist e.g. _get_args
        if (isset($this->{'_' . $this->request->method . '_args'}) === FALSE) {
            $this->{'_' . $this->request->method . '_args'} = [];
        }

        // Set up the GET variables /controller/method/var1/var2 => array('var1'=>'var2');
        $this->_get_args = array_merge($this->_get_args, $this->uri->ruri_to_assoc());

        // Try to find a format for the request (means we have a request body)
        $this->request->format = $this->_detect_input_format();

        // Not all methods have a body attached with them
        $this->request->body = NULL;

        $this->{'_parse_' . $this->request->method}();

        // Now we know all about our request, let's try and parse the body if it exists
        if ($this->request->format && $this->request->body) {
            $this->request->body = $this->format->factory($this->request->body, $this->request->format)->to_array();
            // Assign payload arguments to proper method container
            $this->{'_' . $this->request->method . '_args'} = $this->request->body;
        }

        // Merge both for one mega-args variable
        $this->_args = array_merge(
            $this->_get_args,
            $this->_options_args,
            $this->_patch_args,
            $this->_head_args,
            $this->_put_args,
            $this->_post_args,
            $this->_delete_args,
            $this->{'_' . $this->request->method . '_args'}
        );

        // Which format should the data be response // in config
        $this->response->format = $this->config->item('rest_default_format');

        // check signature , if the request accord to the rule of signature
        if ($this->config->item('signature_check_enable') === TRUE){
            if ($this->config->item('rest_ip_whitelist_enabled') === TRUE){
                if($this->_check_whitelist_auth() === TRUE){
                    return TRUE;
                }
            }
            $this->load->library('Signature');
            $auth = new Signature();
            if ($auth->Check() === FALSE) {
                $this->response([$this->config->item('rest_message_field_name') => 400], 200);
            }
        }

        // check auth ,if the user has ability to access the api resource
        if ($this->config->item('auth_check_enable') === TRUE){
            $this->load->library('Auth');
            $auth = new Auth();
            if($auth->Check() === FALSE){
                $this->response([$this->config->item('rest_message_field_name') => 401], 200);
            }
        }

        // check limit by ip, if over limit, response 429  too many request;
        if ($this->config->item('limits_check_enable') === TRUE) {
            if ($this->config->item('rest_ip_whitelist_enabled') === TRUE){
                if($this->_check_whitelist_auth() === TRUE){
                    return TRUE;
                }
            }
            $this->load->library('AccessLimit');
            $auth = new AccessLimit();
            if ($auth->Check() === FALSE) {
                $this->response([$this->config->item('rest_message_field_name') => 429], 200);
            }
        }
    }

    /**
     * Deconstructor
     *
     * @author Chris Kacerguis
     * @access public
     */
    public function __destruct()
    {
        // Get the current timestamp
        $this->_end_rtime = microtime(TRUE);

        // Log the loading time to the log table
        if ($this->config->item('rest_enable_logging') === TRUE)
        {
            $this->_log_access_time();
        }
    }

    /**
     * 控制器 初始化完成之后，该request 对象 被解析给正确的 function
     *
     * @access public
     *
     * @param  string $object_called   // function name
     * @param  array $arguments        // The arguments passed to the controller method.
     */
    public function _remap($object_called, $arguments)
    {

        // https force 判断
        if ($this->config->item('force_https') && $this->request->ssl === FALSE)
        {
            // http访问 不允许
            $this->response([$this->config->item('rest_message_field_name') => 406], 200);
        }

        $controller_method = $object_called . '_' . $this->request->method;

        // 方法不存在
        if (method_exists($this, $controller_method) === FALSE)
        {
            $this->response([$this->config->item('rest_message_field_name') => 405], 200);
        }

        // 传递参数给正确的方法
        try
        {
            call_user_func_array([$this, $controller_method], $arguments);
        }
        catch (Exception $ex)
        {
            // 方法不存在
            $this->response([$this->config->item('rest_message_field_name') => 405], 200);
        }
    }

    /**
     * Takes mixed data and optionally a status code, then creates the response
     *
     * @access public
     *
     * @param array|NULL $data Data to output to the user
     * @param int|NULL $http_code HTTP status code
     * @param bool $continue TRUE to flush the response to the client and continue
     * running the script; otherwise, exit
     */
    public function response($data = NULL, $http_code = NULL, $continue = FALSE)
    {
        // If the HTTP status is not NULL, then cast as an integer
        if ($http_code !== NULL)
        {
            // So as to be safe later on in the process
            $http_code = (int) $http_code;
        }

        // Set the output as NULL by default
        $output = NULL;

        // If data is NULL and no HTTP status code provided, then display, error and exit
        if ($data === NULL && $http_code === NULL)
        {
            $http_code = 404;
        }

        // If data is not NULL and a HTTP status code provided, then continue
        elseif ($data !== NULL)
        {
            // Is compression enabled and available?
            if ($this->config->item('compress_output') === TRUE && $this->_zlib_oc == FALSE)
            {
                if (extension_loaded('zlib'))
                {
                    $http_encoding = $this->input->server('HTTP_ACCEPT_ENCODING');
                    if ($http_encoding !== NULL && strpos($http_encoding, 'gzip') !== FALSE)
                    {
                        ob_start('ob_gzhandler');
                    }
                }
            }

            // If the format method exists, call and return the output in that format
            if (method_exists($this->format, 'to_' . $this->response->format))
            {
                //Set the format header
                header('Content-Type: ' . $this->_supported_formats[$this->response->format]
                    . '; charset=' . strtolower($this->config->item('charset')));

                $output = $this->format->factory($data)->{'to_' . $this->response->format}();

                // An array must be parsed as a string, so as not to cause an array to string error.
                // Json is the most appropriate form for such a datatype
                if ($this->response->format === 'array')
                {
                    $output = $this->format->factory($output)->{'to_json'}();
                }
            }
            else
            {
                // If an array or object, then parse as a json, so as to be a 'string'
                if (is_array($data) || is_object($data))
                {
                    $data = $this->format->factory($data)->{'to_json'}();
                }

                // Format is not supported, so output the raw data as a string
                $output = $data;
            }
        }

        // If not greater than zero, then set the HTTP status code as 200 by default
        // Though perhaps 500 should be set instead, for the developer not passing a
        // correct HTTP status code

        $http_code > 0 ?  : $http_code = 200;

        set_status_header($http_code);

        // JC: Log response code only if rest logging enabled
        if ($this->config->item('rest_enable_logging') === TRUE)
        {
            $this->_log_response_code($http_code);
        }

        // If zlib.output_compression is enabled it will compress the output,
        // but it will not modify the content-length header to compensate for
        // the reduction, causing the browser to hang waiting for more data.
        // We'll just skip content-length in those cases

        if (!$this->_zlib_oc && !$this->config->item('compress_output'))
        {
            //header('Content-Length: ' . strlen($output));
        }

        if ($continue === FALSE)
        {
            exit($output);
        }

        echo($output);
        ob_end_flush();
        ob_flush();
        flush();
    }

    /**
     * Get the input format e.g. json or xml
     *
     * @access private
     * @return string|NULL Supported input format; otherwise, NULL
     */
    private function _detect_input_format()
    {
        // Get the CONTENT-TYPE value from the SERVER variable
        // default form post => application/x-www-form-urlencoded
        $contentType = $this->input->server('CONTENT_TYPE');
        //var_dump($contentType);

        if (empty($contentType) === FALSE)
        {
            // Check all formats against the HTTP_ACCEPT header
            foreach ($this->_supported_formats as $key => $value)
            {
                // $key = format e.g. csv
                // $value = mime type e.g. application/csv

                // If a semi-colon exists in the string, then explode by ; and get the value of where
                // the current array pointer resides. This will generally be the first element of the array
                $contentType = (strpos($contentType, ';') !== FALSE ? current(explode(';', $contentType)) : $contentType);

                // If both the mime types match, then return the format
                if ($contentType === $value)
                {
                    return $key;
                }
            }
        }

        return NULL;
    }

    /**
     * Get the HTTP request string e.g. get or post
     *
     * @return string|NULL Supported request method as a lowercase string; otherwise, NULL if not supported
     */
    protected function _detect_method()
    {
        // Declare a variable to store the method
        $method = NULL;

        // Determine whether the 'enable_emulate_request' setting is enabled
        if ($this->config->item('enable_emulate_request') === TRUE)
        {
            $method = $this->input->post('_method');
            if ($method === NULL)
            {
                $method = $this->input->server('HTTP_X_HTTP_METHOD_OVERRIDE');
            }

            $method = strtolower($method);
        }

        if (empty($method))
        {
            // Get the request method as a lowercase string.
            $method = $this->input->method();
        }

        return in_array($method, $this->allowed_http_methods) && method_exists($this, '_parse_' . $method) ? $method : 'get';
    }

    // parse http method args -------------------------------------------------------

    /**
     * Parse the HEAD request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_head()
    {
        // Parse the HEAD variables
        parse_str(parse_url($this->input->server('REQUEST_URI'), PHP_URL_QUERY), $head);

        // Merge both the URI segments and HEAD params
        $this->_head_args = array_merge($this->_head_args, $head);
    }
    /**
     * Parse the GET request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_get()
    {
        // Declare a variable that will hold the REQUEST_URI
        $request_uri = NULL;

        // Fix for Issue #247
        if (is_cli())
        {
            $args = $this->input->server('argv');
            unset($args[0]);
            // Combine the arguments using '/' as the delimiter
            $request_uri = '/' . implode('/', $args) . '/';

            // Set the following server variables
            $_SERVER['REQUEST_URI'] = $request_uri;
            $_SERVER['PATH_INFO'] = $request_uri;
            $_SERVER['QUERY_STRING'] = $request_uri;
        }
        else
        {
            $request_uri = $this->input->server('REQUEST_URI');
        }

        // Declare a variable that will hold the parameters
        $get = NULL;

        // Grab the GET variables from the query string
        parse_str(parse_url($request_uri, PHP_URL_QUERY), $get);

        // Merge both the URI segments and GET params
        $this->_get_args = array_merge($this->_get_args, $get);
    }

    /**
     * Parse the POST request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_post()
    {
        $this->_post_args = $_POST;

        if ($this->request->format)
        {
            $this->request->body = $this->input->raw_input_stream;
        }
    }

    /**
     * Parse the PUT request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_put()
    {
        if ($this->request->format)
        {
            $this->request->body = $this->input->raw_input_stream;
        }
        else
        {
            // If no filetype is provided, then there are probably just arguments
            if ($this->input->method() === 'put')
            {
                $this->_put_args = $this->input->input_stream();
            }
        }
    }

    /**
     * Parse the DELETE request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_delete()
    {
        // These should exist if a DELETE request
        if ($this->input->method() === 'delete')
        {
            $this->_delete_args = $this->input->input_stream();
        }
    }


    /**
     * Parse the OPTIONS request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_options()
    {
        // Parse the OPTIONS variables
        parse_str(parse_url($this->input->server('REQUEST_URI'), PHP_URL_QUERY), $options);

        // Merge both the URI segments and OPTIONS params
        $this->_options_args = array_merge($this->_options_args, $options);
    }

    /**
     * Parse the PATCH request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_patch()
    {
        // It might be a HTTP body
        if ($this->request->format)
        {
            $this->request->body = $this->input->raw_input_stream;
        }
        else
        {
            // If no filetype is provided, then there are probably just arguments
            if ($this->input->method() === 'patch')
            {
                $this->_patch_args = $this->input->input_stream();
            }
        }
    }

    // INPUT FUNCTION --------------------------------------------------------------

    /**
     * Retrieve a value from a HEAD request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the HEAD request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the HEAD request; otherwise, FALSE
     */
    public function head($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->head_args;
        }

        return array_key_exists($key, $this->head_args) ? $this->_xss_clean($this->head_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from a GET request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the GET request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the GET request; otherwise, FALSE
     */
    public function get($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_get_args;
        }

        return array_key_exists($key, $this->_get_args) ? $this->_xss_clean($this->_get_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from a POST request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the POST request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the POST request; otherwise, FALSE
     */
    public function post($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_post_args;
        }

        return array_key_exists($key, $this->_post_args) ? $this->_xss_clean($this->_post_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from a PUT request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the PUT request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the PUT request; otherwise, FALSE
     */
    public function put($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_put_args;
        }

        return array_key_exists($key, $this->_put_args) ? $this->_xss_clean($this->_put_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from a DELETE request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the DELETE request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the DELETE request; otherwise, FALSE
     */
    public function delete($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_delete_args;
        }

        return array_key_exists($key, $this->_delete_args) ? $this->_xss_clean($this->_delete_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from a PATCH request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the PATCH request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the PATCH request; otherwise, FALSE
     */
    public function patch($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_patch_args;
        }

        return array_key_exists($key, $this->_patch_args) ? $this->_xss_clean($this->_patch_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Retrieve a value from a OPTIONS request
     *
     * @access public
     *
     * @param NULL $key Key to retrieve from the OPTIONS request.
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     *
     * @return array|string|FALSE Value from the OPTIONS request; otherwise, FALSE
     */
    public function options($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_options_args;
        }

        return array_key_exists($key, $this->_options_args) ? $this->_xss_clean($this->_options_args[$key], $xss_clean) : FALSE;
    }

    /**
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented.
     *
     * @access protected
     *
     * @param  string $value Input data
     * @param  bool $xss_clean Whether to apply XSS filtering
     *
     * @return string
     */
    protected function _xss_clean($value, $xss_clean)
    {
        is_bool($xss_clean) OR $xss_clean = $this->_enable_xss;

        return $xss_clean === TRUE ? $this->security->xss_clean($value) : $value;
    }

    /**
     * Retrieve the validation errors
     *
     * @access public
     * @return array
     */
    public function validation_errors()
    {
        $string = strip_tags($this->form_validation->error_string());

        return explode("\n", trim($string, "\n"));
    }

    // SECURITY FUNCTIONS ---------------------------------------------------------


    /**
     * Checks if the client's ip is in the 'rest_ip_blacklist' config and generates a 401 response
     *
     * @access protected
     */

    protected function _check_blacklist_auth()
    {
        $blacklist = explode(',', $this->config->item('rest_ip_blacklist'));

        foreach ($blacklist AS &$ip)
        {
            $ip = trim($ip);
        }

        if (in_array($this->input->ip_address(), $blacklist) === TRUE)
        {
            return TRUE;
        }

        return FALSE;
    }


    /**
     * Check if the client's ip is in the 'rest_ip_whitelist' config and generates a 401 response
     *
     * @access protected
     * @return bool
     */

    protected function _check_whitelist_auth()
    {
        $whitelist = explode(',', $this->config->item('rest_ip_whitelist'));

        array_push($whitelist, '127.0.0.1', '0.0.0.0');

        foreach ($whitelist AS &$ip)
        {
            $ip = trim($ip);
        }

        if (in_array($this->input->ip_address(), $whitelist) === TRUE)
        {
            return TRUE;
        }

        return FALSE;
    }



    /**
     * check if the signature is right?
     * @access protected
     * @return bool
     */

    protected function _signature_check(){
        $header = $this->input->request_headers();
        $token = isset($header['Token'])?$header['Token']:'';
        $signature = isset($header['Signature'])?$header['Signature']:'';
        if($signature == ''){
            return FALSE;
        }
        $parameter = $token;
        for($i = 2;$this->uri->segment("$i") != ''; $i++){
            $parameter = $parameter.$this->uri->segment("$i");
        }
        $path = md5(md5($parameter).$this->config->item('rest_signature_key'));
        if($signature != $path){
            return FALSE;
        }
        return TRUE;
    }


}