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
    	       
    	}
    	
    	/**
    	 * 生成API的token
    	 * @access	public
    	 * @param	string $prefix 请求应用接口名
    	 * @return	string|boolean
    	 */
    	public function apiAccessToken($prefix = '') {
    	       $token = create_uuid($prefix);
    	       
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
    	       
    	}
    	
    	/**
    	 * 获取算法token值
    	 * @param	string $param['string']	要加密的字符串
    	 * @param	string $param['key']		加密秘钥
    	 * @param	int	   $param['expires']	过期时间(单位：秒)
    	 * @return	string
    	 */
    	public function getSuanfaToken($param) {
    	  
    	}
    	
    	/**
    	 * 解密算法后的字符串
    	 * @param	string $param['md5_string'] 加密后的字符串
    	 * @param	string $param['key']		加密密钥
    	 * @return	string	
    	 */
    	public function checkSuanfaToken($param) {
    	  
    	}
    	
    	/**
    	 * 系统加密方法
    	 * @param string $data 要加密的字符串
    	 * @param string $key  加密密钥
    	 * @param int $expire  过期时间 (单位:秒)
    	 * @return string
    	 */
    	private function _think_ucenter_encrypt($data, $key, $expire = 0) {
    	  
    	}
    	
    	/**
    	 * 系统解密方法
    	 * @param string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
    	 * @param string $key  加密密钥
    	 * @return string
    	 */
    	private function _think_ucenter_decrypt($data, $key) {
    	        $key = md5($key);
    	}
}
