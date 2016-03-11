<?php
namespace Artisans\Model;
use Think\Model;
class ApiModel extends Model {
  
  protected $autoCheckFields    = false;
  private $_thirdlogin_url      = "http://localhost/user/login";                  //第三方登录
  private $_login_url           = "http://localhost/user/login";                  //用户登录
  private $_userinfo_url        = "http://localhost/user/getByUid"                //用户信息
  private $_get_token           = "http://localhost/user/auth";                   //获取接口token地址
  private $_access_token        = "";
  private $_shortmsg_verifycode = '/share/weixinLog/sendShortMsg/sendverify/';    //发送验证码log
  private $_user_api_reg        = '/UserCenterApi/regUser';                       //用户中心api
  
  /**
   * 支付获取商品名
   * @param int $order_id	订单号id
   * @return mixed
   */
  public function getOrderShopInfo($order_id) {
	   if(empty($order_id)) {
	     return false;
	   }
	   $order_shop_info	= M()->table('ord_orderinfo as oo')
	                         ->join(' left join ord_order_item as ooi on oo.OrderId=ooi.OrderId ')
	                         ->where(array('oo.OrderId'=>$order_id))->join('left join prd_productinfo as pp on pp.ProductId=ooi.ProductId')
		                       ->field('count(1) num,ooi.PackageId package_id,ooi.PackageName package_name,group_concat(ooi.ProductId) as pro_id,group_concat(ooi.ProductName)as pro_name,LogoImgUrl,LogoImgCdnUrl')
		                       ->group('oo.OrderId')
		                       ->find();
		 $tmp = array();
		 if($order_shop_info) {
		   if($order_shop_info['package_id']){
				$tmp['shop_name']	= $order_shop_info['package_name'];
				$tmp['shop_id']	= $order_shop_info['package_id'];
			 }elseif($order_shop_info['num']>1){
				$tmp['shop_name']	= $order_shop_info['pro_name'];
				$tmp['shop_id']	= $order_shop_info['pro_id'];
				$tmp['pro_img']	= $this->getImgUrl($order_shop_info['LogoImgCdnUrl'],$order_shop_info['LogoImgUrl']);
			 }else{
				$tmp['shop_name']	= $order_shop_info['pro_name'];
				$tmp['shop_id']	= $order_shop_info['pro_id'];
				$tmp['pro_img']	= $this->getImgUrl($order_shop_info['LogoImgCdnUrl'],$order_shop_info['LogoImgUrl']);
			 }
		 }
		 unset($order_shop_info);
		 return $tmp;
	 }
        
        /*获取产品列表
         *
         *必传
         *平台id PlatformId
         *城市id CityId
         *
         *可传
         *排序 1 正序 2 倒序 Sorting
         *类型 查码表的类型  ClassType
         *（是否分页）
         *当前页	     page
         *每页显示多少条产品 limit
         */
        public function getProductList($postData) {
        	
        	//拼接where条件
        	$where['rela.PlatformId']  =  $postData['PlatformId'];
		$where['rela.CityId']  =  $postData['CityId'];
		
		if(isset($postData['CityId']) && isset($postData['PlatformId'])) {
			
			//分类的筛选条件
			if(isset($postData['ClassType'])) {
				$where['rela.ClassType'] = $postData['ClassType'];
			}
			
			//排序方式的筛选
			if(isset($postData['Sorting']) && $postData['Sorting'] == 2) {
				$order['rela.Sorting'] = 'desc';
			}else{
				$order['rela.Sorting'] = 'asc';
			}
			
			$field[]   =   'rela.RelationshipId';
			$field[]   =   'prd.ProductId';
			$field[]   =   'prd.ProductName as name';
			$field[]   =   'prd.ProductTitle as title';
			$field[]   =   'prd.AddressInfo as addressInfo';
			$field[]   =   'prd.LogoImgUrl as classImg';
			$field[]   =   'prd.LogoImgCdnUrl as classImg_cdn';
			$field[]   =   'prd.DetailImgUrl as headImg';
			$field[]   =   'prd.DetailImgCdnUrl as headImg_cdn';
			$field[]   =   'prd.ProductType as prdtype';
			$field[]   =   'prd.Reserve1 as position';
			$field[]   =   'prd.Price as proPrice';
			
			$where['prd.IsShelves']  =  1;
			$where['prd.IsDelete']  =  0;
			
			if(isset($postData['limit']) && isset($postData['page'])) {
				$return['count'] = M('prd_product_platform_city')->where($where)->order($order)->count();
				$data = M('prd_product_platform_city as rela')
					->join("left join prd_productinfo prd on prd.ProductId=rela.ProductId")
					->where($where)
					->order($order)
					->field($field)
					->page($postData['page'], $postData['limit'])
					->select();
			}else{
				$data = M('prd_product_platform_city as rela')
					->join("left join prd_productinfo prd on prd.ProductId=rela.ProductId")
					->where($where)
					->field($field)
					->order($order)
					->select();
			}
			if($data) {
				foreach($data as $k=>$v) {
					$attribute = M('prd_attribute')->where(array('RelationshipId'=>$v['RelationshipId']))->select();
					foreach($attribute as $k1=>$v1) {
						$attributeName = $v1['Attribute'];
						$AttributeValeu = $v1['Value'];
						$data[$k][$attributeName] = $AttributeValeu;
					}
				}
				foreach($data as $k=>$v) {
					//活动的查询
					$where_pro['ProductId'] = $v['ProductId'];
					$where_pro['StartTime'] = array('lt', date("Y-m-d H:i:s"));
					$where_pro['EndTime']   = array('gt', date("Y-m-d H:i:s"));
					$where_pro['IsDelete']  = 0;
					$where_pro['IsUse']	= 1;
					
					//活动表筛选的字段
					$field_pro[] = 'PromotionID';
					$field_pro[] = 'ActivityPrice as endPrice';
					$field_pro[] = 'Discount as discount';
					$field_pro[] = 'StartTime as startTime';
					$field_pro[] = 'EndTime as endTime';
					
					$promotion = M('prd_promotion')->where($where_pro)->field($field_pro)->find();
					$data[$k]['promotion'] = $promotion;
				}
				$return['status'] = 200;
				$return['msg']    = 'success';
				$return['data']   = $data;
			}
		}else{
			$return['status'] = 300;
			$return['msg']    = '参数错误';
		}
		return $return;
        }
        
        //产品详情接口
        public function getProductInfo($postData) {
        	if(isset($postData['CityId']) && isset($postData['PlatformId']) && isset($postData['ProductId']))
        	{
        		$where['prd.IsShelves']  =  1;
			$where['prd.IsDelete']   =  0;
			$where['rela.ProductId']  =  $postData['ProductId'];
			$where['rela.PlatformId']  =  $postData['PlatformId'];
			$where['rela.CityId']  =  $postData['CityId'];
			
			$field[]   =   'rela.RelationshipId';
			$field[]   =   'prd.ProductId';
			$field[]   =   'prd.ProductName as name';
			$field[]   =   'prd.ProductTitle as title';
			$field[]   =   'prd.AddressInfo as addressInfo';
			$field[]   =   'prd.LogoImgUrl as classImg';
			$field[]   =   'prd.LogoImgCdnUrl as classImg_cdn';
			$field[]   =   'prd.DetailImgUrl as headImg';
			$field[]   =   'prd.DetailImgCdnUrl as headImg_cdn';
			$field[]   =   'prd.ProductType as prdtype';
			$field[]   =   'prd.Reserve1 as position';
			$field[]   =   'prd.ProductIntroduction as proIntro';
			$field[]   =   'prd.Promise as proMise';
			$field[]   =   'prd.UserInstructions as userInstr';
			$field[]   =   'prd.Description as description';
			$field[]   =   'prd.Highlights as proSpecial';
			$field[]   =   'prd.Advantage as proAdvan';
			$field[]   =   'prd.ProductImgUrl1 as img1';
			$field[]   =   'prd.ProductImgUrl2 as img2';
			$field[]   =   'prd.ProductImgUrl3 as img3';
			$field[]   =   'prd.ProductImgUrl4 as img4';
			$field[]   =   'prd.ProductImgUrl5 as img5';
			$field[]   =   'prd.ProductImgUrl6 as img6';
			$field[]   =   'prd.ProductImgCdnUrl1 as cdn_img1';
			$field[]   =   'prd.ProductImgCdnUrl2 as cdn_img2';
			$field[]   =   'prd.ProductImgCdnUrl3 as cdn_img3';
			$field[]   =   'prd.ProductImgCdnUrl4 as cdn_img4';
			$field[]   =   'prd.ProductImgCdnUrl5 as cdn_img5';
			$field[]   =   'prd.ProductImgCdnUrl6 as cdn_img6';
			$field[]   =   'prd.Price as proPrice';
			
			$data  =  M('prd_product_platform_city as rela')
				  ->join("left join prd_productinfo prd on prd.ProductId=rela.ProductId")
				  ->where($where)
				  ->field($field)
				  ->find();
				  
			if($data) {
				$attribute = M('prd_attribute')
					     ->where(array('RelationshipId'=>$data['RelationshipId']))
					     ->select();
				foreach($attribute as $k1=>$v1) {
					$attributeName = $v1['Attribute'];
					$AttributeValeu = $v1['Value'];
					$data[$attributeName] = $AttributeValeu;
				}
				
				//活动的查询条件
				$where_pro['ProductId'] = $data['ProductId'];
				$where_pro['StartTime'] = array('lt', date("Y-m-d H:i:s"));
				$where_pro['EndTime']   = array('gt', date("Y-m-d H:i:s"));
				$where_pro['IsDelete']  = 0;
				$where_pro['IsUse']	= 1;
				
				//活动表筛选的字段
				$field_pro[]  =  'PromotionID';
				$field_pro[]  =  'ActivityPrice as endPrice';
				$field_pro[]  =  'Discount as discount';
				$field_pro[]  =  'StartTime as startTime';
				$field_pro[]  =  'EndTime as endTime';
				
				$promotion = M('prd_pomotion')
					     ->where($where_pro)
					     ->field($field_pro)
					     ->find();
				$data['promotion'] = $promotion;
				
				$return['status'] = 200;
				$return['msg']    = 'success';
				$return['data']   = $data; 
			}else{
				$return['status'] = 305;
				$return['msg']	  = '未找到该产品';
			}
        	}else{
        		$return['status'] = 300;
			$return['msg']	  = '参数错误';
        	}
        	return $return;
        }
        
        //获取产品活动Id
        public function getProActive($param) {
        	$city_id = $param['city_id'];
        	$pro_id  = $param['pro_id'];
        	$now     = date('Y-m-d H:i:s');
        	if(!($city_id && $pro_id)) {
        		return false;
        	}
        	$where	= array(
				'pap.ProductId'=>$pro_id,
				'pac.CityId'=>$city_id,
				'pa.IsDelete'=>0,
				'pa.StartTime'=>array('gt',$now),
				'pa.EndTime'=>array('lt',$now),
		);
		$field = 'ActiveName active_name,ActiveId active_id,Description description,ImgUrl img_url,ImgCdnUrl img_cdn_url,StartTime start_time,EndTime end_time,GroupWay group_way';
		$active_info	= M()->table('prd_active_product  pap')
				     ->join('left join prd_active_city pac on pap.ActiveId=pac.ActiveId')
				     ->join('left join prd_active pa on pac.ActiveId=pa.ActiveId')
				     ->where($where)
				     ->field($field)
				     ->select($field);
		return $active_info;
        }
        
        //获取用户拥有哪些优惠券
        public function getUserCardInfo($param) {
        	$active_id_arr	= $param['active_id_arr'];
		$uid		= $param['uid'];
		if(!($active_id_arr && is_array($active_id_arr) && $uid)) {
			return false;
		}
		$where = array(
			'ActiveId'=>array('in', $active_id_arr),
		);
		$field = 'Codeid code_id, ActiveId active_id, Type type';
		$active_item_info = M('prd_active_itme')->where($where)
							->field($field)
							->select();
		foreach((array)$active_item_info as $value){
			$active_item_info_s[$value['Type']][]	= $value['code_id'];
		}
		$card_info_s = array();
		foreach((array)$active_item_info_s as $key=>$value) {
			$where = array();
			if($key == 0) {
				//使用折扣
			}elseif($key == 1) {
				//使用卡券
			}elseif($key == 2) {
				//使用红包
				$field	= 'MyRedId my_red_id,FromId from_id,ToId to_id,Money money,IsUse is_use';
				$where = array(
						'RedbagId'=>array('in',$value),
						'ToId'=>$uid,
				);
				$card_info	= M('cut_myred')->where($where)->field($field)->select();
				$card_info_s[$key] = $card_info;
			}
		}
		return $card_info_s;
        }
        
        /**
	 * 获取城市信息
	 * @access public
	 * @param  string/int $city	城市名/城市Id
	 * @return boolean|unknown
	 */
	 public function getCityInfo($city) {
	 	if(empty($city)) {
	 		return false;
	 	}
	 	if(is_numeric($city)) {
	 		$city_info = M('sys_city')->where("CityId=%d", $city)->find();
	 	}else{
	 		$city_info = M('sys_city')->where("CityName like '%s'", $city)->find();
	 	}
	 	return $city_info;
	 }
	 
	 /**
	 * 是否第一次下单
	 * @access public
	 * @param int $user_id	用户id
	 * @param array $source_arr	订单来源
	 * @return boolean|unknown
	 */
	public function isFirstOrder($user_id,$source_arr) {
		if(empty($user_id)) {
			return false;
		}
		if(!($source_arr && is_array($source_arr))) {
			return false;
		}
		$where = array(
				'UserId'=>$user_id,
				'Source'=>array('in',$source_arr),
				'Status'=>array('gt',1),
		);
		$count	= M('ord_orderinfo')->where($where)->count();
		return $count;
	}
	
	/**
	 * XXX某个时间产能信息
	 * @param int $user_id	  XXXid
	 * @param int $order_date 预约日期
	 * @param int $order_time_id 预约时间Id
	 * @return mixed
	 */
	public function getCapacity($user_id,$order_date,$order_time_id) {
		if(!($user_id && $order_date && $order_time_id)) {
			return false;
		}
		$capacity_info	= M('crt_capacity')->where("Capacity='%s' and CraftsmanId = %d and TimeId = %d", $order_date, $user_id, $order_time_id)
						   ->find();
		return $capacity_info;
	}
	
	/**
	 * 判断XXX产能是否存在
	 * @param int $user_id	  XXXid
	 * @param int $order_date 预约日期
	 * @param int $order_time_id 预约时间Id
	 * @return boolean
	 */
	 public function isCapacity($user_id,$order_date,$order_time_id) {
	 	if(!($user_id && $order_date && $order_time_id)) {
	 		return false;
	 	}
	 	$capacity_num	= M('crt_capacity')->where("Capacity='%s' and CraftsmanId=%d and TimeId=%d",$order_date,$user_id,$order_time_id)->getField('NouseNum');
	 	if($capacity_num>0) {
			return true;
		}else{
			return false;
		}
	 }
	 
	 /**
	 * 减产能
	 * @access public
	 * @param int $capacity_id
	 * @return mixed
	 */
	 public function reduceCapacity($capacity_id) {
	 	if(!($capacity_id && is_numeric($capacity_id))) {
	 		return false;
	 	}
	 	$sql = " update crt_capacity set NouseNum=NouseNum-1 where CapacityId=".$capacity_id." and NouseNum>0 ";
	 	$id = M()->execute($sql);
	 	return $id;
	 }
	 
	 /**
	 * 获取第三方用户信息
	 * @access public
	 * @param  array  用户信息
	 * @return mixed
	 */
	public function getThirdUserInfo($param) {
		
	}
	
	/**
	 * 获取用户信息
	 * @access public
	 * @param  int  用户Id
	 * @return mixed
	 */
	public function getUserInfo($uid) {
		
	}
	
	/**
	 * 获取datetime
	 * @access public
	 * @param  date	$param['order_date']	预约日期
	 * @param  int	$param['order_time_id']	时间Id
	 * @return mixed
	 */
	public function getOrderDate($param) {
		$order_date	= $param['order_date'];
		$order_time_id	= $param['order_time_id'];
		if(!($order_date && $order_time_id)) {
			return false;
		}
		$time	= $this->getTime($order_time_id);
		if($time) {
			$date_time  = date('Y-m-d H:i:s',strtotime($order_date.' '.$time.':00'));
			return $date_time;
		}else{
			return false;
		}
	}
	
	/**
	 * 获取time
	 * @access public
	 * @param  int	$order_time_id	时间Id
	 * @return mixed
	 */
	public function getTime($order_time_id) {
		if(!$order_time_id) {
			return false;
		}
		$time	= M('prd_servicetime')->where(" IsDelete=0 and TimeId=%d",$order_time_id)->getField('StartTime');
		return trim($time);
	}
	
	//数据过滤  删除空格
	public function trimall($str) {
		$qian[] =" ";
		$qian[] ="\t";
		$qian[] ="\n";
		$qian[] ="\r";
		$qian[]="http://localhost/weixin/index.php/";
		$qian[]="http://localhost/wxuat/index.php/";
		$qian[]="http://localhost/weixin1.1/weixin_1.1/index.php/";
		$qian[]="http://localhost/artisans_1.1/index.php/Artisans/Craft";
		$qian[]="http://localhost/artisans_test/index.php/Artisans/Craft";
		$qian[]="lat=";
		$qian[]="lnt=";
		$qian[]="forWho=";
		
		$hou = array_fill(0, count($qian), "");
		return str_replace($qian, $hou, $str);
	}
	
	function clear_urlcan($url) {
		$tmparr = parse_url($url);
		$str = empty($tmparr['scheme'])?'http://':$tmparr['scheme'].'://';
		$str.= $tmparr['host'].$tmparr['path'];
		return $str;
	}
	
	/**
	 * 发送验证码
	 * @param unknown $param
	 * @return boolean
	 */
	public function sendVerifyCode($param) {
		$phone = $param['phone'];
		$code  = $param['code'];	//验证码
		$create_time = $param['create_time'];
		$lose_time = $param['lose_time'];	//失效时间
		$content = $param['conten'];
		if(!($phone && $code && $create_time && $lose_time && $content)) {
			return false;
		}
		$source = $param['source'];
		$where = array(
			'Phone'=>$phone,
			'Captcha'=>$code,
			'CreaterTime'=>$create_time,
			'LoseTime'=>$lose_time,
			'Source'=>$source
		);
		$id = M('cut_captcha')->add($data);
		if($id) {
			$send_short_msg['source'] = $source;
			$send_short_msg['phone']  = $phone;
			$send_short_msg['content']= $content;
			$send_short_msg['url']    = $this->_shortmsg_verifycode.date('Ymd').'.log';
			D('Comm')->sendShortMsg2($send_short_msg);
			unset($send_short_msg);
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 校验验证码
	 * @param string $param['phone'] 手机号
	 * @param string $param['verify_code'] 验证码
	 * @param int	 $param['source'] 验证码来源 【0用户App,1XXXApp,2微信端，3pcweb端】
	 * @return boolean|string
	 */
	public function checkVerify($param) {
		$phone = $param['phone'];
		$verify_code = $param['verify_code'];
		$source = $param['source'];
		if(!($phone && $verify_code && $source)) {
			return false;
		}
		$where = array(
			'Phone'  =>$phone,
			'Captcha'=>$verify_code,
			'Source' =>$source
		);
		$edate = M('cut_captcha')->where($where)->getField('LoseTime');
		if($edate) {
			$second = time()-strtotime($edate);
			if($second<0) {
				return true;
			}else{
				return 'timeout';
			}
		}
		return false;
	}
	
	/**
	 * 绑定第三方用户
	 * @access	public 
	 * @param	string $username 第三方账号
	 * @return	array 
	 */
	public function bindThirdUser($param) {
		$source_from = $param['source_from'];
		$username    = $param['username'];
		if(!($username && $source_from)) {
			return false;
		}
		$user_info = M('cut_uid_thirdname')->where(" ThirdName='%s'", $username)->find();
		if(empty($user_info)) {
			$base_info_s = array(
				'source_from'=>$source_from,
				'user_type'  =>'other',
				'type'	     =>200,
				'username'   =>$username,
			);
			$user_center_info 	  = send_curl(C('ArtisansApi').$this->_user_api_reg, $base_info_s);
			$parse_data	  	  = json_decode($user_center_info, true);
			$user_info['ThirdName']   = $username;
			$user_info['CreaterTime'] = date('Y-m-d H:i:s');
			if(!$parse_data['data']['uid']) {
				$user_info['UserId'] = 0;
				wlog('/share/weixinLog/artisans/user_center_api/no_find_uid.log', $username);	//调用用户中心接口，获取不到用户openid
				wlog('/share/weixinLog/artisans/user_center_api/no_find_uid.log', $parse_data);
			}else{
				$user_info['UserId'] = $parse_data['data']['uid'];
				M('cut_uid_thirdname')->add($user_info);
			}
		}
		return $user_info;
	}
	
	/**
	 * 订单产能是否存在
	 * @access public
	 * @param  array $orderinfo	订单信息
	 * @return boolean
	 */
	public function orderExistTime($orderinfo) {
		$reservation_time = $orderinfo['ReservationTime'];
		//判断预约时间是否过期，小于3小时过期
		if((strtotime($reservation_time)-10800) < time() || !$reservation_time) {
			return false;
		}
		//订单创建时间超过15分钟，过期
		if(strtotime($orderinfo['CreaterTime']) + 900 < time()) {
			return false;
		}
		$capacity_id = $orderinfo['CapacityId'];
		$time_num    = M('crt_use')->where(array('CapacityId'=>$capacity_id))->count();
		if($time_num==1) {
			return true;
		}else{
			return false;
		}
	}
}
