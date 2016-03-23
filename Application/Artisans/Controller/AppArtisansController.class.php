<?php
namespace Artisans\Controller;
class AppArtisansController extends CommonController {
      //头像地址
      private $_headimg_upload_path = "/share/weixinImg/artisans/app/headImg/";
      private $_headimg_view_path   = "/img/weixinImg/artisans/app/headImg/";
      
      //设备地址
      private $_fun_upload_path	    = "/share/weixinImg/artisans/app/fun/";
      private $_fun_view_path	    = "/img/weixinImg/artisans/app/fun/";
      
      //支付完成之后请求地址
      private $_send_url = "http://localhost/paycenter/return_verify";
      private $_log_url  = "/share/weixinLog/artisans/app/";
      
      //日志开启状态
      private $_log_open_status = false;
      protected $info_log_url = "/share/weixinLog/artisans/app/info_log/";
      
      //app微信支付
      private $_appid		= '';
      private $_appkey		= '';
      private $_partnerkeyid	= '';
      private $_partnerkey	= '';
      
      //获取订单状态地址
      private $_getStatus 	= "http://localhost/order/list";
      
      //生成订单号地址
      private $_getTrade_url	= "http://localhost/order/addor";
      
      //支付接口
      private $_orderPay_url 	= "http://localhost/Paycenter/CreatePayInfo";
      
      //更改用户状态和XX
      private $_pay_log		= "/share/pay_log_url/webArtisans/";
      
      //长连接变为短连接
      private $_changeUrl	= "http://localhost/Qrcode/shorturl";
      
      //设备类型
      private $_fun_type	= array(1=>'手机', 2=>'平板', 3=>'笔记本', 4=>'其他');
      
      //读取配置信息的接口
      public function getConfig() {
            $this->wInfoLog('服务器配置信息，IP：'.get_ip());
            $this->wInfoLog($_REQUEST, '接收参数=>');
            $data = array();
            $config_info = M('app_config')->where(array('IsDelete'=>0))->order('ConfigId desc')->field('Codes code, Jsons content')->find();
            if($config_info) {
            	$data['code'] = $config_info['code'];
            	$json_arr     = explode(',', $config_info['content']);
            	foreach($json_arr as $value) {
            		$tmp = explode('@@', $value);
            		$data[$tmp[0]] = $tmp[1];
            	}
            	$this->returnJsonData(200, $data);
            }else{
            	$this->returnJsonData(404);
            }
      }
      
      //首页广告位
      public function getBanner() {
            $postData = I('request.');
            $this->wInfoLog('广告位，IP：'.get_ip());
            $this->wInfoLog($postData, '接收参数=>');
            
            $source = (int)$postData['type'];	//平台类型。1 微信，2 安卓， 3 IOS
            if(empty($source)) {
            	$this->returnJsonData(300);
            }
            
            $comm_model = D('Comm');
            $getbannerInfo = $comm_model->getbanner($source);
            if($getbannerInfo && is_array($getbannerInfo)) {
            	foreach($getbannerInfo as $val) {
            		$hash['title']    = (string)$val['Title'];
            		$hash['imgUrl']   = $val['ImgUrl'];
            		$hash['clickUrl'] = (string)$val['Url'];
            	}
            	$this->returnJsonData(200, $hash);
            }else{
            	$this->returnJsonData(404);
            }
      }
      
      //给用户发送验证码
      public function sendVerifyCode() {
            $postData	= I('request.');
	    $this->wInfoLog('发送验证码,IP:'.get_ip());
	    $this->wInfoLog($postData,'接收参数=>');
	    
	    $register_phone = trim($postData['phone']);
	    if(empty($register_phone)) {
	    	$this->returnJsonData(300);
	    }
	    $check_phone_status = check_phone($register_phone);
	    if(!($check_phone_status)) {
	    	$this->returnJsonData(1003);
	    }
	    
	    $code = mt_rand(1000, 9999);
	    $content = 'XXXXX验证码('.$code.')';
	    $sendVerify = D('Comm')->sendShortMsg($register_phone, $content, 'artisans_register');
	    if($sendVerify['SendMessageResult']['Resultcode'] == '00') {
	    	$cdate = date('Y-m-d H:i:s');
	    	$edate = date('Y-m-d H:i:s', time()+1800);
	    	
	    	$where = array('Phone'=>$register_phone, 'Source'=>0);
	    	M('cut_captcha')->where($where)->delete();
	    	
	    	$data = array(
	    		'Phone'=>$register_phone,
	    		'Captcha'=>$code,
	    		'CreaterTime'=>$cdate,
	    		'LoseTime'=>$edate,
	    		'Source'=>0
	    	);
	    	$id = M('cut_captcha')->add($data);
	    	if($id) {
	    		$this->returnJsonData(200);
	    	}
	    }
	    $this->returnJsonData(500);
      }
      
      //查看验证码
      public function checkVerifyCode() {
            $postData = I('request.');
            $this->wInfoLog('发送验证码,IP:'.get_ip());
	    $this->wInfoLog($postData,'接收参数=>');
	    
	    $register_phone = trim($postData['phone']);
	    $code	    = trim($postData['code']);
	    if(!($register_phone && $code)) {
	    	$this->returnJsonData(300);
	    }
	    $check_phone_status = check_phone($register_phone);
	    if(!($check_phone_status)) {
	    	$this->returnJsonData(1003);
	    }
	    
	    $where = array('Phone'=>$register_phone, 'Captcha'=>$code, 'Source'=>0);
	    $edate = M('cut_captcha')->where($where)->getField('LoseTime');
	    if($edate) {
	    	$second = time()-strtotime($edate);
	    	if($second > 0) {
	    		$this->returnJsonData(1004);
	    	}else{
	    		$this->returnJsonData(200);
	    	}
	    }
	    $this->returnJsonData(500);
      }
      
      /**
	 * 登陆注册功能
	 * @access	public
	 * @param	int $postData['phone'] 电话号
	 * @param	int $postData['code']  验证码
	 * @param	cid $postData['cid']   设备id
	 * @return	string
	 */
	 public function addUser() {
	        $postData	= I('request.');
		$this->wInfoLog('登陆接口,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');
		
		if(isset($postData['phone']) && isset($postData['code']) && isset($postData['login_time'])) {
			if(!check_phone($postData['phone'])) {
				$this->returnJsonData(1003);
			}
			
			$where['Phone']   = $postData['phone'];
			$where['Captcha'] = $postData['code'];
			$where['Source']  = 0;
			
			$codeStatus 	  = M('cut_captcha')->where($where)->find();
			
			if($codeStatus && $codeStatus['LoseTime']<date('Y-m-d H:i:s')) {
				$this->returnJsonData(1004);
			}
			if(!$codeStatus) {
				$this->returnJsonData(508);
			}
			if($codeStatus) {
				//用户注册
				$base_info_s = array(
					'source_from'=>'app',
					'user_type'=>'other',
					'type'=>200,
					'username'=>$postData['phone']
				);
				
				//注册用户中心
				$user_center_info = send_curl(C('ArtisansApi').'/UserCenterApi/loginUser', $base_info_s);
				$parse_data	  = json_decode($user_center_info, true);
				
				if($parse_data['code']!=200) {
					$this->returnJsonData(500);
				}
				if(isset($postData['cid'])) {
					$param = array(
						'cid'=>$postData['cid'],
						'uid'=>$parse_data['data']['uid'],
						'login_time'=>$postData['login_time']
					);
					$isBind = $this->uiBindCid($param);
					if($isBind['status']==200) {
						$data['user_id'] = $isBind['data']['user_id'];
						$data['cadate']  = $isBind['data']['cdate'];
					}
				}else{
					$data['user_id'] = $parse_data['data']['uid'];
					$data['cadate']  = $isBind['data']['cdate'];
				}
				$this->returnJsonData(200, $data);
			}
		}else{
			$this->returnJsonData(300);
		}
	 }
	 
	 //uid绑定cid 接口
	 public function uidBindCid($param=null) {
	       if($param) {
	       	  $postData = $param;
	       }else{
	       	  $postData=I('request.');
		  $this->wInfoLog('uid绑定cid的接口,IP:'.get_ip());
		  $this->wInfoLog($postData,'接收参数=>');
	       }
	       
	       if(isset($postData['cid']) && isset($postData['uid']) && isset($postData['login_time'])) {
	       	         $info = M('crt_uid_cid')->where(array('Uid'=>$postData['uid']))->select();
	       	         if($info) {
	       	         	$is_cid = M('crt_uid_cid')->where(array('Uid'=>$postData['uid'], 'Cid'=>$postData['cid']))->find();
	       	         	if(!$is_cid) {
	       	         		//新设备登陆
		       	         	$old_login_cid  = M('crt_uid_cid')->where(array('Uid'=>$postData['uid'], 'State'=>1))->getField('Cid');
		       	         	$old_change_cid = M('crt_uid_cid')->where(array('Uid'=>$postData['uid'], 'State'=>1))->save(array('State'=>0));
		       	         	
		       	         	//走极光推送
		       	         	$array['platform'] 	= $data['platform'] ? $data['platform'] : 'all';
		       	         	$array['audience'] 	= "{'registration_id':{$old_change_cid}}";
		       	         	$array['msg_content']   = '你的账号在另一台设备登陆';
		       	         	$array['title']		= '标题';
		       	         	$array['content_type']  = '类型';
		       	         	$array['extras']	= json_encode(array('type'=>'30', 'uid'=>$postData['uid'], 'code'=>(string)$postData['login_time']));
		       	         	
		       	         	$url			= C('JPUSH_API');
		       	         	list($t1, $t2)		= explode(' ', microtime());
		       	         	$start 			= (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
		       	         	file_put_contents('/share/weixinLog/artisans/jpush.txt', 'start:'.$start, FILE_APPEND);
		       	         	$ret		        = send_curl($url, $array);
		       	         	$result 		= json_decode($ret);
		       	         	$res  			= (array)$result;
		       	         	if(res['isok']) {
		       	         		list($t1, $t2) = explode(' ', microtime());
		       	         		$end = (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
		       	         		file_put_contents('/share/weixinLog/artisans/jpush.txt', 'end:'.$end, FILE_APPEND);
		       	         	}
		       	         	
		       	         	//为新设备添加绑定
		       	         	$add['Uid'] 	    = $postData['uid'];
		       	         	$add['Cid'] 	    = $postData['cid'];
		       	         	$add['CreaterTime'] = date('Y-m-d H:i:s');
		       	         	$add['State']	    = 1;
		       	         	$add['CidType']     = 0;
		       	         	$add['Code']        = $postData['login_time'];
		       	         	$res		    = M('crt_uid_cid')->add($add);
	       	         	}elseif($is_cid && $is_cid['State']!=1) {
		       	         	$old_login_cid   =  M('crt_uid_cid')->where(array('Uid'=>$postData['uid'],'State'=>1))->getField('Cid');
					$old_change_cid  =  M('crt_uid_cid')->where(array('Uid'=>$postData['uid'],'State'=>1))->save(array('State'=>0));
					
					// 走极光推送
					$array['platform'] 	= $data['platform'] ? $data['platform'] : 'all';
		       	         	$array['audience'] 	= "{'registration_id':{$old_change_cid}}";
		       	         	$array['msg_content']   = '你的账号在另一台设备登陆';
		       	         	$array['title']		= '标题';
		       	         	$array['content_type']  = '类型';
		       	         	$array['extras']	= json_encode(array('type'=>'30', 'uid'=>$postData['uid'], 'code'=>(string)$postData['login_time']));
		       	         	
		       	         	$url			= C('JPUSH_API');
		       	         	list($t1, $t2)		= explode(' ', microtime());
		       	         	$start 			= (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
		       	         	file_put_contents('/share/weixinLog/artisans/jpush.txt', 'start:'.$start, FILE_APPEND);
		       	         	$ret		        = send_curl($url, $array);
		       	         	$result 		= json_decode($ret);
		       	         	$res  			= (array)$result;
		       	         	if(res['isok']) {
		       	         		list($t1, $t2) = explode(' ', microtime());
		       	         		$end = (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
		       	         		file_put_contents('/share/weixinLog/artisans/jpush.txt', 'end:'.$end, FILE_APPEND);
		       	         	}
		       	         	
		       	         	//为新设备添加绑定
					$add['Uid']		=$postData['uid'];
					$add['Cid']		=$postData['cid'];
					$add['CreaterTime']	=date("Y-m-d H:i:s");
					$add['State']		=1;
					$add['CidType']		=0;
					$add['Code']		=$postData['login_time'];
					$res = M('crt_uid_cid')->where(array('Uid'=>$postData['uid'], 'Cid'=>$postData['cid']))->save(array('State'=>1));
	       	         	}
	       		}else{
	       		// 第一次设备绑定
			$add['Uid']		=$postData['uid'];
			$add['Cid']		=$postData['cid'];
			$add['CreaterTime']	=date("Y-m-d H:i:s");
			$add['State']		=1;
			$add['CidType']		=0;
			$add['Code']		=$postData['login_time'];
			$res=M('crt_uid_cid')->add($add);
	        	}
	 	}else{
	 		$return['status']=300;
			$return['msg']='缺少参数';
	 	}
	 	
	 	$return['status']=200;
		$return['msg']='success';
		$return['data']['user_id']=$postData['uid'];
		$return['data']['cdate']=date("Y-m-d H:i:s");
		
		if($param) {
			return $return;
		}else{
			json_return($return,$postData['test']);
		}
	 }
	 
	 //获取时间ID 接口
	 public function getTimeId($param=null) {
	        $postData=I('param.');
		$this->wInfoLog('获取时间Id的接口,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');

		$field[]='TimeId as timeid';
		$field[]='StartTime as time';
		$where['IsDelete']=0;
		$time=M('prd_servicetime')->field($field)->where($where)->select();
		$return['status']=200;
		$return['msg']='success';
		$return['data']=$time;
		json_return($return,$postData['test']);
	 }
	 
	 //查询用户信息
	 public function selectUser() {
	        $postData=I('param.');
		$this->wInfoLog('查询用户信息,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');
		
		if(isset($postData['user_id'])) {
			$info['uid'] = $postData['user_id'];
			$user_center_info = send_curl(C('ArtisansApi').'/UserCenterApi/getUserInfo', $info);
			$user = json_decode($user_center_info, 'true');
			if($user['code'] == 200) {
				$data['user_id']   = (int)$user['data']['uid'];
				$data['name']	   = (string)$user['data']['nickname'];
				$data['lastLogin'] = date("Y-m-d H:i:s", $user['data']['last_login_time']);
				$data['headImg']   = C("TMPL_PARSE_STRING")['__IMG_URL__'].$user['data']['avatar'];
				$data['cdate']	   = date("Y-m-d H:i:s", $user['data']['regdate']);
				$data['udate']	   = date("Y-m-d H:i:s");
				$data['phone']     = (string)$user['data']['other'];
				
				$return['status']  = 200;
				$return['msg']     = 'success';
				$return['data']    = $data;
			}
		}else{
			$return['status']=300;
			$return['msg']='缺少参数';
		}
		json_return($return, $postData['test']);
	 }
	 
	 //更新用户信息
	 public function updateUser() {
	        $postData	= I('request.');
		$this->wInfoLog('更新用户信息,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');
		
		if(isset($postData['user_id'])) {
			if($_FILES['img']) {
				$img_root_path = C("UPLOAD_WX_IMG");
				$save_path     = "./artisans/app/headImg/";
				$config        = array(
					 'maxSize'=>1048576,
					'rootPath'=>$img_root_path,
					'savePath'=>$save_path,
					'saveName'=>array('uniqid', ''),
					    'exts'=>array('jpg', 'gif', 'png', 'jpeg'),
					 'autoSub'=>true,
					 'subName'=>array('date', 'Ymd')
				);
				
				//实例化上传类
				$upload = new \Think\Upload($config);
				$info   = $upload->uploadOne($_FILES['img']);
				if($info) {
					$data['HeadImgUrl'] = C("VIEW_WX_IMG").trim($save_path, './').'/'.$info['savename'];
					$param['avatar']    = $data['HeadImgUrl'];
				}else{
					$return['status']   = 1005;
					$return['msg']      = $upload->getErrorMsg();
				}
			}
			$param['uid'] = $postData['user_id'];
			if(isset($postData['name'])) {
				$param['nickname'] = $postData['name'];
			}
			$user_center_info = send_curl(C("ArtisansApi").'/UserCenterApi/updateUserInfo', $param);
			$user = json_decode($user_center_info, true);
			$return['status'] = $user['code'];
			$return['msg']    = $user['message'];
		}else{
			$return['status'] = 300;
			$return['msg']='缺少参数';
		}
		json_return($return, $postData['test']);
	 }
	 
	 //获取产品列表
	 public function getServiceList() {
	        $postData=I('param.');
		$this->wInfoLog('产品列表,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');
		
		if(isset($postData['city']) && isset($postData['is_shelves'])) {
			$param['CityId']     = (int)$postData['city'];   //城市ID
			$param['PlatformId'] = 2;   			 //微信的平台ID
			
			if($postData['is_shelves']==200) {
				$param['IsShelves'] = 0;
			}
			
			if(is_numeric($postData['id'])) {
				$param['ProductId'] = $postData['id'];
				
				$Service = A('Api')->getProductInfo($param);
				if($Service['status']==200) {
					$data[] = $Service['data'];
				}
			}else{
				$param['Sorting'] = 1;      //排序方式 2为正序 1为倒
				if(is_numeric($postData['num'])) {
					$param['limit'] = $postData['num']; //分类
					$param['page']  = 1;		    //分类
				}
				$Service = A('Api')->getProductList($param);
				if($Service['status'] == 200) {
					$data = $Service['data'];
				}
			}
			if($data) {
				foreach($data as $key => $val) {
					$tmp['serviceUrl']  = "http://".$_SERVER['HTTP_HOST'].__APP__."/Artisans/Craft/proDetails/ProductId/".$val['ProductId']."/Plat/app/acity/".$postData['city'];
					$tmp['serviceName'] = (string)$val['title'];
					$tmp['serviceId']   = (int)$val['ProductId'];
					$tmp['txt']	    = (string)$val['Description'];
					$tmp['basePrice']   = $val['promotion']['endPrice'] ? (string)$val['promotion']['endPrice'] : (string)$val['Price'];
					$tmp['status']      = ($postData['is_shelves']==200 && isset($postData['is_shelves'])) ? 200 : 100;
					$tmp['discount']    = $val['promotion']['discount'] ? (string)$val['promotion']['discount'] : '0';
					$tmp['logoUrl']     = C('TMPL_PARSE_STRING')['__IMG_URL__'].$val['classImg'];
					$tmp['detailUrl']   = C('TMPL_PARSE_STRING')['__IMG_URL__'].$val['BannerImgUrl'];
					$hash[] 	    = $tmp;
				}
			}
			if($hash) {
				$return['status'] = 200;
				$return['msg']    = 'success';
				$return['data']   = $hash;
			}else{
				$return['status'] = 404;
				$return['msg']    = '没有数据';
			}
		}else{
			$return['status'] = 300;
			$return['msg']    = '缺少参数';
		}
		json_return($return, $postData['test']);
	 }
	 
	 //产品下面XXX
	 public function proUserinfo() {
	        $postData  = I('param.');
		$this->wInfoLog('产品下面XXX,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');
		
		if(isset($postData['id']) && isset($postData['time_id']) && isset($postData['date_s']) && $postData['date_s']>=date("Y-m-d") && isset($postData['city'])) {
			// 必传
			$param['ProductId']= $postData['id'];	    //产品Id
		        $param['Capacity'] = $postData['date_s'];   //日期
		        $param['TimeId']   = $postData['time_id'];  //时间Id
		        $param['City']     = $postData['city'];     //城市Id
		        
		        // 经玮度
		        if (isset($postData['lng'])  && isset($postData['lat'])) {
		        	$param['lng']      = $postData['lng'];       //经度
		        	$param['lat']      = $postData['lat'];       //玮度
		        }
		        
		        // 分页
		        if (isset($postData['currentPage'])  && isset($postData['pageSize'])) {
		       		$param['page']     = $postData['currentPage'] ;         //当前页
		       		$param['limit']    = $postData['pageSize'];       	//一页的数据量
		        }
		        
		        //排序
		        if(isset($postData['order'])) {
		        	if(isset($postData['order'] == 1)) {
		        		$param['goodRate'] = 'desc';
		        	}elseif($postData['order'] == 2) {
		        		$param['serviceNum'] = 'desc';
		        	}elseif($postData['order'] == 3) {
		        		$param['Distance'] == 'asc';
		        	}
		        }
		        
		        $stemList = A('Api')->getCraftsManList($param);
		        if($stemList['data']) {
		        	foreach($stemList['data'] as $k=>$v) {
		        		$tmp['name']	= $v['trueName'];
					$tmp['craft_id']= $v['CraftsmanId'];
					$tmp['photo']   = C('TMPL_PARSE_STRING')['__IMG_URL__'].$v['headImg'];
					$tmp['good']    = $v['goodRate'];
					$tmp['serviced']= $v['serviceNum'];
					$tmp['des']	= $v['description'];
					$tmp['distance']= $v['Distance'];
					foreach($v['productList'] as $ks=>$vs) {
						$tmp['skillArr'][] = C("TMPL_PARSE_STRING")['__IMG_URL__'].$vs['classImg'];
					}
					$hash[] = $tmp;
					unset($tmp);
		        	}
		        	if($hash) {
		        		$return['status'] = 200;
		        		$return['msg']	  = 'success';
		        		$return['data']   = $hash;
		        	}
		        }else{
		        	$return['status'] = 404;
		        	$return['msg']	  = '没有数据';
		        }
		}else{
			$return['status']=300;
			$return['msg']='缺少参数/参数格式不正确';
		}
		json_return($return, $postData['test']);
	 }
	 
	 //城市下面XXX
	 public function cityUserinfo() {
	        //必传参数  城市
		//可传参数  经纬度
		$postData	= I('request.');
		$this->wInfoLog('城市下面XXX,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');
		
		$city_name	= $postData['city'];
		$lat	= $postData['lat'];
		$lng	= $postData['lng'];
		if(empty($city_name)) {
			$this->returnJsonData(300);
		}
		
		$artisans_model = D('Artisans');
		$city_info      = $artisans_model->getCityInfo($city_name);
		$city_id	= $city_info['CityId'];
		if(empty($city_id)) {
			$this->returnJsonData(500);
		}
		
		$var['city_id']		= $city_id;
		$var['lat']		= $lat;
		$var['lng']		= $lng;
		$var['order_type']	= $postData['order'];
		$var['currpage']	= $postData['currentPage'];
		$var['page_size']	= $postData['pageSize'];
		$user_info		= $artisans_model->getCityUserInfo($var);
		if($user_info === false) {
			$this->returnJsonData(500);
		}
		$hash = $this->parseUserinfo($user_info);
		if($hash) {
			$this->returnJsonData(200, $hash);
		}
		$this->returnJsonData(404);
	 }
	 
	 //获取单个XXX信息
	 public function getOneInfo() {
	        $postData	= I('request.');
		$this->wInfoLog('单个XXX,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');
		
		$craft_id = $postData['userId'];
		if(empty($craft_id)) {
			$this->returnJsonData(300);
		}
		
		$userinfo = $artisans_model->getCraftInfo($craft_id);
		if($userinfo) {
			$city_info		= $artisans_model->cityInfo($userinfo['City']);
			
			$data['id']		= (int)$userinfo['CraftsmanId'];
			$data['name']		= (string)$userinfo['TrueName'];
			$data['photo']		= (string)$userinfo['HeadImgCdnUrl'];
			$data['school']		= (string)$userinfo['Source'];
			$data['area']		= (string)$city_info['CityName'];
			$data['good']		= (string)$userinfo['GoodRate'];
			$data['serviced']	= $userinfo['ServiceNum'];
			$data['des']		= (string)$userinfo['Description'];
			$data['skillArr']	= $artisans_model->getCraftProImg($craft_id);
			$data['time']		= $this->getUserTime($craft_id);	//时间点的XX
			
			$this->returnJsonData(200, $data);
		}else{
			$this->returnJsonData(404);
		}
	 }
	 
	 //获取时间点XX
	 public function getUserTime($craft_id) {
	        if(empty($craft_id)) {
	        	return false;
	        }
	        $now = time();
	        $week_arr = array("日", "一", "二", "三", "四", "五", "六");
	        for($i=0; $i<12; $i++) {
	        	$tmp = array();
	        	$tmp_time = $now+86400*$i;
	        	$tmp['date'] = date('Y-m-d', $tmp_time);
	        	$tmp['week'] = '星期'.$week_arr[date('w', $tmp_time)];
	        	
	        	$timePointInfo = $this->getTimeStatus($craft_id, $tmp['date']);
	        	if($timePointInfo) {
	        		$tmp['clock'] = $timePointInfo;
	        	}else{
	        		$tmp['clock'] = array();
	        	}
	        	$date_arr[] = $tmp;
	        }
	        return $date_arr;
	 }
	 
	 public function getTimeStatus($craft_id, $date) {
	        if(!($craft_id && $date)) {
	        	return false;
	        }
	        
	        $where = array(
			'Capacity'=>$date,
			'CraftsmanId'=>$craft_id,
			'NouseNum'=>array('gt',0)
		);
		$reservation_time = M('crt_capacity')->where($where)->field('Capacity as nowday, TimeId as time_id')->select();
		if($reservation_time) {
			$artisans_model = D("Artisans");
			foreach($reservation_time as $value) {
				$time_id_info	= $artisans_model->getTimeIdInfo($value['time_id']);
				$start_time     = strtotime($date.' '.$time_id_info['StartTime'].':00');
				$end_time	= strtotime($date.' '.$time_id_info['EndTime'].':00');
				$target 	= true;
				$i		= 0;
				while($target) {
					$tmp_time = $start_time + 3600 * $i;
					if($tmp_time > = $end_time) {
						$target = false;
					}else{
						$user_time[] = $tmp_time;
						$i++;
					}
				}
			}
		}
		$mintime = strtotime(date('Y-m-d 10:00:00', strtotime($date)));
		for($i=0;$i<12;$i++) {
			$tmp_time = $mintime + 3600*$i;
			//3小时之后
			if(in_array($tmp_time, $user_time) && ($tmp_time-time()>10800)) {
				$tmp['time']   = date('G', $tmp_time);
				$tmp['status'] = true;
			}else{
				$tmp['time']   = date('G', $tmp_time);
				$tmp['status'] = false;
			}
			$data[] = $tmp;
		}
		return $data;
	 }
	 
	 //添加设备
	 public function addEquipment() {
	        $postData	= I('request.');
		$this->wInfoLog('添加设备,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');
		
		if($_FIELS['img']) {
			$file = $_FILES['img'];
			$count = count($file['name']);
			if($count>4) {
				$this->returnJsonData(1002);
			}else{
				$img_root_path = C("UPLOAD_WX_IMG");
				$save_path     = './artisans/app/fun/';
				$config        = array(
					'maxSize'=>2097152, //设置附件上传大小 2M
					'rootPath'=>$img_root_path,
					'savePath'=>$save_path,
					'saveName'=>array('uniqid',''),
					'exts'=>array('jpg', 'gif', 'png', 'jpeg'),
					'autoSub'=>true,
					'subName'=>array('date','Ymd')
				);
				$upload = new \Think\Upload($config);	//实例化上传类
				$info   = $upload->upload();
				if($info) {
					foreach($info as $key=>$val) {
						$data['ImgUrl'.($key+1)] = C('VIEW_WX_IMG').trim($save_path,'./').'/'.$val['savename'];
					}
				}else{
					$this->returnJsonData(1005, array(), $upload->getErrorMsg());
				}
				$user_id = (int)$postData['user_id'];
				$type	 = $postData['type'];
				if(!($user_id && $type)) {
					$this->returnJsonData(300);
				}
				$fun_type	= $this->_fun_type;
				if(!$fun_type[$type]) {
					$this->returnJsonData(500);
				}
				$content = trim($postData['content']);
				
				$artisans_model      = D('Artisans');
				$data['UserId']      = $user_id;
				$data['Type']        = $type;
				$data['Description'] = $content;
				$data['CreateTime']  = date("Y-m-d H:i:s");
				$id  		     = M('cut_fun')->add($data);
				if($id) {
					$hash['fun_id'] = $id;
					$this->returnJsonData(200, $hash);
				}else{
					$this->returnJsonData(500);
				}
			}
		}
		$this->returnJsonData(300);
	 }
	 
	 //我的设备
	 public function myEquipment() {
	        $postData	= I('request.');
		$this->wInfoLog('我的设备,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');
		
		$user_id = trim($postData['user_id']);
		if(empty($user_id)) {
			$this->returnJsonData(300);
		}
		
		$artisans_model = D("Artisans");
		if(!is_numeric($user_id)) {
			$this->returnJsonData(500);
		}
		
		$fun_info = M("cut_fun")->where(array("UserId"=>$user_id))->order("CreateTime desc")->limit(21)->select();
		if($fun_info) {
			foreach($fun_info as $key=>$val) {
				$data[$key]['id']      = (int)$val['FunId'];
				$data[$key]['type']    = (string)$this->_fun_type[$val['Type']];
				$data[$key]['content'] = (string)$val['Description'];
				$data[$key]['img']     = $artisans_model->getImgUrl($val['ImgCdnUrl1'], $val['ImgUrl1']);
				$data[$key]['priseNum']= (int)$val['UpNum'];
				$data[$key]['cdate']   = (int)$val['CreateTime'];
				$data[$key]['url']     = (string)"http://".$_SERVER['HTTP_HOST']."/".C("TP_PROJECT_APP")."/index.php/Aritsans/AppArtisans/appViewH5?id=".$val['FunId'];
			}
			$this->returnJsonData(200, $data);
		}
		$this->returnJsonData(404);
	 }
	 
	 //设备H5地址
	 public function viewHfive() {
	       $postData = I('request.');
	       $this->wInfoLog('设备h5地址,IP:'.get_ip());
	       $this->wInfoLog($postData,'接收参数=>');
	       
	       $fun_id   = $postData['id'];
	       if($fun_id) {
	       	      $url = "http://".$_SERVER['HTTP_HOST']."/".C("TP_PROJECT_APP")."/index.php/Artisans/AppArtisans/appViewH5?id=".$fun_id;
	       	      $data['url'] = $url;
	       	      $this->returnJsonData(200, $data);
	       }else{
	       	      $this->returnJsonData(300);
	       }
	 }
	 
	 //设备H5页面
	 public function appViewH5() {
	       $postData	= I('request.');
	       $fun_id		= $postData['id'];
	       $fun_info        = M("cut_fun")->find($fun_id);
	       $user_info       = M("cut_customer")->find($fun_info['UserId']);
	       $artisans_model  = D("Artisans");
	       $data['pic']     = "http://".$artisans_model->getImgUrl($user_info['HeadCdnImgUrl'], $user_info['HeadImgUrl']);
	       $data['name']    = $user_info['NickName'];
	       $img1 		= $artisans_model->getImgUrl($fun_info['ImgCdnUrl1'], $fun_info['ImgUrl1']);
	       $img2		= $artisans_model->getImgUrl($fun_info['ImgCdnUrl2'], $fun_info['ImgUrl2']);
	       $img3		= $artisans_model->getImgUrl($fun_info['ImgCdnUrl3'], $fun_info['ImgUrl3']);
	       $img4		= $artisans_model->getImgUrl($fun_info['ImgCdnUrl4'], $fun_info['ImgUrl4']);
	       
	       if($img1) {
	       		$data['img'][] = 'http://'.$img1;
	       }
	       if($img2) {
	       		$data['img'][] = 'http://'.$img2;
	       }
	       if($img3) {
	       		$data['img'][] = 'http://'.$img3;
	       }
	       if($img4) {
	       		$data['img'][] = 'http://'.$img4;
	       }
	       if(!$data['img']) {
	       		$data['img'][] = array();
	       }
	       $data['txt'] = (string)$fun_info['Description'];
	       
	       $this->assign('data', $data);
	       
	       $this->display('viewHfive');
	 }
	 
	 //成功跳转页
	 public function success() {
	       $postData = I('request.');
	       post_http($this->_send_url,$postData);
	       $this->display();
	 }
	 
	 //朋友圈
	 public function friendCircle() {
	        $postData		= I('request.');
		$this->wInfoLog('fun朋友圈,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');
		
		$currentPage	= $postData['currentPage']>0? (int)$postData['currentPage']:1;
		$pageSize	= $postData['pageSize']   >0? (int)$postData['pageSize']   :5;
		if(!($currentPage && $pageSize)) {
			$this->returnJsonData(300);
		}
		
		$count = M('cut_fun')->count();
		if($count > 0) {
			$totalPage = ceil($count/$pageSize);
			$totalPage = $totalPage > 0 ? $totalpage : 1;
			if($currentPage < = $totalPage) {
				$info = M("cut_fun")->order("CreateTime desc")->limit(($currentPage-1)*$pageSize, $pageSize)->select();
				if($info) {
					$artisans_model = D("Artisans");
					foreach($info as $key=>$val) {
						$user_info	       = $artisans_model->getUser2($val['UserId']);
						$head_img	       = $user_info['headImg'];
						$data[$key]['id']      = $val['FunId'];
						$data[$key]['type']    = (string)$this->_fun_type[$val['Type']];
						$data[$key]['content'] = (string)$val['Description'];
						$data[$key]['pic']     = (string)$head_img;
						$data[$key]['name']    = (string)$user_info['name'];
						$data[$key]['img']     = array();
						
						$img1	= $artisans_model->getImgUrl($val['ImgCdnUrl1'],$val['ImgUrl1']);
						$img2	= $artisans_model->getImgUrl($val['ImgCdnUrl2'],$val['ImgUrl2']);
						$img3	= $artisans_model->getImgUrl($val['ImgCdnUrl3'],$val['ImgUrl3']);
						$img4	= $artisans_model->getImgUrl($val['ImgCdnUrl4'],$val['ImgUrl4']);
						
						if($img1) {
							$data[$key]['img'][] = $img1;
						}
						if($img2) {
							$data[$key]['img'][] = $img2;
						}
						if($img3) {
							$data[$key]['img'][] = $img3;
						}
						if($img4) {
							$data[$key]['img'][] = $img4;
						}
						
						$data[$key]['priseNum'] = (int)$val['UpNum'];
						$data[$key]['cdate']    = (string)$val['CreateTime'];
					}
					$this->returnJsonData(200, $data);
				}
			}
		}
		$this->returnJsonData(404);
	 }
	 
	 //产品详情页
	 public function productDetails() {
	        $postData	= I('request.');
		$this->wInfoLog('产品详情页,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');
		
		$pro_id	= $postData['id'];
		if($pro_id) {
			$data['url']	= "http://".I('server.HTTP_HOST')."/".C("TP_PROJECT_WEIXIN")."/index.php/Craft/proDetails/moduleId/".$pro_id."/source/100";
			$this->returnJsonData(200, $data);
		}else{
			$this->returnJsonData(300);
		}
	 }
	 
	 //创建订单
	 public function createOrderInfo() {
	        $this->_log_open_status = true;
		$postData		= I('request.');
		$this->wInfoLog('创建订单,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');
		
		$data['user_id']	= $user_id	= $postData['user_id'];   //用户id
		$data['craft_id']	= $craft_id	= $postData['userId'];    //XXXid
		$data['pro_id']		= $pro_id	= $postData['serviceId']; //产品id
		$data['name']		= $name		= $postData['name'];
		$data['phone']		= $phone	= $postData['phone'];
		$data['address']	= $address	= $postData['address'];
		$data['city_id']	= $city_id 	= $postData['city_id'];
		$data['lat']		= $lat		= $postData['lat'];
		$data['lng']		= $lng		= $postData['lng'];
		$data['order_date']	= $order_date	= $postData['date_s'];    //日期
		$data['order_time_id']	= $order_time_id= $postData['time_id'];   //时间id
		$data['address_id']	= 0;
		$data['for_who']	= 100;
		$data['wish']		= '';
		$data['pay_process']	= 1; //支付流程：1正常支付，2客服引导，3客服专家在线，4.距离大于40公里支付
		
		//3 安卓 4 IOS
		if($postData['source_from'] == 3) {
			$data['source_from'] = 300;
		}elseif($postData['source_from'] == 4) {
			$data['source_from'] = 400;
		}else{
			$this->returnJsonData(1008);
		}
		
		if($postData['pay_way'] == 100) {
			$data['pay_way'] = 3;
		}elseif($postData['pay_way'] == 200) {
			$data['pay_way'] = 4;
		}else{
			$this->returnJsonData(1009);
		}
		
		if(!($user_id && $craft_id && $pro_id && $city_id && $name && $phone && $address && $lat && $lng && $order_date && $order_time_id)) {
			$this->returnJsonData(300);	
		}
		
		$create_info	= A('OrderApi')->createOrder($data);
		if($create_info['code'] == 200 && $create_info['data']) {
			$hash['id'] = $create_info['data']['orderid'];
			$hash['out_trade_no'] = $create_info['data']['vmallorderid'];
			$this->returnJsonData(200, $hash);
		}else{
			$this->returnJsonData(1005,array(),$create_info['message']);
		}
	 }
	 
	 //获取支付链接
	 public function appOrderPay() {
	       
	 }
	 
	 //查询单个订单信息
	 public function selectOrderInfo() {
	       
	 }
	 
	 //查询多个订单信息
	 public function checkOrderInfo() {
	       
	 }
	 
	 //取消订单
	 public function cancleOrder() {
	       
	 }
	 
	 //XXX点评数据
	 public function checkEvaluation() {
	       
	 }
	 
	 //提交点评
	 public function createComments() {
	       
	 }
	 
	 //首单减5
	 public function reduceFiveyuan() {
	       
	 }
	 
	 //获取app版本
	 public function getAppVersion() {
	       
	 }
	 
	 //插入微信支付token
	 public function insAppPaytoken() {
	       
	 }
	 
	 //获取签名
	 public function get_sign_name() {
	       
	 }
	 
	 //日志
	 private function wInfoLog($data, $prefix='') {
	       
	 }
	 
	 public function upload_test() {
	       
	 }
	 
	 //获取订单状态
	 public function getOrderStatus($status) {
	       
	 }
	 
	 //解析XXX信息
	 public function parseUserinfo($userinfo) {
	       
	 }
	 
	 //返回数据
	 public function returnJsonData($num, $data=array(), $msg='') {
	       
	 }
	 
	 private function echoInfo($info, $parse='') {
	       
	 }
	 
	 //更新支付方式接口
	 public function updatePayWay() {
	       
	 }
	 
	 //获取产品价格
	 public function getProPrice() {
	       
	 }
}
