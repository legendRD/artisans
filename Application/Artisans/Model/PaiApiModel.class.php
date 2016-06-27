<?php
namespace Artisans\Model;
use Think\Model;
class PayApiModel extends Model {
      private $_artisans_url      = 'http://localhost/card/getCardCodesByUser';  //查看用户卡券信息
      private $_sendCleanQ_url    = 'http://localhost/card/consume';             //核销卡券
      private $_expert_online     = 'http://localhost/tencent/weixinapi.php';    //专家在线服务
      private $_expert_online_url = '/share/pay_log_url/weixin/original/expert_online.log';
      private $_pay_log           = '/share/weixinLog/artisans';                 //微信日志
      
      //核销卡券
      public function cleanKQ($param) {
             if($param['code_pay_way'] == 3) {
                //核销优惠券
                $this->_cleanCoupons($param);
             }else{
                //核销卡券
                $user_id  = $param['UserId'];
                $openid   = $param['UserOpenid'];
                $cardid   = $param['cardid'];
                $codeid   = $param['codeid'];
                $order_id = $param['OrderId'];
                if(!($openid && $cardid && $order_id)) {
                     return false;
                }
                $getCleanQurl = $this->_artisans_url.'?id='.$cardid.'&openid='.$openid;
                $getCleanInfo = send_get_curl($getCleanQurl);
                $getCleanInfo = json_decode($getCleanInfo, true);
                if($getCleanInfo['error_code'] === 0 && $getCleanInfo['data']['card_codes'][0]['id'] == $codeid) {
                                $card_code = $getCleanInfo['data']['card_codes'][0]['card_code'];
                                $url = $this->_sendCleanQ_url.'?code='.$card_code.'&consume_type=100';
                                $reurnInfo = send_get_curl($url);
                                $returnInfo = json_decode($reurnInfo, true);
                                $cleanQlogData['status'] = $returnInfo['error_code'];
                }else{
                                $card_code = '';
                                $cleanQlogData['status'] = '-44';
                }
                $cleanQlogData['user_id']     = $user_id;
                $cleanQlogData['openid']      = $openid;
                $cleanQlogData['card_id']     = $cardid;
                $cleanQlogData['code_id']     = $codeid;
                $cleanQlogData['card_code']   = $card_code;
                $cleanQlogData['order_id']    = $order_id;
                $cleanQlogData['create_time'] = date('Y-m-d H:i:s');
                M('ord_deduction_card_log')->add($cleanQlogData);
                return true;
             }
      }
      
      //核销优惠券
    	private function _cleanCoupons($param){
    		$coupons_id	= $param['coupons_id'];
    		$phone		= $param['Phone'];
    		$where	= array(
    				'CodeId'=>$coupons_id,
    				'Phone'=>$phone,
    		);
    		$save_data	= array(
    				'UpdateTime'=>date('Y-m-d H:i:s'),
    				'IsUse'=>1,
    		);
    		$id		= M('active_phone_code')->where($where)->save($save_data); 
    		if(!$id){
    			wlog($this->_pay_log.'/activity_clean_code.log',$param);
    		}
    	}
    	
    	//支付流程推送消息
    	public function sendMsg($param){
    		$pay_process= $param['PayProcess'];
    		if($pay_process == 1 || $pay_process == 4){	//正常支付
    			$this->_sendToUser1($param);
    		}elseif($pay_process == 2){	//客服引导
    			$this->_sendToUser2($param);
    		}elseif($pay_process == 3){ //客服专家在线
    			$this->_sendToUser3($param);
    		}else{
    			$this->_sendToMe();	//异常提醒
    		}
    		if($pay_process	== 1){
    			$this->_sendAppMsg($param); //给XXX app推送极光消息
    		}
    		unset($param);
    	}
    	
      private function _sendToUser1($param) {
    		$order_id	= $param['OrderId'];
    		$openid		= $param['UserOpenid'];
    		$craft_id	= $param['CraftsmanId'];
    		$craft_openid	= $param['CraftsmanOpenid'];
    		$pay_way	= $param['PayWay'];
    		$for_who	= $param['ForWho'];
    		$price		= $param['Price'];
    		$product_name	= $param['product_name'];
    		$craft_name		= $param['CraftsmanName'];
    		$craft_phone	= $param['craft_phone'];
    		$customer_name	= $param['Name'];
    		$customer_phone	= $param['Phone'];
    		$time			= $param['ReservationTime'];
    		$address		= $param['Address'];
    		$wish			= $param['ShortMessage'];
    		$source_from	= $param['Source'];
    	
    		$user_url	= $art_url = "http://".$_SERVER['HTTP_HOST'].U('Craft/qcsstatus2').'?ordernum='.$order_id;
    		
    		if($pay_way == 1){	//线下支付
    			if($craft_id){
    				$customer_msg	= '【服务预约】您已经预约XXXXX的【'.$product_name.'】的服务（线下支付,金额'.$price.'），XXX是'.$craft_name.'，电话号码：'.$craft_phone.'；服务开始时间'.$time.'，<a href="'.$user_url.'">点击这里</a>查看详情。';
    				$customer_short_msg	= "【服务预约】您已经预约XXXXX的【{$product_name}】的服务（线下支付,金额{$price}），XXX是{$craft_name}，电话号码：{$craft_phone}；服务开始时间{$time}。";
    			}else{	//40公里线下支付
    				$craft_name		= 'XXXXX';
    				$craft_phone	= '';
    				$customer_msg	= '【服务预约】您已经预约XXXXX的【'.$product_name.'】的服务，XXX是'.$craft_name.'，电话号码：'.$craft_phone.'；服务开始时间'.$time.'，<a href="'.$user_url.'">点击这里</a>查看详情。';
    				$customer_short_msg	= "【服务预约】您已经预约XXXXX的【{$product_name}】的服务，XXX是{$craft_name}，电话号码：{$craft_phone}；服务开始时间{$time}。";
    			}
    			$craft_short_msg= "【服务预约】用户{$customer_name}，电话{$customer_phone}，（线下支付,金额{$price}）已经预约你的【{$product_name}】的服务，服务开始时间{$time}，请及时联系用户。";
    			$craft_msg		= '【服务预约】用户名'.$customer_name.'，电话'.$customer_phone.'，（线下支付,金额'.$price.'）已经预约你的【'.$product_name.'】的服务，时间'.$time.' <a href="'.$art_url.'">点击这里</a>可以查看详情，请记得提醒用户在我的服务中更新单据状态，并点评哦';
    	
    		}elseif($for_who == 1){ //为朋友
    	
    			$customer_msg  = '您已经帮朋友'.$customer_name.'预约了XXXXX的【'.$product_name.'】服务，XXX是'.$craft_name.'，服务开始时间'.$time.'，已经短信通知了您的朋友。<a href="'.$user_url.'">点击这里</a>查看详情；点击这里点击后进入预约单详情页面。';
    			$customer_short_msg = "您的好友为您预约了XXXXX{$product_name}的服务，XXX是{$craft_name}，手机：{$craft_phone}，服务时间：{$time}。";
    			if($wish) $customer_short_msg .= "您朋友还想和您说:{$wish}";
    	
    			$craft_msg = '客户'.$customer_name.'已经预约你上门进行【'.$product_name.'】服务，电话：'.$customer_phone.'，时间:'.$time.'；地点：'.$address.'；是Ta朋友帮他预约的。<a href="'.$art_url.'">点击这里</a>查看详情；同时请让上门后打开页面，请用户操作。';
    			$craft_short_msg = "客户{$customer_name}已经预约你上门进行【{$product_name}】服务，电话：{$customer_phone}，时间:{$time}；地点：{$address}；是Ta朋友帮他预约的。";
    	
    		}else{	//为自己
    	
    			$customer_msg  = '【服务预约】您已经预约XXXXX的【'.$product_name.'】的服务，XXX是'.$craft_name.'，电话号码：'.$craft_phone.'；服务开始开始时间'.$time.'，<a href="'.$user_url.'">点击这里</a>查看详情。';
    			$customer_short_msg = "【服务预约】您已经预约XXXXX的【{$product_name}】的服务，XXX是{$craft_name}，电话号码：{$craft_phone}；服务开始开始时间{$time}。";
    	
    			$craft_short_msg = "【服务预约】用户{$customer_name}，电话{$customer_phone}，已经预约你的【{$product_name}】的服务，服务开始开始时间{$time}，请及时联系用户。";
    			$craft_msg =  '【服务预约】用户名'.$customer_name.'，电话'.$customer_phone.'，已经预约你的【'.$product_name.'】的服务，时间'.$time.' <a href="'.$art_url.'">点击这里</a>可以查看详情，请记得提醒用户在我的服务中更新单据状态，并点评哦';
    		}
    		
    		//发送微信消息
    		$this->_sendWeixinMsg($openid,$customer_msg);
    		$this->_sendWeixinMsg($craft_openid,$craft_msg);
    		//发送短信
    		$this->_sendShortMessage($customer_phone,$customer_short_msg);
    		$this->_sendShortMessage($craft_phone,$craft_short_msg);
    		
    		$boss_openid_arr = array(
    		                   '',
    		                   ''
    		);
    		switch($source_from){
    			case 0:
    				$craft_msg .= '【wx】';
    				break;
    			case 1:
    				$craft_msg .= '【web】';
    				break;
    			case 2:
    				$craft_msg .= '【app_android】';
    				break;
    			case 3:
    				$craft_msg .= '【app_ios】';
    				break;
    		}
    		foreach($boss_openid_arr as $value){
    			$this->_sendWeixinMsg($value,$craft_msg);
    		}
      }
      
      //客服导购
      private function _sendToUser2($param){
              $vmall_order_id	= $param['VmallOrderId'];
          		$openid		      = $param['UserOpenid'];
          		$price		      = $param['Price'];
          		$product_name   = $param['product_name'];
          		$user_id	      = $param['UserId'];
          		$pay_time	      = $param['PayTime'];
          		//微信消息
          		$content	= '您已支付成功，XXX将会主动与您联系，请保持手机畅通！感谢您选择XXXXX。';
          		$this->_sendWeixinMsg($openid,$content); //用户
          		$content	= '用户id:'.$user_id.',订单号：'.$vmall_order_id.',价格：'.$price.',产品：'.$product_name.',支付时间：'.$pay_time.'【客服导购】';
          		$head_openid_arr = array('','');
          		foreach($head_openid_arr as $openid){
          			$this->_sendWeixinMsg($openid,$content);
          		}
      }
      
      //客服专家在线
      private function _sendToUser3($param) {
    		$order_id	= $param['OrderId'];
    		$openid		= $param['UserOpenid'];
    		$price		= $param['Price'];
    		$product_name= $param['product_name'];
    		$user_id	= $param['UserId'];
    		$pay_time	= $param['PayTime'];
    		$vmall_order_id	= $param['VmallOrderId'];
    		$ip			= $param['Address'];
    		$phone		= $param['Phone'];
    	
    		$log_msg	= "id:".$order_id.";order_num:".$vmall_order_id;
    		
    		
    		$transfer_data	= array(
    				'PlatformId'=>$param['Source'],
    				'CityId'=>M('sys_city')->where(array('CityName'=>$param['CityName']))->getField('CityId'),
    				'ProductId'=>$param['product_id'],
    		);
    		$product_info	= A('Api')->getProductInfo($transfer_data); //产品信息
    		
    		$data	= array(
    				'datetime'=>date('Y-m-d H:i:s'),
    				'ip'=>$ip,
    				'phone'=>$phone,
    				'type'=>$product_info['data']['remoteType'],
    		);
    		$receive_data	= send_curl($this->_expert_online,$data);
    		wlog($this->_expert_online_url,$log_msg);
    		wlog($this->_expert_online_url,$receive_data);
    	
    		$content	= "感谢您购买XX远程服务产品，请在电脑上打开浏览器输入dwz .cn/H1A5M (请注意大小写)，在打开的页面中输入您的手机号码即可与在线专家建立连接";
    		$this->_sendWeixinMsg($openid,$content); //用户
    	
    		$content	= '订单号：'.$vmall_order_id.',价格：'.$price.',产品：'.$product_name.',支付时间：'.$pay_time.'【远程服务】';
    		$head_openid_arr	= array(
            '',
            ''
    		);
    		foreach($head_openid_arr as $openid){
    			$this->_sendWeixinMsg($openid,$content);
    		}
    	}
    	
    	private function _sendToMe($param){
      		$order_id	= $param['OrderId'];
      		$head_openid_arr	= array(
      				'',
      				''
      		);
      		foreach($head_openid_arr as $openid){
      			$this->_sendWeixinMsg($openid,$order_id);
      		}
       }
      	
       // 发送短消息的方法
	public function _sendShortMessage($phone,$content){
		$url	= $this->_pay_log.'/send_short_msg/'.date('Ymd').'.log';
		$data['phone']	= $phone;
		$data['content']= $content;
		$data['url']	= $url;
		D('Comm')->sendShortMsg2($data);
	}
	
	public function _sendWeixinMsg($open_id,$content){
		$url	= $this->_pay_log.'/send_wx_msg/'.date('Ymd').'.log';
		$data['openid']	= $open_id;
		$data['content']= $content;
		$data['url']	= $url;
		D('Comm')->sendWeixinMsg2($data);
	}
	
	//卡券信息
	public function getUserCardinfo($param){
		$cardid	= $param['cardid'];
		$codeid	= $param['codeid'];
		$openid	= $param['openid'];
		$card_info	= array();
		$card_info['name']	= '卡券';
		$card_info['money']	= 0;
		$card_info['id']	= '';
		if($cardid && $codeid){
			$getcard_artisans_url = $this->_artisans_url.'?id='.$cardid.'&openid='.$openid;
			$getcard_artisans_info  = json_decode(send_get_curl($getcard_artisans_url),true);
			$today  = date('Y-m-d H:i:s');
			if($getcard_artisans_info['error_code'] === 0
			&& $getcard_artisans_info['data']['card']['start_time']<$today
			&& $getcard_artisans_info['data']['card']['end_time']>$today
			&& $getcard_artisans_info['data']['card']['state']==100
			&& $getcard_artisans_info['data']['card_codes'][0]['cost_count']==="0"
			&& $getcard_artisans_info['data']['card_codes'][0]['id']==$codeid){
				$card_info['name']	= $getcard_artisans_info['data']['card']['title'];
				$card_info['money']	= $getcard_artisans_info['data']['card']['reduce_cost'];
				$card_info['id']	= $cardid;
			}
		}
		return $card_info;
	}
	
	/**
	 * 获取用户优惠券信息
	 * @param	int		  $param['source'] 优惠券来源
	 * @param	string	$param['phone']  手机号
	 * @param	int		  $param['pro_id'] 产品id
	 * @param	string	$param['coupons_id'] 优惠券id
	 * @return	array
	 */
	public function getUserCouponsinfo($param) {
	        $source	= $param['source']; //1为58
      		$phone	= $param['phone'];
      		$coupons_id	= $param['coupons_id'];
      		$pro_id	=$param['pro_id'];
      		$card_info	= array();
      		$card_info['name']	= '优惠券';
      		$card_info['money']	= 0;
      		$card_info['id']	= '';
      		
      		if($source && $phone && $pro_id){
      			$activity_where	= array( //58优惠券
      					'apc.`Source`'=>$source,
      					'apc.`Phone`'=>$phone,
      					'apc.`IsUse`'=>0,
      			);
      			if($coupons_id) {
      			  $activity_where['apc.`CodeId`']	= $coupons_id;
      			}
      			$activity_info	= M()->table('active_phone_code apc ')
      			->join('left join active_coupons_info aci on apc.CouponsId=aci.CouponsId')
      			->where($activity_where)
      			->field('CodeId,aci.CouponsId,CodeNum,StartTime,EndTime,Cash,ProductIds')
      			->find();
      			
      			$pro_id_arr		= explode(',',$activity_info['ProductIds']);	
      			$now_time		= date('Y-m-d H:i:s');
      			if($activity_info['StartTime']<=$now_time && $activity_info['EndTime']>=$now_time 
      				&& in_array($pro_id,$pro_id_arr)){
      				$card_info['money']	= $activity_info['Cash'];
      				$card_info['id']	= $activity_info['CodeId'];
      			}
      		}
      		return $card_info;
	}
	
	/**
	 * 订单状态明细
	 * @access private
	 * @param array $var
	 * @return mixed
	 */
	public function addOrderState($var){
		$data['OrderId']	= $order_id	= $var['order_id'];
		if(empty($order_id)) {
		  return false;
		}
		$data['State']	= (int)$var['state'];
		$data['Description']	= $var['state_desc'];
		$data['CreaterBy']		= $var['cuid']? $var['cuid']:0;
		$data['CreaterTime']	= $var['create_time'];
		$id	= M('ord_state')->add($data);
		if($id){
			return $id;
		}else{
			//订单状态日志
			return false;
		}
	}
	
	//给XXXapp推送信息：用户下订单
	public function _sendAppMsg($param) {
	       $app_url = $this->_pay_log.'/send_craft_app_msg/'.date('Ymd').'.log';
	       $send_url = C('JPUSH_API');  //极光推送消息地址
	       $craft_id = $param['CraftsmanId'];
	       $where = array(
	              'CidType'=>0,
	              'CraftsmanId'=>$craft_id,
	              'State'=>1
	       );
	       $cid_info = M('crt_craftsman_cid')->where($where)->find();
	       $data['platform'] = 'all';
	       $data['audience'] = "{'registration_id':['".$cid_info['Cid']."']}";
	       $data['title']    = '标题';
	       $data['content_type'] = '类型';
	       $extras = array(
	                 'type'=>10,
	                 'uid'=>$craft_id.
	                 'code'=>$cid_info['Code'],
	                 'order_id'=>$param['OrderId']
	       );
	       $data['extras'] = json_encode($extras);
	       $msg .= '您有一个【'.$param['product_name'].'】新订单，时间：';                  //消息推送内容
	       $msg .= '【'.date('m月d日 H:i', strtotime($param['ReservationTime'])).'】，';
	       $msg .= '地点：【'.$param['CityName'].$param['Address'].'】';
	       
	       $data['msg_content'] = $msg;
	       wlog($app_url, $data);
	       $ret = send_curl($send_url, $data);
	       wlog($app_url, $ret);
	 }
	 
	 //释放XX
	public function releaseCapacity($capacity_id){
  		if(!$capacity_id) return false;
  		$data	= array(
  				'NouseNum'=>1,
  				'RecoveryTime'=>date('Y-m-d H:i:s'),
  		);
  		$id	= M('crt_capacity')->where("CapacityId=%d",$capacity_id)->save($data);
  		if($id){
  			$id	= M('crt_use')->where("CapacityId=%d",$capacity_id)->delete();
  		}
  		if($id){
  			return true;
  		}else{
  			return false;
  		}
	}
	
	
}
	
	
