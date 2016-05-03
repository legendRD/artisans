<?php
namespace Artisans\Controller;
class ProActiveApiController extends CommonController {
      private $_access_token = false;
      private $_five58_string = '';  //算法字符串
      private $_secret_key    = '';  //算法密钥
      
      //检测token是否存在
      private function _checkToken($access_token) {
              $string	            = $this->_five58_string;
		  $data['md5_string']	= $access_token;
		  $data['key']		= $this->_secret_key;
		  $decode			= D('Token')->checkSuanfaToken($data);
		  if($decode && $string==$decode){
			$this->_access_token	= true;
		  }else{
			$this->_access_token	= false;
		  }
      }
      
      //获取密钥
      public function getAccessToken($param = null) {
             if(isset($param)){
			$post_data	= $param;
		 	$exit_type	= 'array';
		 }else{
			$post_data	= I('post.');
			$exit_type	= 'json';
		 }
		 $real_source     = trim($post_data['real_source']);       //算法字符串
		 
		 if(empty($real_source)) {
		       return $this->returnJsonData($exit_type,300);
		 }
		 if($real_source <> $this->_five58_string) {
		       return $this->returnJsonData($exit_type,10001);
		 }
		 
		 $data['string']	= $this->_five58_string; 
		 $data['key']	= $this->_secret_key;
		 $data['expires'] = 7200;
		 $access_token	= D('Token')->getSuanfaToken($data);
		 
		 $return_data['access_token']	= $access_token;
		 return $this->returnJsonData($exit_type, 200, $return_data);
      }
      
      //58绑定用户优惠券
      public function bindPhoneCode($param = null) {
             if(isset($param)){
			$post_data	= $param;
			$exit_type	= 'array';
		}else{
			$post_data	= I('post.');
			$exit_type	= 'json';
		}
		wlog('/share/weixinLog/artisans/58_phone.log', $post_data['phone']);
		$access_token = $post_data['access_token'];
		$this->_checkToken($access_token);
		if(!$this->_access_token) {
		      return $this->returnJsonData($exit_type, 10002);      //没有权限
		}
		$phone = $post_data['phone'];
		if(empty($phone)) {
		      return $this->returnJsonData($exit_type, 300);
		}
		if(!check_phone($phone)) {
		      return $this->returnJsonData($exit_type, 10003);      //手机号格式有误
		}
		$now_time = date('Y-m-d H:i:s');
		$rand_code = mt_rand('100000', '999999');
		$data['CouponsId'] = 1;
		$data['Phone']     = $phone;
		$data['CodeNum']   = $rand_code;
		$data['Source']    = 1;
		$data['IsUse']     = 0;
		$data['CreateTime']= $now_time;
		$where = array(
		      'Source'=>1,
		      'Phone'=>$phone
		);
      }
      
      public function returnJsonData($exit_type,$code,$data=array(),$msg=''){
    		switch($code){
    			case 200:
    				$hash['code']	= 200;
    				$hash['message']= 'success';
    				$hash['data']	= $data;
    				break;
    			case 300:
    				$hash['code']	= 300;
    				$hash['message']= 'Lack of parameter';
    				break;
    			case 10001:
    				$hash['code']	= 10001;
    				$hash['message']= 'Illegal parameter values';
    				break;
    			case 10002:
    				$hash['code']	= 10002;
    				$hash['message']= 'Permission denied';
    				break;
    			case 10003:
    				$hash['code']	= 10003;
    				$hash['message']= 'Mobile phone is wrong';
    				break;
    			case 10004:
    				$hash['code']	= 10004;
    				$hash['message']= 'The user has to receive';
    				break;
    			case 10005:
    				$hash['code']	= 10005;
    				$hash['message']= 'Coupons have been brought out';
    				break;
    			case 10006:
    				$hash['code']	= 10006;
    				$hash['message']= 'The activity have no start';
    				break;
    			case 10007:
    				$hash['code']	= 10007;
    				$hash['message']= 'The activity have end';
    				break;
    			case 10008:
    				$hash['code']	= 10008;
    				$hash['message']= 'The activity no exist';
    				break;
    			default:
    				$hash['code']	= 500;
    				$hash['message']= 'error !';
    				break;
    		}
    		
    		if($exit_type == 'json'){
    			if(I('get.parse')=='echo_info')	pp($hash);
    			echo json_encode($hash);
    			exit();
    		}else{
    			return $hash;
    		}
    		
    	}
}
