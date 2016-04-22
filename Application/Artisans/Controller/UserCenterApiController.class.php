<?php
namespace Artisans\Controller;
class UserCenterApiController extends CommonController {
      private $_get_token         = "http://localhost/user/auth";             //获取接口token地址
      private $_reg_url           = "http://localhost/user/register";         //用户注册接口
      private $_login_url         = "http://localhost/user/login";            //用户登录
      private $_userinfo_url      = "http://localhost/user/getByUid";         //用户信息
      private $_updateinfo_url    = "http://localhost/user/updateUser";       //更新用户信息
      private $_thirdlogin_url    = "http://localhost/user/login";            //第三方登录
      private $_update_passwd_url = "http://localhost/user/changePassword";   //修改密码
      private $_deleteinfo_url    = "http://localhost/user/delUser";          //删除用户
      private $_scrypt_pwd        = 'XXX';
      
      public function _initialize() {
             //获取token
             $this->_access_token = D('Token')->getUserCenterToken($this->_get_token);
      }
      
      public function regUserBy() {
             $post_string = I('request.pos_str');
             $exit_type   = 'json';
             $key         = $this->_scrypt_pwd;
             $model       = new \Artisans\Org\Scrypt($key);
             $de_string   = $model->decrypt_base64($post_string);
             parse_str($de_string, $post_data);
             
             $data['username']	        = $username    = $post_data['username'];
        		 $data['password']	        = $password	   = $post_data['password'];
        		 $data['source_from']       = $source_from = $post_data['source_from'];	//app,web,weixin
        		 $data['user_type']	        = $user_type	 = $post_data['user_type'];	  //other
        		 $data['third_part_key']		= $user_type;	                              //other
        		 $data['third_part_value']	= $username;	                              //other
        		 
        		 $reg_type = $post_data['type'];  //注册类型，100普通用户注册，200第三方用户注册
        		 if($reg_type == 200) {
        		      if(!($user_type && $source_from && $username)) {
        		            $this->returnJsonData(300);
        		      }
        		      $hash = $this->_thirdReg($data);
        		      wlog('/share/weixinLog/artisans/user_center_api/reduser_api_'.date('Ymd').'.log', $hash);
        		 }else{
        		      if(!($username && $password && $source_from && $user_type)) {
        		           $this->returnJsonData(300);
        		      }
        		      $hash = $this->_normalReg($data);
        		 }
        		 unset($data);
        		 if(is_array($hash) && $hash) {
        		      $code	= $hash['error_code'];
			            $json_data['uid']	= $hash['data']['uid'];
			            $json_data['username']	= $hash['data']['username'];
			            $this->returnJsonData($code,$json_data);
        		 }else{
        		      $this->returnJsonData(300);
        		 }
      }
}
