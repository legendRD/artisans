<?php
namespace Artisans\Controller;
class WxController extends CommonController {
  private $_artisans_url   = 'http://localhost/card/getCardCodesByUser?id=';
  private $_sendCleanQ_url = 'http://localhost/card/consume';
  private $_cardid         = array(1=>'69');
  private $_redbag_log     = '/share/weixinLog/artisans/weixin/redbag';     //红包更新状态日志目录
  public $pay_log_url      = '/share/pay_log_url/';                         //支付信息日志记录
  
  public function selectCity() {
    $city = I('get.city', '', 'sql_filter');
    $this->isCity($city);
  }
  
  //判断是不是所在数据库中的城市
  private function isCity($city) {
    $city_where['status'] = 1;
    $city_arr = M('artisans_city')->where($city_where)->select();
    foreach($city_arr as $value) {
      $city_array[] = $value['city'];
    }
    if(in_array($city, $city_array)) {
      return $city;
    }else{
      $this->dt();    //走sorry页面
      exit;
    }
  }
  
  public function passCity() {
    //获取到城市的位置
    $city = I('post.city', '', 'sql_filter')==''?I('get.city', '', 'sql_filter'):I('post.city', '', 'sql_filter');
    //转换为城市并把市去掉
    $city = str_replace('市', '', $city);
    // 制作城市查找条件
    $city_where['status'] = 1;
    // 通过查询city表把开通的城市筛选出来
    $city_arr = M('artisans_city')->where($city_where)->select();
    // 通过foreach把城市转为索引数组的城市id
    foreach($city_arr as &$value) {
      if($city==$value['city']) {
        $arr = $value;
      }
    }
    // 切割字符串来使页面获取到开通城市对应的技能id
    $passCity['moduleId'] = explode(',', $arr['moduleIdStr']);
    $len = count($passCity['moduleId']);
    // 该页面共有9个技能信息 获取到长度的为都获取的到技能信息的
    /*
    if($len==8) {
     $a = array(1, 1, 1, 1, 1, 0, 1);
    }else{
      $a = array_fill(0, $len, 1);      //0到$len在数组中填充1
      $a = array_pad($a, 9, 0);
    }
    */
    $a=array(1,1,1,1,1,1,0,0);  //暂时使用
    // 把该数组以json的格式返回给ajax
    $this->ajaxReturn($a);
  }
  
  //通过openid获取到经纬度
  private function getlocation($openId) {
    $where['event'] = 'LOCATION';
    $where['fromusername'] = $openId;
    $res = M('msg_main')->where($where)->order('location_x desc')->find();
    $location['location_x']=$res['location_x'];
    $location['location_y']=$res['location_y'];
    return $location;
  }
  
  public function setCity() {
    //异步
    $data['city']   = I('post.city', '', 'sql_filter');   //获取城市
    $data['openId'] = I('post.id', '', 'sql_filter');     //获取openid
    $data['upDate'] = time();                             //获取当前时间
    
    //查找该用户是否在表中
    $res = M('artisans_user_city')->where(array('openId'=>$data['openId']))->find();
    if($res) {
      //如果在表中更新一下内容
      M('artisans_user_city')->where(array('openId'=>$data['openId']))->save($data);
    }else{
      M('artisans_user_city')->where(array('openId'=>$data['openId']))->add($data);
    }
    exit;
  }
  
  public function index() {
    $kaquan = 500;
    
    if(I('get.source', '', 'sql_filter')) {
      $source = array(
        'source_value'=>I('get.source', '', 'sql_filter'),
        'source_link' =>$_SERVER['HTTP_REFERER'],
        'udate'       =>time();
      );
      M('artisans_index_source')->add($source);
    }
    
    if(I('get.source', '', 'sql_filter')=='kaquan') {
      $kaquan = 200;
      $getinfo = 'source/kaquan';
      $getinfo = '?getinfo='.$getinfo;
    }
    
    if(I('code')) {
      $code = I('code');
      $shop = D('WeiXinApi');
      $userinfo = $shop->getOAuthAccessToken($code);
      $openid = $userinfo['openid'];
    }else{
      if($getinfo) {
        $openid = $this->reGetOAuthDebug(U('Craft/index').$getinfo);
      }else{
        $openid = $this->reGetOAuthDebug(U('Craft/index'));
      }
    }
    
    // 通过openid来查找用户的默认对应城市表
    $res=M('artisans_user_city')->where(array('openId'=>$openid))->find();
    if($res) {
      // 表中找到后状态为1 城市为表中找到的城市
      $city['status'] = 1;
      $city['city']   = $res['city'];
    }else{
      $city['status'] = 0;
    }
    
    $location = $this->getlocation($openid);  // 通过openid 获取到 经纬度
    $this->assign('location', $location);     //把经纬度分配到模板中
    
    //判断用户有没有关注过公众号
    $target = D('UserInfo')->isFans($openid);
    if($target) {
      $target = 100;
    }else{
      $target = 0;
    }
    
    //服务是否打折，以及价格
    $modulesInfo = M('artisans_modules')->select();
    $ttime = date('Y-m-d H:i:s');
    foreach((array)$modulesInfo as $val) {
      if($val['startTime']<=$ttime && $val['endTime'] >= $ttime) {
        $pp = 100;
        $basePrice    = $val['endPrice'];
        $discount_txt = $val['discount']/10;      //显示折扣信息
      }else{
        $basePrice    = $val['price'];
        $pp = 200;
      }
      if($val['id'] == 1) {
        $tmp[1]['price']    = $basePrice;
				$tmp[1]['endPrice'] = $val['price'];
				$tmp[1]['target']   = $pp;
      }elseif($val['id'] == 6) {
        $tmp[0]['price']        = $basePrice;
				$tmp[0]['endPrice']     = $val['price'];
				$tmp[0]['target']       = $pp;
				$tmp[0]['discount_txt'] = $discount_txt;
      }elseif($val['id'] == 7) {
        $tmp[2]['price']    = $basePrice;
				$tmp[2]['endPrice'] = $val['price'];
				$tmp[2]['target']   = $pp;
      }elseif($val['id'] == 8) {
        $tmp[3]['price']    = $basePrice;
				$tmp[3]['endPrice'] = $val['price'];
				$tmp[3]['target']   = $pp;
      }elseif($val['id'] == 9) {
        $tmp[4]['price']    = $basePrice;
				$tmp[4]['endPrice'] = $val['price'];
				$tmp[4]['target']   = $pp;
      }elseif($val['id'] == 10) {
        $tmp[5]['price'] = $basePrice;
				$tmp[5]['endPrice']=$val['price'];
				$tmp[5]['target'] = $pp;
			}elseif($val['id'] == 11) {
			  $tmp[6]['price'] = $basePrice;
				$tmp[6]['endPrice']=$val['price'];
				$tmp[6]['target'] = $pp;
			}elseif($val['id'] == 12) {
			  $tmp[7]['price'] = $basePrice;
				$tmp[7]['endPrice']=$val['price'];
				$tmp[7]['target'] = $pp;
			}
    }
    $this->share_url = "http://localhost/".C("TP_PROJECT_NAME")."/index.php/Craft/index";
    $this->imgUrl    = "http://localhost/".C("TP_PROJECT_NAME")."/Public/Images/lnv_qcs/share.png";
    
    $this->assign('kaquan',$kaquan);
    $this->assign('city',$city);                //$city['status'] 为1是有默认城市  为0为没有默认城市 需要GPS定位
    $this->assign('modulesInfo',$modulesInfo);
    $this->assign('discountInfo',$tmp);
    $this->assign('target',$target);
    $this->assign('openid',$openid);
    $this->assign('openId',$openid);
    $this->assign('pageHome','craftIndx');
    $this -> display('qcs_index');
  }
  
  // 分享签名 (如果使用微信jssdk 请在 lib/widget/WXSDKWidget.class.php中修改)
  public function get_signature($data) {
    ksort($data);
    $signature = sha1(urldecode(http_build_query($data)));
    if(empty($signature)) {
      $info = D('Token');
      $token = $info->getToken();
    }
    return $signature;
  }
  
  public function get_ticket() {
    $js_ticket = D('Share');
    $jsapi_ticket = $js_ticket->getJsticket();
    return $jsapi_ticket;
  }
  
  //预约时间页
  public function dt() {
    $getData  = I('get.', '', 'sql_filter');
    $moduleId = (int)$getData['moduleId'];
    $openid   = $getData['openid'];
    $city     = $getData['city'];
    //显示日期，显示最近12天
    $date_arr = $this->showweek();
    $time_s = array('10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00');
    //获取服务名称
    $data = M('artisans_service_modules')->where(array('moduleId'=>$moduleId))->find();
		$serviceName = $data['serviceName'];
		
		$description = M('artisans_modules')->find($data['moduleId']);
		$ttime = date('Y-m-d H:i:s');
		if($description['startTime'] <= $ttime && $description['endTime'] >= $ttime) {
		  $description['status'] = 100;
		  $description['failPrice'] = $description['endPrice'];
		}else{
		  $description['status'] = 200;
		  $description['failPrice'] = $description['endPrice'];
		}
		$this->appid = C("APP_ID");
		
		$this->assign('description',$description);
		$this->assign('user_id',$openid);
		$this->assign('city',$city);
		$this->assign('moduleId',$moduleId);
		$this->assign('serviceName',$serviceName);
		$this->assign('date_arr', json_encode($date_arr));
		$this->assign('time_arr', json_encode($time_s));
		$this->assign('openId', $openid);
		$this->assign('pageHome', 'craftDt');
		
		$this->display('qcs_dt');
	}
	
	// 产品详情页面
	public function proDetails() {
	  //获取moduleId
	  $moduleId = I('get.moduleId', '', 'sql_filter');
	  
	  //获取用户的openid
	  $openid = I('get.openid', '', 'sql_filter');
	  
	  //获取城市的信息
	  $city = I('get.city', '', 'sql_filter');
	  
	  //app端调取产品详情页
	  $source = I('get.source', '', 'sql_filter');
	  
	  //通过moduleId来查找对应模块的数据
	  $res = M('artisans_modules')->find($moduleId);
	  
	  // 获得banner图的到多个图片路径的以及信息
	  $bannerImg = M('artisans_modulse_img')->where(array('moduleId'=>$moduleId))->select();
	  
	  // 分割产品介绍
	  $res['proIntro'] = $res['proIntro']==''?'':explode('◎',$res['proIntro']);
	  
	  // 分割产品亮点
	  $res['proSpecial'] = $res['proSpecial']==''?'':explode('◎',  $res['proSpecial']);
	  
	  // 分割产品优势
	  $res['proAdvan']=$res['proAdvan']==''?'':explode('◎',$res['proAdvan']);
	  
	  // 分割产品承诺
	  $res['proMise']=$res['proMise']==''?'':explode('◎',$res['proMise']);
	  
	  // 分割用户须知
	  $res['userInstr']=$res['userInstr']==''?'':explode("◎",$res['userInstr']);
	  
	  // 判断是否为促销时间和促销价格
	  $ttime = date('Y-m-d H:i:s');
	  if($res['startTime']<=$ttime && $res['endTime'] >= $ttime) {
	    $res['status']    = 100;                         //有促销活动时状态为100
	    $res['failPrice'] = $res['endPrice'];            //最终价格为折后价格
	    $res['discount']  = (int)$res['discount']/10;    //显示折扣信息
	  }else{
	    $res['status']    = 200;                        //没有促销活动时状态为200
	    $res['failPrice'] = $res['price'];              //最终价格为原价
	  }
	  
	  $this->assign('bannerImg',$bannerImg);
	  $this->assign('description', $res);     //把该模块的信息分配到前台页面中去
	  $this->assign('source', $source);
	  
	  $this->display('qcs_intro');            //显示前台页面
	}
	
	//提示错误页
	public function noCityError(){
		$getData = I('get.','','sql_filter');
		$num  = M('artisans_point_log')->where(array('city'=>$getData['city']))->count();
		$pnum = M('artisans_point_log')->where(array('openId'=>$getData['user_id']))->count();
		$num  = $num>0? (int)$num:0;
		$num  = $num+156;
		
		$this->assign('pnum',$pnum);
		$this->assign('getData',$getData);
		$this->assign('openid',$getData['user_id']);
		$this->assign('num',$num);
		$this->display('qcs_error');
  }
  
  public function changeCity() {
    $this->assign('getData', I('get.', '', 'sql_filter'));
    $this->display('qcs_change_city');
  }
  
  //ajax
  public function addpointnum() {
    $postData       = I('post.', '', 'sql_filter');
    $data['city']   = $postData['city'];
    $data['openId'] = $postData['user_id'];
    $data['cdate']  = date('Y-m-d H:i:s');
    if($data['openId']) {
      $pnum = M('artisans_point_log')->where(array('openId'=>$data['openId']))->count();
      if($pnum > 0) {
        $status = 0;
      }else{
        $id = M('artisans_point_log')->add($data);
        if($id) {
          $status = 200;
        }else{
          $status = 0;
        }
      }
    }else{
      $status = 0;
    }
    return json_encode(array('status'=>$status)));
  }
  
  public function showalltime($moduleId) {
    $where = array(
      'startTime' => array('egt', date('Y-m-d 00:00:00')),
      'endTime'   => array('elt', date('Y-m-d 23:59:59')),
      'moduleId'  => $moduleId,
    );
    $reservation_time = M('artisans_time_price')->where($where)->group('startTime,endTime')->order('startTime asc')->field('startTime, endTime')->select();
    foreach($reservation_time as $row) {
      $target = true;
      $i = 0;
      while($target) {
        $tmp_time = strtotime($row['startTime']) + 3600*$i;
        if($tmp_time > strtotime($row['endTime'])) {
          $target = false;
        }else{
          $time_arr[] = $tmp_time;
          $i++;
        }
      }
    }
    $tmp_arr = $time_arr;
    $maxtime = array_pop($tmp_arr);
    //寻找丢失的时间点
    $target = true;
    $i = 0;
    while($target) {
      $tmp_time = $time_arr[0] + 3600*$i;
      if($tmp_time > $maxtime) {
        break;
      }else{
        $time_s[] = date('H:i', $tmp_time);
        $i++;
      }
    }
    unset($time_arr);
    return $time_s;
  }
  
  //显示日期，显示最近12天
  public function showweek() {
    $now = time();
    $week_arr = array("日","一","二","三","四","五","六");
    for($i=0; $i<12; $i++) {
      $tmp_time    = time() + 86400 * $i;
      $tmp['date'] = date('Y-m-d', $tmp_time);
      $tmp['week'] = '周'.$week_arr[date('w', $tmp_time)];
      $date_arr[]  = $tmp;
    }
    return $date_arr;
  }
  
  //ajax  预约时间的某一天各个点状态
  public function ajdtDay() {
    $postData = I('post.', '', 'sql_filter');
    $date_arr = explode('周', trim($postData['time']));
    $date = $date_arr[0];
    $moduleId = $postData['moduleId'];
		$userId = $postData['userId'];
		$city = $postData['city'];
		$city = str_replace('市','',$city);
		$data = $this->alltimeStatus($date,$moduleId,$city,$userId);
		if($data){
			$return_data = array('status'=>200,'data'=>$data);
		}else{
			$return_data = array('status'=>0,'data'=>array(0,0,0,0,0,0,0,0,0,0,0,0));
		}
		return json_encode($return_data);
  }
  
  public function alltimeStatus($date, $moduleId, $city, $userId='') {
	$where = array(
		'a.startTime' => array('egt', date('Y-m-d 00:00:00', strtotime($date))),
		'a.endTime'   => array('elt', date('Y-m-d 23:59:59', strtotime($date))),
		'a.moduleId'  => $moduleId,
	);
	if($userId) {
		$where['a.userId'] = $userId;
	}
	if($city) {
		$where['b.city']   = $city;
	}
	$reservation_time = M()->table('wx_artisans_user_baseinfo as b')
			       ->join('wx_artisans_time_price as a on a.userId = b.id')
			       ->where($where)
			       ->group('a.startTime, a.endTime')
			       ->field('a.startTime as startTime, a.endTime as endTime, sum(a.`NouseNum`) as totalnum')
			       ->select();
	if($rservation_time) {
		foreach($reservation_time as $row){
			//可以预约的时间段, 切割的时间点,按一个小时切割
			if($row['totalnum'] > 0) {
				$target = true;
				$i = 0;
				while($target) {
					$tmp_time = strtotime($row['startTime'] + 3600*$i)
					if($tmp_time > strtotime($tow['endTime'])) {
						$target = false;
					}else{
						$use_time[] = $tmp_time;
						$i++;
					}
				}
			}
		}
		
		//寻找丢失的时间点
		$mintime = strtotime(date('Y-m-d 10:00:00', strtotime($date)));
		for($i=0;$i<12;$i++) {
			$tmp_time = $mintime + 3600*$i;
			//3小时之后
			if(in_array($tmp_time, $user_time) && ($tmp_time-time()>10800)) {
				$num_arr[] = 1;
			}else{
				$num_arr[] = 0;
			}
		}
	}else{
		$num_arr = array(0,0,0,0,0,0,0,0,0,0,0,0);
	}
	return $num_arr;
  }
  
  //ajax 预约时间临时表
  public function choosetimetmp() {
  	$postDate = I('post.', '', 'sql_filter');
  	$day_arr  = explode('周', $postData['day']);
  	$data['moduleId'] = $postData['moduleId'];
  	$data['openId']	  = $postData['user_id'];
  	$data['reservationTime'] = date('Y-m-d H:i:s', strtotime($day_arr[0].' '.trim($postData['time']).':00'));
  	$data['address']  = $postData['address'];
  	$data['lat']	  = $postData['lat'];
  	$data['lng']	  = $postData['lng'];
  	$data['cdate']	  = date('Y-m-d H:i:s');
  	//为朋友预约
  	$data['type']	  = (int)$postData['type'];
  	//判断该城市是否开通了该技能
  	$city = str_replace('市','',$postData['city']);
  	$moduleIdStr = M('artisans_city')->where(array('city'=>$city, 'status'=>1))->getField('moduleIdStr');
  	if(strpos($moduleIdStr, $data['moduleId']) === false) {
  		$returnData = array('status'=>300, 'errormsg'=>'亲，您所选择的地址，所在城市还未开通服务，敬请期待。');
  		return json_encode($returnData);
  	}
  	
  	if(empty($data['type'])||$data['type']=='0') {
  		$id = M('artisans_reservation_log')->add($data);
  		if($id) {
  			$returnData = array('status'=>200, 'tableid'=>0);
  		}else{
  			$returnData = array('status'=>0);
  		}
  		return json_encode($returnData);
  	}elseif($data['type'] == 1) {
  		//ajax 为朋友预约时间临时表
  		$data['friendName']    = $postData['name'];
  		$data['friendPhone']   = $postData['phone'];
  		$data['detailAddress'] = $postData['addressdetail'];
  		$data['shortmessage']  = $postData['wish'];
  		$data['way']	       = '上门';
  		$id = M('artisans_reservation_log')->add($data);
  		if($id) {
  			$returnData = array('status'=>200, 'tableid'=>$id);
  		}else{
  			$returnData = array('status'=>0);
  		}
  		return json_encode($returnData);
  	}
  }
  
  //ajax 预约时间临时表
  public function chooseArtisanstmp() {
  	$postData = I('post.', '', 'sql_filter');
  	$data['moduleId'] = $postData['moduleId'];
  	$data['openId']   = $postData['user_id'];
  	$data['reservationTime'] = date('Y-m-d H:i:s', strtotime(trim($postData['day']).' '.trim($postData['time']).':00'));
  	$data['address']  = $postData['address'];
  	$data['lat']	  = $postData['lat'];
  	$data['lng']	  = $postData['lng'];
  	$data['userId']   = $postData['userId'];
  	$data['cdate']	  = date('Y-m-d H:i:s');
  	$source 	  = $postData['source'];
  	if($source == 100) {
  		$data['type'] = 1;
  	}
  	$status = true;
  	if($status) {
  		$id = M('artisans_reservation_log')->add($data);
  	}
  	if($id) {
  		$returnData = array('status'=>200, 'tableid'=>$id);
  	}else{
  		$returnData = array('status'=>0);
  	}
  	return json_encode($returnData);
  }
	
  //列表页
  public function systemList() {
  	$getData = I('get.', '', 'sql_filter');
  	$get['moduleId'] = $moduleId = $getData['moduleId'];
  	$day_arr = explode('周', $getData['day']);
  	$get['time'] = $time = date('Y-m-d H:i:s', strtotime($day_arr[0].' '.trim($getData['time'].':00')));
  	$get['lat']  = $lat  = $getData['lat'];
  	$get['lng']  = $lng  = $getData['lng'];
  	$get['user_id'] = $getData['user_id'];
	$get['type'] 	= $getData['type'];
	$get['tableid'] = $getData['tableid'];
	$get['city'] 	= $getData['city'];
	
	$this->assign('get', $get);
	$this->assign('openId', $get['user_id']);
	$this->assign('pageHome', 'craftsSystemList');
	
	$this->display('qcs_list_spa');
  }
  
  //ajax 列表页
  public function ajsystemList(){
  	$postData = I('post.','','sql_filter');
  	$type     = $postData['type']?$postData['type']:0;	//0综合排序，1距离，2人气，3价格
  	$page	  = $postData['page']>0?$postData['page']:1;	//当前页
  	$per_page = $postData['per_page']?$postData['per_page']:10;	//显示条数
  	$moduleId = $postData['moduleId'];
  	$time 	  = $postData['time'];
  	$lat	  = $postData['lat'];
  	$lng	  = $postData['lng'];
  	$limit_start = ($page-1)*$per_page;
  	$limit_end   = $page*$per_page;
  	$openid   = $postData['user_id'];
  	$selecttype  = $postData['selecttype'];	//位自己预约还是为朋友预约
  	$city     = $postData['city'];
  	$city     = str_replace('市','',$city);
  	//获取该模快基础价
  	$baseinfo = M('artisans_modules')->where(array('id'=>$moduleId))->find();
	$ttime = date('Y-m-d H:i:s');
	if($baseinfo['startTime']<=$ttime && $baseinfo['endTime'] >= $ttime){
		$basePrice = $baseinfo['endPrice'];
	}else{
		$basePrice = $baseinfo['price'];
	}
  	$where = array(
  		'b.moduleId' => $moduleId,
  		'a.city'     => $city,
  	);
  	$userInfo = M()->table('wx_artisans_user_baseinfo as a')
  		       ->join('wx_artisans_modules_user as b on a.id = b.userId')
  		       ->where($where)
  		       ->field('distinct a.`id` as userId, userName, trueName, headImg, source, lat, lng, a.goodRate as goodRate, a.serviceNum as serviceNum')->select();
  	if($userInfo) {
  		foreach($userInfo as $value) {
  			$tmp_where = array(
  				'moduleId' => $moduleId,
  				'userId'   => $value['userId'],
  				'startTime'=> array('elt', $time),
  				'endTime'  => array('egt', $time),
  			);
  			$tmp = M('artisans_time_price')->where($tmp_where)->field('startTime, endTime, price, status, nouseNum')->find();
  			$value['startTime'] = $tmp['startTime'];
  			$value['endTime']   = $tmp['endTime'];
  			$value['price']     = $tmp['price']>0? $tmp['price']:0;
  			$value['status']    = $tmp['status']>0?1:0;
  			$value['nouseNum']  = $tmp['nouseNum']>0?$tmp['nouseNum']:0;
  			$jsondata['name']   = $value['trueName'];
  			$jsondata['avatar'] = $value['headImg'];
  			$jsondata['school'] = $value['source'];
  			$jsondata['goodRate'] = $value['goodRate'];
  			$jsondata['good']   = $value['goodRate'].'%';
  			$jsondata['distance_s'] = ceil(round($this->getDistance($lat, $lng, $value['lat'], $value['lng']), 1));
  			//距离格式
  			$dis_where = array(
  				'moduleId'=>$moduleId,
  				'userId'  =>$value['userId'],
  				'minDistance'=>array('elt', $jsondata['distance_s']),
  				'maxDistance'=>array('egt', $jsondata['distance_s']),
  				'status'=>1
  			);
  			$price = $jsondata['distance_s']*2;
  			if($baseinfo['startTime']<=$ttime && $baseinfo['endTime']>=$ttime) {
  				$discount = $baseinfo['discount']/10;
  				$jsondata['my_msg'] = "(原价￥".$baseinfo['price']."元)";
  			}else{
  				$josndata['my_msg'] = "(上门费￥".$price.")";
  			}
  			$distance_price = M('artisans_distance_price')->where($dis_where)->getField('price');
  			$distance_price = $distance_price>0? $distance_price:0;
  			//时间价
  			if($value['status'] == 1) {
  				$time_price = $value['price'];
  			}else{
  				$time_price = 0;
  			}
  			//个人加成
  			$dis_where = array(
  				'moduleId'=>$moduleId,
  				'userId'  =>$value['userId'],
  				'status'  =>1
  			);
  			$tip_price = M('artisans_tip_price')->where($dis_where)->getField('price');
			$tip_price = $tip_price>0? $tip_price:0;
			//标准价 = 基础价+时间价+距离价+加成价
			$jsondata['price_s'] = $basePrice + $distance_price + $time_price + $tip_price;
			$jsondata['price'] = $jsondata['price_s'];
			$jsondata['distancePrice'] = sprintf("%02.2f",$distance_price);
			$jsondata['distance'] = $jsondata['distance_s'];
			//服务过的单数
			$jsondata['ordered'] = $value['serviceNum']>0? (int)$value['serviceNum']:50;
			if(empty($selecttype)) {
				$jsondata['url'] = U('Craft/systemDetail')."?user_id=".$openid."&moduleId=".$moduleId."&userId=".$value['userId']."&price=".$jsondata['price_s']."&distanceprice=".$distance_price."&ordernum=".$jsondata['ordered']."&distance=".$jsondata['distance_s'];	//详情页	
			}else{
				$jsondata['url'] = U('Craft/systemDetail')."?source=100&tableid=".$postData['tableid']."&user_id=".$openid."&moduleId=".$moduleId."&userId=".$value['userId']."&price=".$jsondata['price_s']."&distanceprice=".$distance_price."&ordernum=".$jsondata['ordered']."&distance=".$jsondata['distance_s']; //详情页
			}
			$jsondata['choose_url'] = U('Craft/submitUserinfo').'?openid='.$openid.'&moduleId='.$moduleId.'&userId='.$value['userId'].'&source_type=1&price='.$jsondata['price_s']; //填写信息页
			if($value['nouseNum']>0 && (strtotime($value['endTime'])-time()) > 10800) {
				$jsondata['disabled'] = 'able';
				$jsondata['btntxt']   = '约TA';
				$chooseGoodrate[]     = $value['goodRate'];
				$choosePrice[]	      = $jsondata['price_s'];
				$chooseDistance[]     = $jsondata['distance_s'];
				$chooseHot[]	      = $jsondata['ordered'];
				$chooseInfo[]	      = $jsondata;
			}else{
				$jsondata['choose_url'] = 'javascript:void(0)';
				$jsondata['disabled']   = 'disable';
				$jsondata['btntxt']	= '约满';
				$nochooseGoodrate[]	= $value['goodRate'];
				$nochoosePrice[]	= $jsondata['price_s'];
				$nochooseDistance[]	= $jsondata['distance_s'];
				$nochooseHot[]		= $jsondata['ordered'];
				$nochooseInfo[]		= $jsondata;
			}
  		}
  		if($type == 1){
  			//按距离 好评率
  			array_multisort($chooseDistance, SORT_ASC, SORT_NUMERIC, $chooseGoodrate, SORT_DESC, SORT_NUMERIC, $chooseInfo);
  			array_multisort($nochooseDistance, SORT_ASC, SORT_NUMERIC, $nochooseGoodrate, SORT_DESC, SORT_NUMERIC, $nochooseInfo);
  		}elseif($type == 2){
  			//按人气 好评率
  			array_multisort($chooseHot, SORT_DESC, SORT_NUMERIC, $chooseGoodrate, SORT_DESC, SORT_NUMERIC, $chooseInfo);
  			array_multisort($nochooseHot, SORT_DESC, SORT_NUMERIC, $nochooseGoodrate, SORT_DESC, SORT_NUMERIC, $nochooseInfo);
  		}elseif($type == 3){
  			//按价格 好评率
  			array_multisort($choosePrice, SORT_ASC, SORT_NUMERIC, $chooseGoodrate, SORT_DESC, SORT_NUMERIC, $chooseInfo);
  			array_multisort($nochoosePrice, SORT_ASC, SORT_NUMERIC, $nochooseGoodrate, SORT_DESCm SORT_NUMERIC, $nochooseInfo);
  		}else{
  			//综合排序	1.好评率 降序  2，价格升序
  			array_multisort($chooseDistance, SORT_ASC, SORT_NUMERIC, $chooseGoodrate, SORT_DESC, SORT_NUMERIC, $chooseInfo);
  			array_multisort($nochooseDistance, SORT_ASC, SORT_NUMERIC, $nochooseGoodrate, SORT_DESC, SORT_NUMERIC, $nochooseInfo);
  		}
  		$Info = array_merge((array)$chooseInfo, (array)$nochooseInfo);
  		//取数
  		$totalnum = count($Info);
  		for($limit_start;($limit_start<$limit_end && $limit_start<=$totalnum-1);$limit_start++) {
  			$data[] = $Info[$limit_start];
  		}
  		if($data){
  			return json_encode(array('status'=>200, 'data'=>$data));
  		}eles{
  			return json_encode(array('status'=>0));
  		}
  	}else{
  		return json_encode(array('status'=>0));
  	} 
  }
  
  //为朋友预约支付页
  public function forfriendpay() {
  	$getData = I('get.', '', 'sql_filter');
  	$getinfo = 'userId='.$getData['userId'].'**source='.$getData['source'].'**tableid='.$getData['tableid'];
  	if(I("code")) {
  		$code = I("code");
  		$shop = D("WeiXinApi");
  		$userinfo = $shop->getOAuthAccessToken($code);
  		$openid = $userinfo["openid"];
  	}else{
  		if(C('ProductStatus')===false){
  			$openid = $this->reGetOAuthDebug("/weixin/index.php/shop/wxceshi_forfriendpay?getinfo=".$getinfo);
  		}else{
  			$openid = $this->reGetOAuthDebug("/weixin/index.php/shop/shouyi_forfriendpay?getinfo=".$getinfo);
  		}
  	}
  	if(APP_DEBUG) {
  		$userId = $getData['userId'];
  		$source = $getData['source'];
  		$tableId = $getData['tableid'];
  	}else{
  		$getinfo = I('get.getinfo', '', 'sql_filter');
  		$getinfo = explode('**', $getinfo);
  		foreach($getinfo as $key=>$value) {
  			$tmp = array();
  			$tmp = explode('=', $value);
  			if($tmp[0] == 'userId') {
  				$userId = $tmp[1];
  			}elseif($tmp[0] == 'source') {
  				$source = $tmp[1];
  			}elseif($tmp[0] == 'tableid') {
  				$tableId = $tmp[1];
  			}
  		}
  	}
  	$log_where = array(
  		'id'=>$tableId,
  		'openId'=>$openid,
  		'type'=>1,
  	);
  	$reservation_log = M('artisans_reservation_log')->where($log_where)->find();
  	$getData['moduleId'] = $reservation_log['moduleId'];
  	$getData['type']     = 1;	//默认为1
  	if($reservation_log['id']) {
  		//添加到submitinfo
  		$data['moduleId'] = $reservation_log['moduleId'];
  		$data['openId']   = $reservation_log['openId'];
  		$data['reservationTime'] = $reservation_log['reservationTime'];
  		$data['address'] = $reservation_log['address'];
		$data['cdate'] = date('Y-m-d H:i:s');
		$data['type'] = 1;
		$data['shortmessage'] = $reservation_log['shortmessage'];
		$data['friendPhone'] = $reservation_log['friendPhone'];
		$data['friendName'] = $reservation_log['friendName'];
		$data['detailAddress'] = $reservation_log['detailAddress'];
		$data['way'] = $reservation_log['way'];
		if($source == 100){
			$data['userId'] = $userId;
		}else{
			$data['userId'] = $reservation_log['userId'];
		}
		//以下为价格计算
		$artisansInfo = M('artisans_user_baseinfo')->where(array('id'=>$data['userId']))->find();
		//模块基础价
		$baseinfo = M('artisans_modules')->where(array('id'=>$reservation_log['moduleId']))->find();
		$ttime = date('Y-m-d H:i:s');
		if($baseinfo['startTime']<=$ttime && $baseinfo['endTime'] >= $ttime) {
			$basePrice = $baseinfo['endPrice'];
		}else{
			$basePrice = $baseinfo['price'];
		}
		$jsondata['distance_s'] = round($this->getDistance($reservation_log['lat'],$reservation_log['lng'],$artisansInfo['lat'],$artisansInfo['lng']));
			//距离价格
			$dis_where = array(
					'moduleId'=>$reservation_log['moduleId'],
					'userId'=>$data['userId'],
					'minDistance'=>array('elt',$jsondata['distance_s']),
					'maxDistance'=>array('egt',$jsondata['distance_s']),
					'status'=>1
			);
			$distance_price = M('artisans_distance_price')->where($dis_where)->getField('price');
			$distance_price = $distance_price>0? $distance_price:0;
			
			//时间价
			$time_where = array(
					'moduleId'=>$reservation_log['moduleId'],
					'userId'=>$data['userId'],
					'startTime'=>array('elt',$reservation_log['reservationTime']),
					'endTime'=>array('egt',$reservation_log['reservationTime']),
					'status'=>1
			);
		$time_price = M('artisans_distance_price')->where($time_where)->getField('price');
		$time_price = $time_price>0? $time_price:0;
		//个人加成
			$dis_where = array(
					'moduleId'=>$reservation_log['moduleId'],
					'userId'=>$data['userId'],
					'status'=>1
			);
			$tip_price = M('artisans_tip_price')->where($dis_where)->getField('price');
			$tip_price = $tip_price>0? $tip_price:0;
		//标准价 = 基础价+时间价+距离价+加成价
		$price = $basePrice + $distance_price + $time_price + $tip_price;
		$data['price'] = $price;
		$id = M('artisans_submitinfo')->add($data);
		if($id) {
			//支付需要的参数
			$conf['appid'] = C("APPID");
			$conf['partnerkey'] = C("PARTNERKEY");
			$conf['partnerid']  = C("PARTNERID");
			$conf['appkey']	    = C("PAYSIGNKEY");
			$cardinfo['money']  = 0;
			$cardinfo['cardid'] = '';
			$cardinfo_status    = false;
			//获取价格
			$cardid = $this->_cardid[$data['moduleId']];
			if($cardid) {
				$getcard_artisans_url  = $this->_artisans_url.$cardid.'&openid='.$openid;
				$getcard_artisans_info = send_get_curl($getcard_artisans_url);
				$getcard_artisans_info = json_decode($getcard_artisans_info, true);
				$today = date('Y-m-d H:i:s');
				if($getcard_artisans_info['error_code'] === 0) {
					if($getcard_artisans_info['data']['card']['start_time']<$today && $getcard_artisans_info['data']['card']['end_time']>$today 
					   && 
					   $getcard_artisans_info['data']['card']['state'] == 100 
					   &&
					   $getcard_artisans_info['data']['card_codes'][0]['cost_count'] === "0") {
					   	$cardinfo['money'] = $price;
					   	$cardinfo['cardid'] = '111';
					   	$cardinfo_status = true;
					   }
				}
			}
			if($cardinfo_status) {
				if($type == 2) {
					$price = $tipInfo['package1'];
				}elseif($type == 3) {
					$price = $tipInfo['package2'];
				}
			}
			
			$this->assign('cardinfo', $cardinfo);
			$this->assign('openid',$openid);
			$this->assign('price',$price);
			$this->assign('getData',$getData);
			$this->assign('conf',$conf);
		}
  	}
  	$this->assign('openId',$openid);
  	$this->assign('pageHome','craftSelectCard');
	
  	$this->display('qcs_choose_card2');
  }
  
  public function systemDetail() {
  	$getData = I('get.','','sql_filter');
	$moduleId = $getData['moduleId'];
	$userId = $getData['userId'];
	$openid = $getData['user_id'];
	//显示日期，显示最近12天
	$date_arr = $this->showweek();
	//切割当天的时间点
	$time_s = array('10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00');
	//微信用于选择的时间
	$selectTimeInfo = M('artisans_reservation_log')->where(array('openId'=>$openid,'moduleId'=>$moduleId))
						       ->order('cdate desc')
						       ->find();
	$activeDay = (strtotime(date('Y-m-d 00:00:00', strtotime($selectTimeInfo['reservationTime']))) - strtotime(date('Y-m-d 00:00:00')))/86400;
	$active['activeDay'] = $activeDay >= 0?(int)$activeDay:0;
	$activeTime = array_filp($time_s)[date('H:i', strtotime($selectTimeInfo['reservationTime']))];
	$active['activeTime'] = $activeTime >= 0?(int)$activeTime:0;
	$active['lat'] =  $selectTimeInfo['lat'];
	$active['lng'] =  $selectTimeInfo['lng'];		
	$active['address'] =  $selectTimeInfo['address'];
	$artisansInfo = M('artisans_user_baseinfo')->where(array('id'=>$userId))->find();
	$artisansInfo['price_i'] = $getData['price'];
	$artisansInfo['price']   = $getData['price'];
	$artisansInfo['distancePrice'] = sprintf("%02.2f", $getData['distanceprice']);
	$artisansInfo['distance_txt']  = $getData['distance'].'公里';
	$artisansInfo['good']	       = $artisansInfo['goodRate'].'%';
	$artisansInfo['ordernum']      = $getData['ordernum'];
	$baseinfo = M('artisans_modules')->where(array('id'=>$moduleId))->find();
	$ttime = date('Y-m-d H:i:s');
	if($baseinfo['startTime']<=$ttime && $baseinfo['endTime'] >= $ttime) {
		$basePrice = $baseinfo['endPrice'];
		$discount  = $baseinfo['discount']/10;
		$del	   = "(原价￥".$baseinfo['price']."元)";
	}else{
		$basePrice = $baseinfo['price'];
		$distance_price=$getData['distance']*2;
		$del="(上门费￥".$distance_price.")";
	}
	
	//获取拥有的技能
	$res=M('artisans_modules_user')->where(array('userId'=>$userId))->select();
	foreach($res as $key=>$value) {
		$res[$key]['img'] = M('artisans_modules')->find($value['moduleId'])['classImg'];
	}
	
	$this->assign('module',$res);
	$this->assign('del',$del);
	$this->assign('del_price',$basePrice);
	$this->assign('userInfo',$artisansInfo);
	$this->assign('get',$getData);
	$this->assign('active',$active);
	$this->assign('user_id',$openid);
	$this->assign('openId',$openid);
	$this->assign('date_arr', json_encode($date_arr));
	$this->assign('time_arr', json_encode($time_s));
	$this->assign('pageHome','craftDetail');
	
	$this->display('qcs_detail');
  }
  
  //点评数据 ajax
  public function getcomments() {
  	$postData = I('post.','','sql_filter');
  	$where = array(
		'userId'=>$postData['userId'],
	);
	$postData['start'] = $postData['start']>0?$postData['start']:1;
	$postData['limit'] = $postData['limit']>0?$postData['limit']:5;
	$startnum = ($postData['start']-1)*$postData['limit'];
	$endnum = $postData['limit'];
	//用户点评数据
	$comments = M('artisans_comments')->where($where)
					  ->field('headImg as photo,nums as star,cdate as time,comments as text,name')
					  ->order('cdate desc')
					  ->limit("{$startnum}, {$endnum}")
					  ->select();
	if($comments) {
		$comment_arr = array('status'=>200, 'data'=>$comments);
	}else{
		$comment_arr = array('status'=>0);
	}
	return json_encode($comment_arr);
  }
  
  public function submitUserinfo() {
  	
  }
  
  public function createInfo() {
  	
  }
  
  //选择卡卷页
  public function selectCard() {
  	
  }
  
  public function systempay() {
	$this->display('qcs_have_card');
  }
  
  //支付成功页
  public function successPay() {
  	
  }

  //更新订单状态
  public function notice() {
  	
  }
  
  //查看订单支付状态是否更新成为成功
  public function updateOrder() {
  	
  }
  
  //查询微信那边是否生成成功订单的信息
  public function findwxorder() {
  	
  }
  
  //核销卡卷
  public function cleanQ($openid, $moduleId) {
  	
  }
  
  public function cleanKQ($openid,$cardid,$codeid) {
  
  }
  
  //推送消息
