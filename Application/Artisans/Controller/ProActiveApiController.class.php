<?php
namespace Artisans\Controller;
class ProActiveApiController extends CommonController {
      private $_access_token = false;
      private $_five58_string = '';  //算法字符串
      private $_secret_key    = '';  //算法密钥
      
      //检测token是否存在
      private function _checkToken($access_token) {
        
      }
      
      //获取密钥
      public function getAccessToken($param = null) {
        
      }
      
      //58绑定用户优惠券
      public function bindPhoneCode($param = null) {
        
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
