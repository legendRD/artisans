<?php
namespace Artisans\Model;
use Think\Model;
class TokenModel extends Model {
      protected $autoCheckFields = false;
      
      /**
    	 * 获取用户中心token
    	 * @access	public
    	 * @param	string $url
    	 * @return	mixed
    	 */
    	public function getUserCenterToken($url) {
    	       if(!$url) {
    	           return false;
    	       }
    	       $post_data = json_encode(array('realm'=>'XXX'));
    	       $receive   = send_curl($url, $post_data);
    	       $parse_data= json_decode($receive, true);
    	       if(is_array($parse_data['data']) && $parse_data['data']['access_token']) {
    	             $token = $parse_data['data']['access_token'];
    	       }else{
    	             wlog('/share/weixinLog/artisans/user_center_api/user_center_token'.date('Ymd').'.log', $parse_data);
    	       }
    	       return $token;
    	}
    	
    	/**
    	 * 生成API的token
    	 * @access	public
    	 * @param	string $prefix 请求应用接口名
    	 * @return	string|boolean
    	 */
    	public function apiAccessToken($prefix = '') {
    	       $token  = create_uuid($prefix);
    	       $second = 7500;
    	       $start_time = time();
    	       $end_time = $start_time + $second;
    	       $data = array(
    	             'token'=>$token,
    	             'expires_in'=>$second,
    	             'expires_start'=>$start_time,
    	             'expires_end'=>$end_time
    	       );
    	       $id = M('api_token')->add($data);
    	       if($id) {
    	             return $token;
    	       }else{
    	             return false;
    	       }
    	}
    	
    	/**
    	 * 检测token是否有效
    	 * @access public
    	 * @param string $access_token token值
    	 * @return boolean
    	 */
    	public function checkAccessToken($access_token = '') {
    	       if(empty($access_token)) {
    	                return false;
    	       }
    	       $where = array('token'=>$access_token);
    	       $token_info = M('api_token')->where($where)->find();
    	       if($token_info) {
    	             if($token['expires_end'] < time()) {
    	                   return false;
    	             }else{
    	                   return true;
    	             }
    	       }else{
    	             return false;
    	       }
    	}
    	
    	/**
    	 * 获取算法token值
    	 * @param	string   $param['string']	要加密的字符串
    	 * @param	string   $param['key']		加密秘钥
    	 * @param	int	   $param['expires']	过期时间(单位：秒)
    	 * @return	string
    	 */
    	public function getSuanfaToken($param) {
    	       $string = $param['string'];
    	       $key    = $param['key'] ? $param['key'] : 'XXX';
    	       $expire = $param['expires'] ? $param['expires'] : 7200;
    	       $token = $this->_t_ucenter_encrypt($string, $key, $expire);
    	       return $token;
    	}
    	
    	/**
    	 * 解密算法后的字符串
    	 * @param	string $param['md5_string'] 加密后的字符串
    	 * @param	string $param['key']		加密密钥
    	 * @return	string	
    	 */
    	public function checkSuanfaToken($param) {
    	       $md5_string = $param['md5_string'];
    	       $key = $param['key'] ? $param['key'] : 'XXX';
    	       $string = $this->_t_ucenter_decrypt($md5_string, $key);
    	       return $string;
    	}
    	
    	/**
    	 * 系统加密方法
    	 * @param string $data 要加密的字符串
    	 * @param string $key  加密密钥
    	 * @param int $expire  过期时间 (单位:秒)
    	 * @return string
    	 */
    	private function _t_ucenter_encrypt($data, $key, $expire = 0) {
    	        $key = md5($key);
    	        $data = base64_encode($data);
    	        $x = 0;
    	        $l   = strlen($key);
    	        $len = strlen($data);
    	        $char = '';
    	        for($i = 0; $i < $len; $i++) {
    	              if($x == $l) {
    	                    $x = 0;
    	              }
    	              $char .= substr($key, $x, 1);
    	              $x++;
    	        }
    	        $str = sprintf('%010d', $expire ? $expire + time() : 0);
    	        for($i = 0; $i < $len; $i++) {
    	              $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)))%256);
    	        }
    	        return str_replace('=', '', base64_encode($str));
    	}
    	
    	/**
    	 * 系统解密方法
    	 * @param string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
    	 * @param string $key  加密密钥
    	 * @return string
    	 */
    	private function _t_ucenter_decrypt($data, $key) {
    	        $key  = md5($key);
    	        $x    = 0;
    	        $data = base64_decode($data);
    	        $expire = substr($data, 0, 10);
    	        $data = substr($data, 10);
    	        if($expire > 0 && $expire < time()) {
    	              return '';
    	        }
    	        $len = strlen($data);
    	        $l = strlen($key);
    	        $char = $str = '';
    	        for($i = 0; $i < $len; $i++) {
    	              if($x == $l) {
    	                    $x = 0;
    	              }
    	              $char .= substr($key, $x, 1);
    	              $x++;
    	        }
    	        for($i = 0; $i < $len; $i++) {
    	              if(ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
    	                    
    	              }else{
    	                    
    	              }
    	        }
    	        return base64_decode($str);
    	}
}
