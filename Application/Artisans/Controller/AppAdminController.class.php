<?php
namespace Artisans\Controller;
class AppAdminController extends CommonController {

	//日志开启状态
	private $_log_open_status = true;
	private $_info_log_url    = '/share/weixinLog/artisans/app/info_log';
	private $_weekId_s	      = array(1=>'每周一', 2=>'每周二', 3=>'每周三', 4=>'每周四', 5=>'每周五', 6=>'每周六', 7=>'每周日');

	private function _echoInfo($info, $parse='') {
		if($parse == 'echo_info') {
			pp($info);
		}
	}

	//返回数据
	public function returnJsonData($num, $data = array(), $msg = '') {
		switch ($num) {
			case 300:
			    $hash['status'] = 300;
				$hash['msg']    = 'noparam';
				break;
			case 500:
				$hash['status'] = 500;
				$hash['msg']    = 'fail';
				break;
			case 200:
				$hash['status'] = 200;
				$hash['msg']    = 'success';
				$hash['data']   = $data;
				break;
			case 501:
				$hash['status'] = 501;
				$hash['msg']    = '手机号已被注册';
				break;
			case 502:
				$hash['status'] = 502;
				$hash['msg']    = '发送验证码失败';
				break;
			case 503:
				$hash['status']	= 503;
				$hash['msg']	= '身份证号有误';
				break;
			case 504:
				$hash['status']	= 504;
				$hash['msg']	= '身份证号已被注册';
				break;
			case 505:
				$hash['status']	= 505;
				$hash['msg']	= '邮箱格式不正确';
				break;
			case 506:
				$hash['status']	= 506;
				$hash['msg']	= '邮箱已被注册';
				break;
			case 507:
				$hash['status']	= 507;
				$hash['msg']	= '用户不存在';
				break;
			case 508:
				$hash['status']	= 508;
				$hash['msg']	= '验证码错误';
				break;
			case 1003:
				$hash['status']	= 1003;
				$hash['msg']	= '手机号错误';
				break;
			case 1004:
				$hash['status']	= 1004;
				$hash['msg']	= '验证码超时';
				break;
			case 1005:
				$hash['status']	= 1005;
				$hash['msg']	= $msg;
				break;
		}
		$this->_echoInfo($hash, $_GET['parse']);	//显示信息
		return json_encode($hash);
	}

	//用户登录
	public function usrLogin() {
		$postData = I('request.');
		$username = trim($postData['phone']);
		if(!check_phone($username)) {
			$this->returnJsonData(1003);
		}
		$passwd = md5($postData['passwd']); //加密
		$cid    = $postData['cid'];
		$code   = $postData['code'];
		if(!$cid || !$code) {
			$this->returnJsonData(300);
		}
		$where = array(
			'IsDelete' => 0,
			'Phone'    => $username,
			'Password' => $passwd
		);
		$user_info = M('crt_craftsmaninfo')->where($where)->find();
		if($user_info['CraftsmanId']) {
			$data['user_id']	= (int)$user_info['CraftsmanId'];
			$data['head_img']	= I('server.HTTP_HOST').$user_info['HeadImgUrl'];
			$data['real_name']	= (string)$user_info['TrueName'];
			$data['is_audit']	= $user_info['IsCheck']? 100:200;

			unset($where);
			$where = array(
				'CraftsmanId' => $data['user_id'], 
				'Cid'		  => $cid
			);
			$id = M('crt_craftsman_cid')->where($where)->find();
			$on = M('crt_craftsman_cid')->where(array('CraftsmanId'=>$data['user_id'], 'State'=>1))->find();
			if($on && $on['Cid'] != $cid) {
				$registration_id      = $on['Cid'];

				$array['platform']    = $data['platform'] ?$data['platform'] :'all';
				$array['audience']    = "{'registration_id':['$registration_id']}";
				$array['msg_content'] = '你的账号在另一台设备登陆';
				$array['title']       = '标题';
				$array['content_type']= '类型';
				$array['extras']      = json_encode(array('type'=>'30', 'uid'=>$data['user_id'], 'code'=>(string)$on['Code']));
				$url = C('JPUSH_API');
				list($t1, $t2) = explode(' ', microtime());
				$start = (float)sprintf('%.0f', (floatval($t1)+floatval($t2))*1000);
				file_put_contents('/share/weixinLog/artisans/a.txt', 'start:'.$start, FILE_APPEND);
				$ret = send_curl($url, $array);
				$result = json_decode($ret);
				$res = (array)$result;
				if($res['isok']) {
					list($t1, $t2) = explode(' ', microtime());
					$end = (float)sprintf('%.0f', (floatval($t1)+floatval($2))*1000);
					file_put_contents('/share/weixinLog/artisans/a.txt', 'end:'.$end, FILE_APPEND);
				}
				$ret = D('Jpushaa')->jpushpush($array);
				$whe['CraftsmanId'] = $data['user_id'];
				$whe['Cid']         = $on['Cid'];
				$arr['State']       = 0;
				M('crt_craftsman_cid')->where($whe)->save($arr);
			}
			if($id) {
				$arr['State'] = 1;
				$arr['Code']  = $code;
				M('crt_craftsman_cid')->where($where)->save($arr);
			}else{
				$time = date('Y-m-d H:i:s');
				$cdata = array(
					'CraftsmanId' => $data['user_id'],
					'Cid'         => $cid,
					'State'		  => 1,
					'CreaterTime' => $time,
					'CidType'     => 0,
					'Code'		  => $code
				);
				M('crt_craftsman_cid')->add($cdata);
			}
			$this->returnJsonData(200, $data);
		}else{
			$this->returnJsonData(500);
		}
 	}

 	//更改设备登陆状态
 	public function updateCState() {
 		$postData	= I('request.');
		$cid        = $postData['cid'];
		$user_id    = $postData['user_id'];
		$code       = $postData['code'];
		if(!$user_id || !$cid || !$code) {
			$this->returnJsonData(300);
		}
		$on = M('crt_craftsman_cid')->where(array('CraftsmanId'=>$user_id, 'State'=>'1'))->find();
		if($on) {
			$registration_id      = $on['Cid'];

			$data['platform']     = $data['platform'] ? $data['platform'] : 'all';
			$data['audience']     = "{'registration_id':['$registration_id']}";
			$data['msg_content']  = '你的账号在另一台设备登录';
	        $data['title']        = '标题';
	        $data['content_type'] = '类型';
	        $data['extras']		  = json_encode(array('type'=>'30', 'uid'=>$data['user_id'], 'code'=>(string)$on['Code']));

	        $url 				  = C('JPUSH_API');
	        $ret 				  = send_curl($url, $data);
	        $ret                  = D('Jpushaa')->jpushpush($data);

	        $whe['CraftsmanId'] = $user_id;
			$whe['Cid'] 	    = $on['Cid'];
			$arr['State'] = 0;
			M('crt_craftsman_cid')->where($whe)->save($arr);
		}
		$isse = M('crt_craftsman_cid')->where(array('CraftsmanId' => $user_id, 'Cid' => $cid))->find();
		if($isse) {
			$where['Cid']  = $cid;
			$save['State'] = 1;
			$save['Code']  = $code;
			M('crt_craftsman_cid')->where($where)->save($save);
		}else{
			$add['CraftsmanId'] = $user_id;
			$add['Cid'] 		= $cid;
			$add['Code'] 		= $code;
			$add['CreaterTime'] = date("Y-m-d H:i:s");
			$add['CidType'] 	= 0;
			$add['State'] 		= 1;
			M('crt_craftsman_cid')->add($add);
		}
		$this->returnJsonData(200);
 	}

 	private function _sendShortMsg($phone, $verify_msg, $source = 'artisans_register') {
 		$send_status = D('Comm')->_sendShortMsg($phone, $verify_msg, $source);
 		if($send_status['SendMessageResult']['Resultcode'] == '00') {
 			return true;
 		}else{
 			return false;
 		}
 	}

 	private function _insertVerify($phone, $verify_code, $verify_msg, $source = 'artisans_register') {
 		$send_msg = $this->_sendShortMsg($phone, $verify_msg, $source);
 		if($send_msg) {
 			$cdate	= date('Y-m-d H:i:s');
			$edate	= date('Y-m-d H:i:s',strtotime('30 minute'));
			$where	= array('Phone'=>$phone,'Source'=>1);
			M('cut_captcha')->where($where)->delete();
			$data = array(
					'Phone'=>$phone,
					'Captcha'=>$verify_code,
					'CreaterTime'=>$cdate,
					'LoseTime'=>$edate,
					'Source'=>1
			);
			$id = M('cut_captcha')->add($data);
			if($id) {
				return true;
			}
 		}
 		return false;
 	}

 	//发送验证码 验证手机号是否注册过
 	public function regPhone() {
 		$postData	= I('request.');
		$phone		= $postData['phone'];
		if(empty($phone)) {
			$this->returnJsonData(300);
		}
		if(!check_phone($phone)) {
			$this->returnJsonData(1003);
		}
		//判断手机号是否注册过
		$user_id = M('crt_craftsmaninfo')->where(array('Phone'=>$phone))->getField('CraftsmanId');
		if($user_id) {
			$this->returnJsonData(501);
		}

		$verify_code = mt_rand(1000, 9999);
		$verify_msg  = 'XXXXX验证码（'.$verify_code.'）';

		//发送验证码
		$send_msg    = $this->_insertVerify($phone, $verify_code, $verify_msg, 'regPhone_app_admin');
		if($send_msg) {
			$this->returnJsonData(200);
		}else{
			$this->returnJsonData(500);
		}
 	}

 	//验证身份证号是否有效 注册
 	public function regIdcard() {
 		$postData	= I('request.');
		$id_card	= $postData['id_card'];
		if(empty($id_card))	{
			$this->returnJsonData(300);
		}
		if(!is_idcard($id_card)) {
			$this->returnJsonData(503);
		}
		$user_id	= M('crt_craftsmaninfo')->where(array('IdCard'=>$id_card))->getField('CraftsmanId');
		if($user_id) {
			$this->returnJsonData(504);
		}else{
			$this->returnJsonData(200);
		}
 	}

 	//验证邮箱 是否注册
	public function regEmail(){
		$postData	= I('request.');
		$email		= $postData['email'];
		if(empty($email)) {
			$this->returnJsonData(300);
		}
		if(!check_email($email)) {
			$this->returnJsonData(505);
		}
		$user_id	= M('crt_craftsmaninfo')->where(array('Email'=>$email))->getField('CraftsmanId');
		if($user_id) {
			$this->returnJsonData(506);
		}else{
			$this->returnJsonData(200);
		}
	}
	
	//检查数据唯一性
	public function _checkInfo($content, $type) {
		if(empty($content)) {
			return false;
		}
		$user_id = false;
		switch($type) {
			case 'email':
				$user_id = M('crt_craftsmaninfo')->where(array('Email'=>$content))->getField('CraftsmanId');
				break;
			case 'phone':
				$user_id = M('crt_craftsmaninfo')->where(array('Phone'=>$content))->getField('CraftsmanId');
				break;
			case 'id_card':
				$user_id = M('crt_craftsmaninfo')->where(array('IdCard'=>$content))->getField('CraftsmanId');
				break;
			case 'username':
				$user_id = M('crt_craftsmaninfo')->where(array('UserName'=>$content))->getField('CraftsmanId');
				break;
		}
		return $user_id;
	}
	
	//个人资料
	public function getBaseInfo() {
		$postData = I('request.');
		$user_id  = $postData['user_id'];
		if(empty($user_id)) {
			$this->returnJsonData(300);
		}
		$user_info = M('crt_craftsmaninfo')
			     ->where("CraftsmanId=%d", $user_id)
			     ->field('CraftsmanId, UserName, TrueName, IdCard, HeadImgCdnUrl, HeadImgUrl, Description, Lat, Lng, Email, Phone, Address, City, Bank, BankCard, CardName, BankProvince, BankCity')
			     ->find();
		if($user_info) {
			$data['user_id']	= (int)$user_info['CraftsmanId'];
			$data['user_name']	= (string)$user_info['UserName'];
			$data['name']		= (string)$user_info['TrueName'];
			$data['id_card']	= (string)$user_info['IdCard'];
			$data['description']    = (string)$user_info['Description'];
			$data['email']		= (string)$user_info['Email'];
			$data['phone']		= (string)$user_info['Phone'];
			$data['address']	= (string)$user_info['Address'];
			$data['bankname']	= (string)$user_info['Bank'];
			$data['bankcard']	= (string)$user_info['BankCard'];
			$data['account_name']	= (string)$user_info['CardName'];
			$data['bankprovince']	= (string)$user_info['BankProvince'];
			$data['bankcity']	= (string)$user_info['BankCity'];
			$data['photo']		= I('server.HTTP_HOST').$user_info['HeadImgUrl'];
			$data['lat']		= floatval($user_info['Lat']);
			$data['lng']		= floatval($user_info['Lng']);
			if($user_info['City']) {
				$city_info = M()
					     ->table('sys_city sc')
					     ->join('left join sys_province sp on sp.ProvinceId = sc.ProvinceId')
					     ->where(array('CityId'=>$user_info['City']))
					     ->field("CityName city_name, ProvinceName p_name")
					     ->find();
				$data['city_id']   = (int)$user_info['City'];
				$data['city_name'] = (string)$city_info['city_name'];
				$province_name	   = (string)$city_info['p_name'];
			}else{
				$data['city_id']	= '';
				$data['city_name']	= '';
				$province_name		= '';
			}
			//获取服务城区
			$district_arr		= D('Admin')->getDistrict($user_id);
			$data['service_district'] = array();
			forach((array)$district_arr as $value) {
				$tmp['content']   = $province_name.'-'.$data['city_name'].'-'.$value['DistrictName'];
				$tmp['district_id'] = $value['DistrictId'];
				$data['service_district'][] = $tmp;
			}
			unset($tmp);
			$this->returnJsonData(200, $data);
		}
		$this->returnJsonData(500);
	}
	
	//修改手机号
	public function instPhone() {
		$postData	= I('request.');
		$phone		= $postData['phone'];
		$verify_code    = $postData['verify_code'];
		$user_id	= $postData['user_id'];
		if(!($phone && $user_id && $verify_code)) {
			$this->returnJsonData(300);
		}
		if(!check_phone($phone)) {
			$this->returnJsonData(1003);
		}
		
		//检查验证码
		$timeout	= $this->_checkVerify($phone,$verify_code);
		if($timeout === 'timeout') {
			$this->returnJsonData(1004);
		}elseif($timeout) {
			$data = array(
				'CreaterTime'=>date('Y-m-d H:i:s'),
				'Phone'=>$phone
			);
			$id   = M('crt_craftsmaninfo')->where(array('CraftsmanId'=>$user_id))->save($data);
			if($id) {
				$this->returnJsonData(200);
			}
		}else{
			$this->returnJsonData(508);
		}
	}
	
	//注册接口 （各个信息必须非空）
	public function regUser() {
		$postData = I('request.');
		if($_FILES['img']) {
			//判断有没有图片
			$file        = $_FILES['img'];
			$count       = count($file['name']);
			$phone 	     = trim($postData['phone'])
			$verify_code = trim($postData['verify_code']);
			$name        = trim($postData['name']);
			$id_card     = trim($postData['id_card']);
			$email       = trim($postData['email']);
			$passwd      = $postData['passwd'];
			$address     = $postData['address'];
			$lat 	     = $postData['lat'];
			$lng         = $postData['lng'];
			$city        = $postData['city'];
			$district_arr= $postData['district'];
			
			if(!($phone && $verify_code && $passwd && $name && $id_card && $email && $address && $lat && $lng && $city && $district_arr || $count<3)) {
				$this->returnJsonData(300);
			}
			if(!check_phone($phone)) {
				$this->returnJsonData(1003);
			}
			if(!is_idcard($id_card)) {
				$this->returnJsonData(503);
			}
			if(!check_email($email)) {
				$this->returnJsonData(505);
			}
			
			//检查数据一致性
			if($this->_checkInfo($email,'email')) {
				$this->returnJsonData(506);
			}
			if($this->_checkInfo($phone,'phone')) {
				$this->returnJsonData(501);
			}
			if($this->_checkInfo($id_card,'id_card')) {
				$this->returnJsonData(504);
			}
			
			//检查验证码
			$timeout = $this->_checkVerify($phone, $verify_code);
			if($timeout === 'timeout') {
				$this->returnJsonData(1004);
			}else{
				$this->returnJsonData(500);
			}
			$data = array();
			$data['State']		= 0;
			$data['IsDelete']	= 0;
			$data['UserName']	= '';
			$data['Password']	= md5($passwd);
			$data['TrueName']	= $name;
			$data['IdCard']		= $id_card;
			$data['Source']		= 1;	//暂定  目前设为1代表app端
			$data['Lat']		= $lat;
			$data['Lng']		= $lng;
			$data['Email']		= $email;
			$data['Address']	= $address;
			$data['City']		= $city;
			$data['CreaterTime']	= date('Y-m-d H:i:s');
			$data['Phone']		= $phone;
			
			$img_root_path	= C('UPLOAD_WX_IMG');
			$save_path	= './artisans/app/headImg/';
			$config = array(
					'maxSize'=>2097152, //设置附件上传大小 2M
					'rootPath'=>$img_root_path,
					'savePath'=>$save_path,
					'saveName'=>array('uniqid',''),
					'exts'=>array('jpg', 'gif', 'png', 'jpeg'),
					'autoSub'=>true,
					'subName'=>array('date','Ymd'),
			);
			$upload = new \Think\Upload($config);
			$info   =   $upload->upload();
			if($info) {
				foreach($info as $key=>$value) {
					if($key == 0) {
						$photo      = C('VIEW_WX_IMG').trim($save_path, './').'/'.$val['savename'];
					}
					if($key == 1) {
						$id_card_p1 = C('VIEW_WX_IMG').trim($save_path, './').'/'.$val['savename'];
					}
					if($key == 2) {
						$id_card_p2 = C('VIEW_WX_IMG').trim($save_path, './').'/'.$val['savename'];
					}
				}
			}else{
				$this->returnJsonData(1005, array(), $upload->getErrorMsg());
			}
			$data['IdCardImg1']	= $id_card_p1;
			$data['IdCardImg2']	= $id_card_p2;
			$data['HeadImgUrl']	= $photo;
			$data['IsCheck']	= 1;
			$id	= M('crt_craftsmaninfo')->add($data);
			if($id) {
				foreach($district_arr as $district_id) {
					$tmp['CreaterTime']	= $data['CreaterTime'];
					$tmp['DistrictId']	= $district_id;
					$tmp['CraftsmanId']	= $id;
					$hash[]	= $tmp;
				}
				unset($tmp);
				if($hash) {
					M('crt_craftsman_district')->addAll($hash);
				}
				$this->returnJsonData(200);
			}
			$this->returnJsonData(500);
		}
		$this->returnJsonData(300);
	}
	
	//发送验证码，可用于修改密码、手机号、注册
	public function sendCodeRepasswd() {
		$postData	= I('request.');
		$phone		= $postData['phone'];
		$action		= $postData['action'];
		if(!($phone && $action)) {
			$this->returnJsonData(300);
		}
		if(!check_phone($phone)) {
			$this->returnJsonData(1003);
		}
		
		if($action == 'passwd') {
			$log_name = 'app_admin_update_repasswd';
			$user_id  = $this->_checkInfo($phone, 'phone');
			if(!$user_id) {
				$this->returnJsonData(507);
			}
		}elseif($action == 'register') {
			$log_name = 'app_admin_register';
			$user_id  = $this->_checkInfo($phone, 'phone');
			if($user_id) {
				$this->returnJsonData(501);
			}
		}elseif($action == 'phone') {
			$log_name = 'app_admin_update_phone';
		}
		
		$verify_code	= mt_rand(1000,9999);
		$verify_msg	= 'XXXXX验证码('.$verify_code.')';
		$send_status	= $this->_insertVerify($phone,$verify_code,$verify_msg,$log_name);
		if($send_status) {
			$this->returnJsonData(200);
		}else{
			$this->returnJsonData(500);
		}
	}
	
	//找回密码   校验验证码
	public function checkCodeRepasswd() {
		$postData	= I('request.');
		$phone		= $postData['phone'];
		$verify_code    = $postData['verify_code'];
		$action		= $postData['action'];
		if(!($phone && $verify_code)) {
			$this->returnJsonData(300);
		}
		if(!check_phone($phone)) {
			$this->returnJsonData(1003);
		}
		
		if($action == 'passwd') {
			$user_id	= $this->_checkInfo($phone,'phone');
			if(!$user_id) {
				$this->returnJsonData(507);
			}
		}elseif($action == 'register') {
			$user_id	= $this->_checkInfo($phone,'phone');
			if($user_id) {
				$this->returnJsonData(501);
			}
		}elseif($action == 'phone') {
		
		}
		
		$timeout	= $this->_checkVerify($phone,$verify_code);
		if($timeout === 'timeout') {
			$this->returnJsonData(1004);
		}elseif($timeout) {
			$this->returnJsonData(200);
		}else{
			$this->returnJsonData(508);
		}
	}
	
	//重设密码
	public function instPasswd() {
		$postData	= I('request.');
		$phone		= $postData['phone'];
		$passwd		= md5($postData['passwd']);
		$time		= date('Y-m-d H:i:s');
		if(!($phone && $passwd)) {
			$this->returnJsonData(300);
		}
		if(!check_phone($phone)) {
			$this->returnJsonData(1003);
		}
		$id	= M('crt_craftsmaninfo')->where(array('Phone'=>$phone))->save(array('Password'=>$passwd,'UpdateTime'=>$time));
		if($id) {
			$this->returnJsonData(200);
		}else{
			$this->returnJsonData(500);
		}
	}
	
	//检查验证码是否过期
	private function _checkVerify($phone,$verify_code){
		$where	= array('Phone'=>$phone,'Captcha'=>$verify_code,'Source'=>1);
		$edate	= M('cut_captcha')->where($where)->getField('LoseTime');
		if($edate){
			$second	= time()-strtotime($edate);
			if($second<0){
				return true;
			}else{
				return 'timeout';
			}
		}
		return false;
	}
	
	//检查数据唯一性
	private function _checkInfo($content, $type) {
		if(empty($content)) {
			return false;
		}
		$user_id = false;
		switch($type) {
			case 'email':
				$user_id	= M('crt_craftsmaninfo')->where(array('Email'=>$content))->getField('CraftsmanId');
				break;
			case 'phone':
				$user_id	= M('crt_craftsmaninfo')->where(array('Phone'=>$content))->getField('CraftsmanId');
				break;
			case 'id_card':
				$user_id	= M('crt_craftsmaninfo')->where(array('IdCard'=>$content))->getField('CraftsmanId');
				break;
			case 'username':
				$user_id	= M('crt_craftsmaninfo')->where(array('UserName'=>$content))->getField('CraftsmanId');
				break;
		}
		return $user_id;
	}
	
	//修改邮箱
	public function instEmail() {
		$postData	= I('request.');
		$email		= $postData['email'];
		$user_id	= $postData['user_id'];
		if(!($email && $user_id)) {
			$this->returnJsonData(300);
		}
		if(!check_email($email)) {
			$this->returnJsonData(505);
		}
		$user_id_s = M('crt_craftsmaninfo')->where(array('Email'=>$email, 'CraftsmanId'=>array('neq', $user_id)))->getField('CraftsmanId');
		if($user_id_s) {
			$this->returnJsonData(506);
		}else{
			$id	= M('crt_craftsmaninfo')->where(array('CraftsmanId'=>$user_id))->save(array('Email'=>$email,'CreaterTime'=>date('Y-m-d H:i:s')));
			if($id) {
				$this->returnJsonData(200);
			}else{
				$this->returnJsonData(500);
			}
		}
	}
	
	//修改我的位置
	public function instaddress() {
		$postData	= I('request.');
		$data['lat']	= $lat = $postData['lat'];
		$data['lng']	= $lng	= $postData['lng'];
		$data['address']= $address	= trim($postData['address']);
		$data['user_id']= $user_id	= $postData['user_id'];
		if(!($lat && $lng && $address && $user_id)) {
			$this->returnJsonData(300);
		}
		$status	= $this->_editBaseinfo($data);
		if($status) {
			$this->returnJsonData(200);
		}else{
			$this->returnJsonData(500);
		}
	}
	
	//修改个人说明
	public function instDesc() {
		$postData	        = I('request.');
		$data['description']	= $description   = $postData['description'];
		$data['user_id']	= $user_id       = $postData['user_id'];
		if(!($user_id))	{
			$this->returnJsonData(300);
		}
		$status	= $this->_editBaseinfo($data);
		if($status) {
			$this->returnJsonData(200);
		}else{
			$this->returnJsonData(500);
		}
	}
	
	private function _editBaseinfo($data) {
		if($data && is_array($data)) {
			$user_id = $data['user_id'];
			if(!$user_id) {
				return false;
			}
			
			if(isset($data['lat'])) {
				$hash['Lat'] = $data['lat'];
			}
			if(isset($data['lng'])) {
				$hash['Lng'] = $data['lng'];
			}
			if(isset($data['address'])) {
				$hash['Address'] = $data['address'];
			}
			if(isset($data['description'])) {
				$hash['Description'] = $data['description'];
			}
			unset($data);
			if($hash) {
				$id = M('crt_craftsmaninfo')->where(array('CraftsmanId'=>$user_id))->save($hash);
			}
			if($id) {
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	//删除城区
	public function DelDistrict() {
		$postData	= I('request.');
		$user_id	= $postData['user_id'];
		$district_id	= $postData['district_id'];
		if(!($user_id && $district_id)) {
			$this->returnJsonData(300);
		}
		$id = M('crt_craftsman_district')->where("CraftsmanId=%d and DistrictId=%d", $user_id, $district_id)->delete();
		if($id) {
			//如果用户删除所有的城区，那么XXX表中城市id赋值为0
			$count = M('crt_craftsman_district')->where("CraftsmanId=%d and DistrictId<>%d", $user_id, $district_id)->count();
			if($count == 0) {
				M('crt_craftsmaninfo')->where("CraftsmanId=%d", $user_id)->save(array('City'=>0, 'UpdateTime'=>date('Y-m-d H:i:s')));
			}
			$this->returnJsonData(200);
		}else{
			$this->returnJsonData(500);
		}
	}
	
	//添加城区
	public function AddDistrict() {
		$postData	= I('request.');
		$user_id	= $postData['user_id'];
		$district_id    = $postData['district_id'];
		$city_id	= $postData['city_id'];
		if(!($user_id && $district_id && $city_id)) {
			$this->returnJsonData(300);
		}
		$data	= array(
				'CraftsmanId'=>$user_id,
				'DistrictId' =>$district_id,
				'CreaterTime'=>date('Y-m-d H:i:s')
		);
		$id = M('crt_craftsman_district')->add($data);
		if($id) {
			$count = M('crt_craftsman_district')->where("CraftsmanId=%d", $user_id)->count();
			if($count == 0) {
				M('crt_craftsmaninfo')->where("CraftsmanId=%d", $user_id)->save(array('City'=>$city_id));
			}
			$user_info = M()->table('sys_district sd')
					->join('left join sys_city sc on sd.CityId = sc.CityId')
					->join('left join sys_province sp on sc.ProvinceId = sp.ProvinceId')
					->where("sd.DistrictId=%d", $district_id)
					->field('sc.CityName as city_name, sp.ProvinceName as p_name, sd.DistrictName as district_name')
					->find();
			$content = $user_info['p_name'].'-'.$user_info['city_name'].'-'.$user_info['district_name'];
			$hash    = array('content'=>$content, 'district_id'=>$district_id);
			$this->returnJsonData(200, $hash);
		}else{
			$this->returnJsonData(500);
		}
		
		//时间模板
		public function capacityTemplate() {
			$post_data = I('request.');
			$user_id   = $post_data['user_id'];
			if(empty($user_id)) {
				$this->returnJsonData(300);
			}
			$time_info = M('prd_servicetime')->where('IsDelete=0')->order('StartTime asc')->field('StartTime, TimeIe')->select();
			foreach((array)$time_info as $value) {
				$time_arr[$value['TimeId']] = $vlaue['StartTime'];
			}
			unset($time_arr);
			$capacity_template	= M('crt_capacity_template')->where("CraftsmanId=%d",$user_id)->select();
			foreach($capacity_template as $value) {
				$capacity_temp_arr[$value['WeekId']]['template_id']	= $value['TemplateId'];
				$capacity_temp_arr[$value['WeekId']]['user_id']	= $value['CraftsmanId'];
				$capacity_temp_arr[$value['WeekId']]['week_id']	= $value['WeekId'];
				$time_id_arr	= explode(',',trim($value['TimeStr'],','));
				$capacity_temp_arr[$value['WeekId']]['time_arr_id']= $time_id_arr;
			}
			unset($capacity_template);
			$week_info_s = $this->_weekId_s;
			$data = array();
			foreach((array)$week_info_s as $week_id => $value) {
				$tmp = array();
				$tmp['week'] = (string)$value;
				$tmp['week_id'] = (int)$week_id;
				$tmp['content'] = array();
				foreach((array)$time_arr as $time_id => $time_txt) {
					$time = array();
					$time['time'] = (string)$time_txt;
					$time['time_id'] = (int)$time_id;
					if(in_array($time_id, $capacity_temp_arr[$week_id]['time_arr_id'])) {
						$time['state']	= true;
					}else{
						$time['state']	= false;
					}
					$tmp['content'][]	= $time;
				}
				$data[] = $tmp;
			}
			$hash['user_id'] = $user_id;
			$hash['data']    = $data;
			$this->returnJsonData(200, $hash);
		}
	}
	
	//更新产能模板
	public function updateCapacityTemplate() {
		$json_string = file_get_contents('php://input');
		$post_data   = json_decode($json_string, true);
		$user_id	= $post_data['user_id'];
		$time_arr	= $post_data['data'];
		if(!($user_id && $time_arr)) {
			$this->returnJsonData(300);
		}
		foreach((array)$time_arr as $value) {
			$week_id	= $value['week_id'];
			$time_id_arr    = $value['time_id'];
			if($time_id_arr) {
				//添加、修改
				$time_str = ','.trim(implode(',', $time_id_arr), ',').',';
				$id = M('crt_capacity_template')->where("CraftsmanId=%d and WeekId=%d", $user_id, $week_id)->save(array('TimeStr'=>$time_str, 'UpdateTime'=>date('Y-m-d H:i:s')));
				if(!$id) {
					$data['CraftsmanId'] = $user_id;
					$data['WeekId']      = $week_id;
					$data['TimeStr']     = $time_str;
					$data['CreaterTime'] = date('Y-m-d H:i:s');
					M('crt_capacity_template')->add($data);
				}
			}else{
				//删除
				M('crt_capacity_template')->where("CraftsmanId=%d and WeekId=%d", $user_id, $weed_id)->delete();
			}
		}
		$this->returnJsonData(200);
	}
}
