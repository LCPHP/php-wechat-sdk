<?php
namespace niklaslu;


class Wechat {

    public $version = '1.0.0';

    //appID
    private $appid = '';

    //appsecret
    private $appsecret = '';

    //access_token
    private $access_token = '';
    
    private $Cache = null;

    //授权回调地址
    private $redirect_uri = '';

    const OPEN_URI = 'https://open.weixin.qq.com/';
    const API_URI = 'https://api.weixin.qq.com/';

    const ACCESS_TOKEN_URI = 'cgi-bin/token';
    const IP_LIST_URI = 'cgi-bin/getcallbackip';
    const USER_INFO_URI = 'cgi-bin/user/info';
    const USER_LIST_URI = 'cgi-bin/user/get';
    const USER_LIST_INFO_URI = 'cgi-bin/user/info/batchget';

    const AUTH_URI = 'connect/oauth2/authorize';
    const AUTH_ACCESS_TOKEN_URI = 'sns/oauth2/access_token';
    const AUTH_USER_URI = 'sns/userinfo';

    const SNSAPI_USERINFO = 'snsapi_userinfo';
    const SNSAPI_BASE = 'snsapi_base';


    public $error = '';
    /**
     * 传入微信配置
     * @param array $config
     */
    public function __construct($config){

        $this->appid = $config['appid'];
        $this->appsecret = $config['appsecret'];
        $this->redirect_uri = $config['redirect_uri'];
        $this->access_token = $this->get_access_token();

    }

    /**
     * 返回access_token的值
     * @return Ambigous <string, boolean, mixed, unknown>
     */
    public function return_access_token(){

        return $this->access_token;
    }
    /**
     * 获取access_token
     */
    private function get_access_token(){

        $data = $this->cache('access_token_data');
        if ($data){
            //从缓存中取
            $access_token = $data['access_token'];
            return $access_token;
        }else {
            $url = self::API_URI . self::ACCESS_TOKEN_URI;

            $param['appid'] = $this->appid;
            $param['secret'] = $this->appsecret;
            $param['grant_type'] = 'client_credential';

            $access_token_url = $this->create_url($url, $param);

            $data = $this->http_get($access_token_url);
            $data = $this->return_data($data);
            if ($data){
                $access_token = $data['access_token'];
                $this->cache('access_token_data' , $data , $data['expires_in'] - 100);
                return $access_token;
            }else{
                return false;
            }
        }


    }

    /**
     * 获取微信服务器的ip列表
     * @param string $access_token
     */
    public function get_ip_list(){

        $url = self::API_URI . self::IP_LIST_URI;
        $param['access_token'] = $this->access_token;

        $ip_list_url = $this->create_url($url, $param);

        $data = $this->http_get($ip_list_url);

        return $this->return_data($data);
    }

    /**
     * 生成授权url
     */
    public function get_auth_url($scope = ''){

        $url = self::OPEN_URI . self::AUTH_URI;

        $param['appid'] = $this->appid;
        $param['redirect_uri'] = $this->redirect_uri;
        $param['response_type'] = 'code';
        $param['scope'] = $scope ? $scope : self::SNSAPI_USERINFO;
        $param['state'] = '1';
        $auth_url = $this->create_url($url, $param);
        $auth_url .= '#wechat_redirect';

        return $auth_url;
    }

    /**
     * 获取通过code获得access_token的链接
     * @param string $code
     * @return string
     */
    public function get_auth_access_token($code){

        $url = self::API_URI . self::AUTH_ACCESS_TOKEN_URI;

        $param['appid'] = $this->appid;
        $param['secret'] = $this->appsecret;
        $param['code'] = $code;
        $param['grant_type'] = 'authorization_code';

        $access_token_url = $this->create_url($url, $param);
        $data = $this->http_get($access_token_url);

        return $this->return_data($data);
    }

    /**
     * 获取用户信息
     * @param string $openid
     * @param string $lang
     * @return Ambigous <\Org\Com\boolean, \Org\Com\unknown>
     */
    public function get_user_info($openid , $lang = 'zh_CN'){

        $url = self::API_URI . self::USER_INFO_URI;
        $param['access_token'] = $this->access_token;
        $param['openid'] = $openid;
        $param['lang'] = $lang;

        $user_info_url = $this->create_url($url, $param);
        $data = $this->http_get($user_info_url);

        return $this->return_data($data);
    }

    /**
     * 获取用户列表信息
     * @param unknown $data
     * @return boolean|\Org\Com\unknown
     */
    public function get_user_list_info($data){

        $url = self::API_URI . self::USER_LIST_INFO_URI;
        $param['access_token'] = $this->access_token;
         
        $user_list_url = $this->create_url($url, $param);

        $postData['user_list'] = $data;
        $postData = json_encode($postData,true);

        $data = $this->http_post($user_list_url, $postData);
         
        return $this->return_data($data);
    }
    /**
     * 获取用户列表
     */
    public function get_user_list($next = ''){
         
        $url = self::API_URI . self::USER_LIST_URI;
        $param['access_token'] = $this->access_token;
        $param['next_openid'] = $next;
         
         
        $user_list_url = $this->create_url($url, $param);
        $data = $this->http_get($user_list_url);
         
        return $this->return_data($data);
    }
     
    /**
     * 网页授权获取用户信息
     * @param string $openid
     * @param string $access_token
     * @param string $lang
     * @return Ambigous <\Org\Com\boolean, \Org\Com\unknown>
     */
    public function get_auth_user($openid , $access_token , $lang = 'zh_CN'){

        $url = self::API_URI . self::AUTH_USER_URI;
        $param['access_token'] = $access_token;
        $param['openid'] = $openid;
        $param['lang'] = $lang;

        $user_info_url = $this->create_url($url, $param);
        $data = $this->http_get($user_info_url);

        return $this->return_data($data);
    }
    /**
     * 返回data
     * @param array $data
     * @return boolean|unknown
     */
    public function return_data($data){

        if (isset($data['errcode'])){
            $this->error = $data['errmsg'];
            return false;
        }else{
            return $data;
        }
    }
    /**
     * 生成url
     * @param string $url
     * @param 参数 $param
     */
    public function create_url($url , $param){

        $url .= "?";
        $i = 0;
        foreach ($param as $k=>$v){
            $i++;
            if ($i == count($param)){
                $url .= $k . '=' . $v;
            }else{
                $url .= $k . '=' . $v . '&';
            }

        }

        return $url;
    }

    /**
     * http curl get
     * @param string $url
     * @param string $data_type
     * @return mixed|boolean
     */
    public function http_get($url, $data_type='json') {

        $cl = curl_init();
        if(stripos($url, 'https://') !== FALSE) {
            curl_setopt($cl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($cl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($cl, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($cl, CURLOPT_URL, $url);
        curl_setopt($cl, CURLOPT_RETURNTRANSFER, 1 );
        $content = curl_exec($cl);
        $status = curl_getinfo($cl);
        curl_close($cl);
        if (isset($status['http_code']) && $status['http_code'] == 200) {
            if ($data_type == 'json') {
                $content = json_decode($content , true);
            }
            return $content;
        } else {
            return FALSE;
        }
    }

    /**
     * http curl post
     * @param string $url
     * @param unknown $fields
     * @param string $data_type
     * @return mixed|boolean
     */
    public function http_post($url, $fields, $data_type='json') {

        $cl = curl_init();
        if(stripos($url, 'https://') !== FALSE) {
            curl_setopt($cl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($cl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($cl, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($cl, CURLOPT_URL, $url);
        curl_setopt($cl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($cl, CURLOPT_POST, true);
        // convert @ prefixed file names to CurlFile class
        // since @ prefix is deprecated as of PHP 5.6
        if (class_exists('\CURLFile')) {
            foreach ($fields as $k => $v) {
                if (strpos($v, '@') === 0) {
                    $v = ltrim($v, '@');
                    $fields[$k] = new \CURLFile($v);
                }
            }
        }
        curl_setopt($cl, CURLOPT_POSTFIELDS, $fields);
        $content = curl_exec($cl);
        $status = curl_getinfo($cl);
        curl_close($cl);
        if (isset($status['http_code']) && $status['http_code'] == 200) {
            if ($data_type == 'json') {
                $content = json_decode($content ,true);
            }
            return $content;
        } else {
            return FALSE;
        }
    }
    
    /**
     * 设置缓存
     * @param unknown $key
     * @param unknown $value
     * @param number $expire
     */
    public function cache($key , $value = null , $expire = 3600){
        
        require_once dirname(__FILE__).'/FileCache/src/FileCache.php';
        $cache = new \fileCache();

        
        if ($value){
            $result = $cache->set($key, $value , $expire);
            return $result;
        }else{
            $value = $cache->get($key);
            return $value;
        }
        
    }
    
    public function cacheClear($key = null){
        
        require_once  dirname(__FILE__).'/FileCache/src/FileCache.php';
        $cache = new \fileCache();

        
        if ($key){
            $have = $cache->isHave($key);
            if ($have){
                $cache->delete($key);
            }
        }else{
            $cache->flush();
        }
    }

}