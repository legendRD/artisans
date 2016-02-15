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
  	$getData = I('get.','','sql_filter');
  	$getinfo = 'moduleId='.$getData['moduleId'].'**source_type='.$getData['source_type'].'**userId='.$getData['userId'];
  	$appid	 = C("APP_ID");
  	$this->appid = $appid;
  	
  	if(I("code")) {
  		$code = I("code");
  		$shop = D("WeiXinApi");
  		$userinfo = $shop->getOAuthAccessToken($code);
  		$openid = $userinfo['openid'];
  	}else{
  		$openid = $this->reGetOAuthDebug(U("Craft/submitUserinfo").'?getinfo='.$getinfo);
  	}
  	if(APP_DEBUG) {
  		$moduleId = $getData['moduleId'];
  		$source_type = $getData['source_type'];
  		$userId = $getData['userId'];
  	}else{
  		$getinfo = I('get.getinfo','','sql_filter');
  		$getinfo = explode('**', $getinfo);
  		foreach($getinfo as $key=>$value) {
  			$tmp = explode('=', $value);
  			if($key == 0) {
  				$moduleId = $tmp[1];
  			}elseif($key == 1) {
  				$source_type = $tmp[1];
  			}elseif($key == 2) {
  				$userId = $tmp[1];
  			}
  		}
  	}
  	$where = array(
  		'openId'=>$openid,
  		'moduleId'=>$moduleId,
  	);
  	if($source_type == 1) {
  		$where['userId'] = 0;
  	}else{
  		$where['userId'] = $userId;
  	}
  	$ordertimeInfo = M('artisans_reservation_log')->where($where)->order('cdate desc')->find();
	$ordertimeInfo['userId'] = $userId;
	$artisansInfo = M('artisans_user_baseinfo')->where(array('id'=>$userId))->find();
	//模块基础价
	$baseinfo = M('artisans_modules')->where(array('id'=>$moduleId))->find();
	$ttime = date('Y-m-d H:i:s');
	if($baseinfo['startTime']<=$ttime && $baseinfo['endTime'] >= $ttime){
		$basePrice = $baseinfo['endPrice'];
	}else{
		$basePrice = $baseinfo['price'];
	}
	$jsondata['distance_s'] = round($this->getDistance($ordertimeInfo['lat'], $ordertimeInfo['lng'], $artisansInfo['lat'], $artisansInfo['lng']));
  	//距离价格
  	$dis_where = array(
  		'moduleId'=>$moduleId,
  		'userId'=>$userId,
  		'minDistance'=>array('elt', $jsondata['distance_s']),
  		'maxDistance'=>array('egt', $jsondata['distance_s']),
  		'status'=>1
  	);
  	$distance_price = M('artisans_distance_price')->where($dis_where)->getField('price');
	$distance_price = $distance_price>0? $distance_price:0;
	//时间价
	$time_where = array(
		'moduleId'=>$moduleId,
		'userId'=>$userId,
		'startTime'=>array('elt', $ordertimeInfo['reservationTime']),
		'endTime'=>array('egt', $ordertimeInfo['reservationTime']),
		'status'=>1
	);
	$time_price = M('artisans_distance_price')->where($time_where)->getField('price');
	$time_price = $time_price>0? $time_price:0;
	//个人加成
	$dis_where = array(
		'moduleId'=>$moduleId,
		'userId'=>$userId,
		'status'=>1
	);
	$tip_price = M('artisans_tip_price')->where($dis_where)->getField('price');
	$tip_price = $tip_price>0? $tip_price:0;
	//标准价 = 基础价+时间价+距离价+加成价
	$price = $basePrice+$distance_price+$time_price+$tip_price;
	$ordertimeInfo['price'] = $price;
	//用户手机号，姓名
	$wxUserinfo = M("BespokeRepair")->where(array('user_id'=>$openid))->order(' id desc ')->field('user_true_name,mobile')->find();
  	
  	$this->assign('wxUserinfo',$wxUserinfo);
	$this->assign('ordertimeInfo',$ordertimeInfo);
	$this->assign('openId',$openid);
	$this->assign('pageHome','craftSubmitOrder');
	
	$this->display('qcs_sbmit_order');
  }
  
  public function createInfo() {
  	$postData = I('post.','','sql_filter');
	$target = 200;		
	$data['type'] = $postData['type']==1? 1:0;
	if($postData['type'] == 1) {
			$data['moduleId'] = $postData['moduleId'];
			$data['openId'] = $postData['user_id'];
			$data['userId'] = $postData['userId'];
			$data['reservationTime'] = $postData['time'];
			$data['address'] = $postData['address_s'];
			$data['detailAddress'] = $postData['address'];
			$data['friendName'] = trim($postData['name']);
			$data['friendPhone'] = trim($postData['telephone']);
			$data['price'] = $postData['price'];
			$data['way'] = $postData['way'];
			$data['cdate'] = date('Y-m-d H:i:s');
			$data['shortmessage'] = $postData['wish'];	
	}else{
			$data['moduleId'] = $postData['moduleId'];
			$data['openId'] = $postData['user_id'];
			$data['userId'] = $postData['userId'];
			$data['reservationTime'] = $postData['time'];
			$data['address'] = trim($postData['address']);
			$data['name'] = trim($postData['name']);
			$data['phone'] = trim($postData['telephone']);
			$data['price'] = $postData['price'];
			$data['cdate'] = date('Y-m-d H:i:s');
	}
	$id = M('artisans_submitinfo')->add($data);
	if(!$id) {
		$target = 0;
	}
	return json_encode(array('status'=>$target));
  }
  
  //选择卡卷页
  public function selectCard() {
  	$getData = I('get.','','sql_filter');
  	if($getData['getinfo']) {
  		$getinfo = $getData['getinfo'];
  	}else{
  		$getinfo = 'moduleId='.$getData['moduleId'].'**type='.$getData['type'];
  	}
  	if($getData['redid']) {
  		$getinfo .= '**redid='.$getData['redid'];
  	}
  	if($getData['cardid']) {
  		$getinfo .= '**cardid='.$getData['cardid'];
  		$getinfo .= '**codeid='.$getData['codeid'];
  	}
  	$appid = C("APP_ID");
  	$this->appid = $appid;
  	if(I("code")) {
  		$code = I("code");
  		$shop = D("WeiXinApi");
  		$userinfo   = $shop->getOAuthAccessToken($code);
		$openid = $userinfo["openid"];
  	}else{
  		if(C('ProductStatus') === false) {
  			$openid = $this->reGetOAuthDebug("/weixin/index.php/shop/wxceshi_selectCard?getinfo=".$getinfo);
  		}else{
  			$openid = $this->reGetOAuthDebug("/weixin/index.php/shop/shouyi_selectCard?getinfo=".$getinfo);
  		}
  	}
  	if(APP_DEBUG && I('get.openid', '', 'sql_filter')) {
  		$moduleId = $getData['moduleId'];
  		$type = $getData['type'];
  	}else{
  		$getinfo = explode('**',$getinfo);
  		foreach($getinfo as $key=>$value) {
  			$tmp = explode('=', $value);
  			if($tmp[0] == 'moduleId') {
  				$moduleId = $tmp[1];
  			}elseif($tmp[0] == 'type') {
  				$type = $tmp[1];
  			}elseif($tmp[0] == 'redid') {
  				$redid = $tmp[1];
  				unset($getinfo[$key]);
  			}elseif($tmp[0] == 'codeid') {
  				$codeid = $tmp[1];
  				unset($getinfo[$key]);
  			}elseif($tmp[0] == 'cardid') {
  				$cardid = $tmp[1];
  				unset($getinfo[$key]);
  			}
  		}
  	}
  	
  	$getData['moduleId'] = $moduleId;
  	$getData['type']     = $type;
  	$getData['redid']    = $redid;
  	
  	//支付需要的参数
  	$conf['appid']      = C("APPID");
  	$conf['partnerkey'] = C("PARTNERKEY");
  	$conf['partnetid']  = C("PARTNERID");
  	$conf['appkey']	    = C("PAYSIGNKEY");
  	
  	//获取价格
  	$userOrderInfo = M('artisans_submitinfo')->where(array('moduleId'=>$moduleId, 'openId'=>$openid))->order('cdate desc')->find();
  	$price = $userOrderInfo['price'];
  	if($redid) {
  		//通过红包id来找到红包
  		$redbag = M('artisans_user_redbag')->where(array('to_id'=>$openid))->find($redid);
  		if($redbag['status']==0) {
  			$redbag_data = '{name:"代金券", money:"'.$redbag['cash'].'", id:".$redid."}';
  		}
  	}else{
  		$redbag_data = '{name:"卡券", money:0, id:''}';
  	}
  	
  	$cardinfo['money']  = 0;
  	$cardinfo['cardid'] = '';
  	$cardinfo_status    = false;
  	
  	if($cardid && $codeid) {
  		$getcard_artisans_url  = $this->_artisans_url.$cardid.'&openid='.$openid;
  		$getcard_artisans_info = send_get_curl($getcard_artisans_url);
  		$getcard_artisans_info = json_decode($getcard_artisans_info, true);
  		$today = date('Y-m-d H:i:s');
  		if($getcard_artisans_info['error_code'] === 0) {
  			if($getcard_artisans_info['data']['card']['start_time'] < $today && 
  			   $getcard_artisans_info['data']['card']['end_time'] > $today && 
  			   $getcard_artisans_info['data']['card']['state'] == 100 && 
  			   $getcard_artisans_info['data']['card_codes'][0]['cost_count'] === "0" && 
  			   $getcard_artisans_info['data']['card_codes'][0]['id'] == $codeid) {
  			   	$cardinfo['cash'] = $getcard_artisans_info['data']['card']['reduce_cost'];
  			   	$cardinfo['name'] = $getcard_artisans_info['data']['card']['title'];
  			   	$redbag_data = '{name:"体验券", money:"'.$cardinfo['cash'].'", id:"'.$cardid.'"}';
  			   	
  			   	$this->assign('cardid', $cardid);
  			   	$this->assign('codeid', $codeid);
  			   }
  		}
  	}
  	
  	$getinfo = implode('**', $getinfo);
  	
  	$this->assign('getinfo', $getinfo);
  	$this->assign('redbag_data',$redbag_data);
	$this->assign('cardinfo',$cardinfo);
	$this->assign('openid',$openid);
	$this->assign('price',$price);
	$this->assign('getData',$getData);
	$this->assign('conf',$conf);
	$this->assign('openId',$openid);
	$this->assign('pageHome','craftSelectCard');
	
	$this->display('qcs_choose_card2');
  }
  
  public function createOrderinfo() {
  	$postData  = I('post.','','sql_filter');
	$moduleId  = $postData['order_moduleId'];
	$type      = $postData['order_type'];
	$openid    = $postData['order_user_id'];
	$redbag_id = $postData['order_redbag_id'];
	$cardid    = $postData['order_card_id'];
	$codeid    = $postData['order_code_id'];
	$userOrderInfo = M('artisans_submitinfo')->where(array('moduleId'=>$moduleId,'openId'=>$openid))->order('cdate desc')->find();
	//判断这个时间点是否有
	$time_where = array(
		'moduleId'=>$moduleId,
		'startTime'=>array('elt',$userOrderInfo['reservationTime']),
		'endTime'=>array('egt',$userOrderInfo['reservationTime']),
		'userId'=>$userOrderInfo['userId'],
	);
	$timeInfo = M('artisans_time_price')->where($time_where)->find();
	if($timeInfo['nouseNum']<1) {
		$return_data = array('status'=>0,'message'=>'呀！你下手慢了吆，这个点已经被别人约了');
		return json_encode($return_data);
	}
	$price = $userOrderInfo['price'];
	//判断是不是有卡卷,有卡卷才能使用
	$today  = date('Y-m-d H:i:s');
	$getcard_status = false;
	if($cardid && $codeid) {
		$data['card_code_id']  = $cardid.'**'.$codeid;
		$getcard_artisans_url  = $this->_artisans_url.$cardid.'&openid='.$openid;
		$getcard_artisans_info = send_get_curl($getcard_artisans_url);
		$getcard_artisans_info = json_decode($getcard_artisans_info, true);
		if($getcard_artisans_info['error_code'] === 0) {
			if($getcard_artisans_info['data']['card']['start_time']<$today
				&& $getcard_artisans_info['data']['card']['end_time']>$today
				&& $getcard_artisans_info['data']['card']['state']==100
				&& $getcard_artisans_info['data']['card_codes'][0]['cost_count']==="0"
				&& $getcard_artisans_info['data']['card_codes'][0]['id']==$codeid
			) {
				$getcard_status = true;
				$cash = $getcard_artisans_info['data']['card']['reduce_cose'];
				$price = $price-$cash;
				$price = $price<0?0:$price;
			}
		}
	}
	
	$data['price'] = $price;
	$data['status']= 0;
	$data['useCardStatus'] = $getcard_status?1:0;
	$data['moduleId'] = $moduleId;
	$data['type'] = $type;
	$data['userId'] = $userOrderInfo['userId'];
	$data['openId'] = $openid;
	$data['name'] = $userOrderInfo['name'];
	$data['reservationTime'] = $userOrderInfo['reservationTime'];
	$data['address'] = $userOrderInfo['address'];
	$data['phone'] = $userOrderInfo['phone'];
	$data['cdate'] = date('Y-m-d H:i:s');
	$data['moduleName'] = M('artisans_modules')->where(array('id'=>$moduleId))->getField('name');
	//为朋友预约为start
	$data['forWho'] = $userOrderInfo['type'];
	$data['shortmessage'] = $userOrderInfo['shortmessage'];
	$data['friendPhone'] = $userOrderInfo['friendPhone'];
	$data['friendName'] = $userOrderInfo['friendName'];
	$data['detailAddress'] = $userOrderInfo['detailAddress'];
	$data['way'] = $userOrderInfo['way'];
	//判断红包是否属于这个人
	if($redbag_id) {
		$redbag_info = M('artisans_user_redbag')->where(array('id'=>$redbag_id,'to_id'=>$openid,'status'=>0))->find();
		$price = $price - $redbag_info['cash'];
		$price = $price>0?$price:0;
		$data['price'] = $price;
	}
	$data['redbag_id'] = $redbag_id;
	//为朋友预约end
	if($price == 0) {
		$data['status'] = 3;
	}
	$id = M('artisans_order')->add($data);
	if($id) {
		$data['ordernum'] = $id;
		if($price == 0) {
			//核销卡券
			$this->cleanKQ($openid,$cardid,$codeid);
			//减1
			$reduceTimenumid = $this->reduceTimenum($userOrderInfo['userId'],$userOrderInfo['reservationTime'],$moduleId);
			//更新红包id状态
			$this->_updateRedbag($redbag_id, $openid, $id);
			//推送消息
			$engineerId = M('artisans_user_baseinfo')->where(array('id'=>$userOrderInfo['userId']))->find();
			$artInfo = array(
				'artName'=>$engineerId['trueName'],
				'artPhone'=>$engineerId['phone'],
				'userName'=>$userOrderInfo['name'],
				'userPhone'=>$userOrderInfo['phone'],
				'time'=>$userOrderInfo['reservationTime'],
				'moduleName'=>$data['moduleName'],
				'userAddress'=>$userOrderInfo['address'],
				'ordernum'=>$id,
				'friendName'=>$userOrderInfo['friendName'],
				'friendPhone'=>$userOrderInfo['friendPhone'],
				'shortmessage'=>$userOrderInfo['shortmessage'],
				'detailAddress'=>$userOrderInfo['detailAddress'],
				'forWho'=>$userOrderInfo['forWho'],
			);
			$this->sendMessage($openid, $engineerId['openId'], $artInfo);
		}
		$data['price'] = $price*100;
		$return_data = array('status'=>200, 'data'=>$data);
	}else{
		$return_data = array('status'=>0, 'message'=>'生成订单失败!');
	}
	return json_encode($return_data);
  }
  
  private function _updateRedbag($redbag_id, $openid, $id) {
  	wlog($this->_redbag_log.date('Ymd').'.log','参数:id=>'.$id.',redbag_id=>'.$redbag_id.',openid=>'.$openid, FILE_APPEND);
  	if($redbag_id && $openid) {
  		$id = M('artisans_user_redbag')->where(array('to_id'=>$openid,'id'=>$redbag_id))->save(array('status'=>1,'cdate'=>time()));
  		if(!$id) {
  			wlog($this->_redbag_log.date('Ymd').'.log','红包状态更新失败:订单号：'.$id.',redbag_id=>'.$redbag_id.',openid=>'.$openid, FIEL_APPEND);
  		}
  	}
  }
  
  public function systempay() {
	$this->display('qcs_have_card');
  }
  
  //支付成功页
  public function successPay() {
  	$getData = I('get.','','sql_filter');
	$id = $getData['id'];
	$appid  = C("APP_ID");
	$this->appid   = $appid;
	if(I("code")){
		$code   = I("code");
		$shop   = D("WeiXinApi");
		$userinfo   = $shop->getOAuthAccessToken($code);
		$openid = $userinfo["openid"];
	}else{
		$openid = $this->reGetOAuthDebug(U("Craft/successPay").'?id='.$id);
	}
	
	$orderinfo = M('artisans_order')->where(array('id'=>$id,'openId'=>$openid,'status'=>3))->find();
	$orderinfo['userName'] = M('artisans_user_baseinfo')->where(array('id'=>$orderinfo['userId']))->getField('trueName');
	$orderinfo['moduleName'] = M('artisans_modules')->where(array('id'=>$orderinfo['moduleId']))->getField('name');
	
	$jump_url = 'http://localhost/'.C("TP_PROJECT_NAME").'index.php/Craft/qcsstatus2?openid='.$openid.'&ordernum='.$id.'&role=2';
	if($orderinfo['forWho'] == 1){
		$orderinfo['name'] = $orderinfo['friendName'];
		$orderinfo['phone'] = $orderinfo['friendPhone'];
		$orderinfo['address'] =$orderinfo['detailAddress'];
	}
	
	$this->assign('openid',$openid);
	$this->assign('jump_url',$jump_url);
	$this->assign('orderinfo',$orderinfo);
	
	$this->display('qcs_order');
  }

  //更新订单状态
  public function notice() {
  	$orderParam = I('get.','','sql_filter');
  	$userParam = $GLOBALS["HTTP_RAW_POST_DATA"];
  	$dateString = date('Ymd');
  	//将原始信息存入文件中
  	file_put_contents($this->pay_log_url.'artisans_original_'.$dateString.'.txt',"#############START:".date('Y-m-d H:i:s'), FILE_APPEND);
  	file_put_contents($this->pay_log_url.'artisans_original_'.$dateString.'.txt',"*************GET：\r\n'",FILE_APPEND);
  	file_put_contents($this->pay_log_url.'artisans_original_'.$dateString.'.txt',json_endoce($orderParam)."\r\n", FILE_APPEND);
  	file_put_contents($this->pay_log_url.'artisans_original_'.$dateString.'.txt',"*************POSTBEGIN\r\n", FILE_APPEND);
  	file_put_contents($this->pay_log_url.'artisans_original_'.$dateString.'.txt',$userParam."\r\n", FILE_APPEND);
  	file_put_contents($this->pay_log_url.'artisans_original_'.$dateString.'.txt',"#############END", FILE_APPEND);
  	//将数据解析后存入表中：wx_payment_userinfo  wx_payment_order
	//获取支付用户表的字段数组
	$payment_user_fields     = M('payment_userinfo')->getDbFields();
	$userinfo = $this->parseXml($userParam, $payment_user_fields);
	$userinfo['create_time'] = array('exp', 'now()');
	$userinfo['status']	 = 1;
	$userinfo_flag		 = M('payment_userinfo')->add($userinfo);
	//判断是否插入成功
	if(!$userinfo_flag) {
		file_put_contents($this->pay_log_url."paymentOrder_fail.txt",date('Y-m-d H:i:s')."--->payment_userinfo:".mysql_error()."--".M("payment_userinfo")->getLastSql()."\r\n", FILE_APPEND);
	}
	$orderParam['OpenId']   = $userinfo['OpenId'];
	$orderParam['create_time']  = array('exp','now()');
	$orderParam['status']   = 1;
	$order_flag = M("payment_order")->add($orderParam);
	if(!$order_flag) {
		file_put_contents($this->pay_log_url."paymentOrder_fail.txt",date('Y-m-d H:i:s')."--->payment_order:".mysql_error()."--".M("payment_order")->getLastSql()."\r\n", FILE_APPEND);
	}
	//更新订单状态及发送消息,其中trade_state为财付通返回，0代表成功
	$trade_state = true;
	if($orderParam['trade_state'] == '0') {
		//该订单支付信息在表中的记录次数
		$paymentoutcount    = M("payment_order")->where("trade_state='0' and out_trade_no='".$orderParam['out_trade_no']."'")->count();
		file_put_contents($this->pay_log_url."artisans_cft_log.txt",date('Y-m-d H:i:s')."--->payment_order:".M("payment_order")->getLastSql()."--订单次数count:".$paymentoutcount."\r\n",FILE_APPEND);
		//只有获取第一次通知记录更新订单状态
		if($paymentoutcount == 1) {
			$usershopid = ltrim($orderParam['out_trade_no'],'0');
			file_put_contents($this->pay_log_url."artisans_cft_log.txt",date('Y-m-d H:i:s')."--->商品订单号:".$usershopid."\r\n",FILE_APPEND);
			$artisans_order = M('artisans_order')->where('id='.$usershopid)->find();
			file_put_contents($this->pay_log_url."artisans_cft_log.txt",date('Y-m-d H:i:s')."--->artisans_order:".D("artisans_order")->getLastSql()."\r\n",FILE_APPEND);
			file_put_contents($this->pay_log_url."artisans_cft_log.txt",date('Y-m-d H:i:s')."--->artisans_order:".json_encode($artisans_order)."\r\n",FILE_APPEND);
			if($artisans_order) {
				$data['status'] = 3;
				$data['udate']  = array('exp','now()');
				$data['update_by']  = 'notice';
				//修改用户订单状态为已支付
				$usershop_res = M("artisans_order")->where("id=".$usershopid." and status=0")->save($data);
				//记录错误日志
				if(!$usershop_res) {
					//如果更新失败，则记入到错误日志
					file_put_contents($this->pay_log_url."artisans_cft_log.txt",date('Y-m-d H:i:s')."--->用户订单更新支付状态失败,对应的订单号为:".$usershopid.";from:notice"."\r\n",FILE_APPEND);
					$trade_state = false;
				}else{
					//更新红包id状态
					$this->_updateRedbag($artisans_order['redbag_id'],$artisans_order['openId'],$artisans_order['id']);
					//核销受益人卡卷
					$card_code = explode('**', $artisans_order['card_code_id']);
					$cardid = $card_code[0];
					$code   = $card_code[1];
					$this->cleanKQ($userinfo['OpenId'], $cardid, $codeid);
					//减1
					$reduceTimenumid = $this->reduceTimenum($artisans_order['userId'], $artisans_order['reservationTime'], $artisans_order['moduleId']);
					//推送消息
					$engineerId = M('artisans_user_baseinfo')->where(array('id'=>$artisans_order['userId']))->find();
					$artInfo = array(
						'artName'=>$engineerId['trueName'],
						'artPhone'=>$engineerId['phone'],
						'userName'=>$artisans_order['name'],
						'userPhone'=>$artisans_order['phone'],
						'time'=>$artisans_order['reservationTime'],
						'moduleName'=>$artisans_order['moduleName'],
						'userAddress'=>$artisans_order['address'],
						'ordernum'=>$usershopid,
						'friendName'=>$artisans_order['friendName'],
						'friendPhone'=>$artisans_order['friendPhone'],
						'shortmessage'=>$artisans_order['shortmessage'],
						'detailAddress'=>$artisans_order['detailAddress'],
						'forWho'=>$artisans_order['forWho'],
					);
					$this->sendMessage($artisans_order['openId'], $engineerId['openId'], $artInfo);
				}
			}else{
				$trade_state = false;
				file_put_contents($this->pay_log_url."artisans_cft_log".date('Y-m-d').".txt","商品订单号不存在:".$usershopid."\r\n",FILE_APPEND);
			}
		}
	}else{
		file_put_contents($this->pay_log_url."artisans_cft_log".date('Y-m-d').".txt","财付通返回值错误",FILE_APPEND);
		$trade_state = false;
	}
	if($userinfo_flag && $order_flag and $trade_state) {
		echo 'success';
	}else{
		echo 'fail';
	}
  }
  
  //查看订单支付状态是否更新成为成功
  public function updateOrder() {
  	$getData = I("post.",'','sql_filter');
	$openid = $getData['order_user_id'];
	$out_trade_no = $getData["out_trade_no"];
	//订单信息
	if(C('ProductStatus') === false) {
		$orderinfo = M("payment_order")->where(" out_trade_no='{$out_trade_no}' ")->order("create_time desc")->limit(1)->field("trade_state,id")->find();
	}else{
		$orderinfo = M("payment_order")->where(" OpenId='{$openid}' and out_trade_no='{$out_trade_no}' ")->order("create_time desc")->limit(1)->field("trade_state,id")->find();
	}
	file_put_contents($this->pay_log_url."updateorder.txt",date('Y-m-d H:i:s')."--->用户id:{$openid}----订单号:".$out_trade_no."----".M("payment_order")->getLastSql()."\r\n",FILE_APPEND);
	//交易状态
	$trade_state = $orderinfo["trade_state"];
	if(isset($trade_state) && $trade_state==0 && $orderinfo) {
		//查看订单状态是否更新，如果没有更新，则更新订单状态
		$artisans_order = M('artisans_order')->where('id='.$out_trade_no)->find();
		if($artisans_order['status']<>3) {
			file_put_contents($this->pay_log_url."updateorder.txt",date('Y-m-d H:i:s')."--->订单号为:".$out_trade_no.",该订单信息:".json_encode($artisans_order)."\r\n",FILE_APPEND);
			$data['status'] = 3;
			$data['udate']  = array('exp','now()');
			$data['update_by']  = 'updateOrder';
			//修改用户订单状态为已支付
			$res = M("artisans_order")->where("id=".$out_trade_no." and status<>3")->save($data);
			if(!$res) {
				//如果更新失败，则计入到错误日志
				file_put_contents($this->pay_log_url."updateorder.txt",date('Y-m-d H:i:s')."--->用户订单更新支付状态失败,对应的订单号为:".$out_trade_no."\r\n",FILE_APPEND);
			}else{
				$status = 200;
			}
			if($status == 200) {
				//更新红包id状态
				$this->_updateRedbag($artisans_order['redbag_id'], $artisans_order['openId'], $artisans_order['id']);
				//核销卡券
				$card_code=explode('**', $artisans_order['card_code_id']);
				$cardid=$card_code[0];
				$code=$card_code[1];
				$this->cleanKQ($openid,$cardid,$codeid);
				//产能减1
				$reduceTimenumid = $this->reduceTimenum($artisans_order['userId'],$artisans_order['reservationTime'],$artisans_order['moduleId']);
				$engineerId = M('artisans_user_baseinfo')->where(array('id'=>$res['userId']))->find();
				$artInfo = array(
					'artName'=>$engineerId['trueName'],
					'artPhone'=>$engineerId['phone'],
					'userName'=>$artisans_order['name'],
					'userPhone'=>$artisans_order['phone'],
					'time'=>$artisans_order['reservationTime'],
					'moduleName'=>$artisans_order['moduleName'],
					'userAddress'=>$artisans_order['address'],
					'ordernum'=>$out_trade_no,
					'friendName'=>$artisans_order['friendName'],
					'friendPhone'=>$artisans_order['friendPhone'],
					'shortmessage'=>$artisans_order['shortmessage'],
					'detailAddress'=>$artisans_order['detailAddress'],
					'forWho'=>$artisans_order['forWho'],
				);
				$this->sendMessage($openid,$engineerId['openId'],$artInfo);
			}
		}else{
			$status = 200;
		}
	}else{
		$status = 0;
	}
	return json_encode(array('status'=>$status, 'info'=>$orderinfo["id"]));
  }
  
  //查询微信那边是否生成成功订单的信息
  public function findwxorder() {
  	$return_data['status'] = 0;
  	$getData 	= I("post.",'','sql_filter');
  	$out_trade_no   = $getData["out_trade_no"];
	$paymentid  	= $getData["paymentid"];
	$openid 	= $getData["order_userId"];
	$ret 		= $this->orderQueryApi($out_trade_no);
	//解析返回的订单结果
	$error_code = $ret['errcode'];
	if(isset($error_code) && $error_code == 0) {
		//调用接口成功，获取订单详情
		$order_info = $ret["order_info"];
		if($order_info["ret_code"] == 0) {
			//如果订单获取成功
			$trade_state    = $order_info["trade_state"];
			if($trade_state == 0) {
				$counntnum = M("payment_order")->where(" OpenId='{$openid}' and out_trade_no='{$out_trade_no}' and trade_state=0 ")->count();
				if($countnum == 0) {
					//重新生成微信订单信息
					$order_info['status'] = 1;
					$order_info['flag']   = 2;
					$order_info['OpenId'] = $openid;
					$order_info['create_time'] = array('exp', 'now()');
					$return_num = M("payment_order")->add($order_info);
					file_put_contents($this->pay_log_url."findwxorder.txt",date("Y-m-d H:i:s")."--->系统中微信订单信息号：".$paymentid."--->".M("payment_order")->getLastSql()."\r\n",FILE_APPEND);
					file_put_contents($this->pay_log_url."findwxorder.txt",date("Y-m-d H:i:s")."--->重新生成的系统中微信订单信息号：".$return_num."\r\n",FILE_APPEND);
					//查询并更新系统订单的状态
					$artisans_order = M('artisans_order')->where('id='.$out_trade_no)->find();
					if($artisans_order['status']<>3) {
						$data['status'] = 3;
						$data['udate']  = array('exp','now()');
						$data['update_by']  = 'findwxorder';
						//修改用户订单状态为已支付
						$return_num = M("artisans_order")->where("id=".$out_trade_no." and status<>3")->save($data);
						if($return_num) {
							//更新红包id状态
							$this->_updateRedbag($artisans_order['redbag_id'],$artisans_order['openId'],$artisans_order['id']);
							//核销卡卷
							$card_code=explode('**', $artisans_order['card_code_id']);
							$cardid=$card_code[0];
							$code=$card_code[1];
							$this->cleanKQ($openid,$cardid,$codeid);
							//减1
							$reduceTimenumid = $this->reduceTimenum($artisans_order['userId'],$artisans_order['reservationTime'],$artisans_order['moduleId']);
							$engineerId = M('artisans_user_baseinfo')->where(array('id'=>$artisans_order['userId']))->find();
							$artInfo = array(
								'artName'=>$engineerId['trueName'],
								'artPhone'=>$engineerId['phone'],
								'userName'=>$artisans_order['name'],
								'userPhone'=>$artisans_order['phone'],
								'time'=>$artisans_order['reservationTime'],
								'moduleName'=>$artisans_order['moduleName'],
								'userAddress'=>$artisans_order['address'],
								'ordernum'=>$out_trade_no,
								'friendName'=>$artisans_order['friendName'],
								'friendPhone'=>$artisans_order['friendPhone'],
								'shortmessage'=>$artisans_order['shortmessage'],
								'detailAddress'=>$artisans_order['detailAddress'],
								'forWho'=>$artisans_order['forWho'],
							);
							$this->sendMessage($openid,$engineerId['openId'],$artInfo);
						}
						file_put_contents($this->pay_log_url."findwxorder.txt",date("Y-m-d H:i:s")."--->系统中订单信息号：".$out_trade_no."--->".M("artisans_order")->getLastSql()."\r\n",FILE_APPEND);
						file_put_contents($this->pay_log_url."findwxorder.txt",date("Y-m-d H:i:s")."--->更新之后的返回的值：".$return_num."\r\n",FILE_APPEND);
					}
				}
				$return_data['status'] = 200;
			}
		}
  	}
  	return json_encode($return_data);
  }
  
  //核销卡卷
  public function cleanQ($openid, $moduleId) {
  	if(!($openid && $moduleId)) {
  		return false;
  	}
  	$cardid = $this->_cardid[$moduleId];
  	if(empty($cardid)) {
  		return false;
  	}
  	$getCleanQurl = $this->_artisans_url.$cardid.'&openid='.$openid;
  	$getCleanInfo = send_get_curl($getCleanQurl);
  	$getCleanInfo = json_decode($getCleanInfo, true);
  	if($getCleanInfo['error_code'] === 0) {
  		$card_code = $getCleanInfo['data']['card_codes'][0]['card_code'];
  		$url 	   = $this->_sendCleanQ_url.'?code='.$card_code.'&consume_type=100';
  		$reurnInfo = send_get_curl($url);
  		$returnInfo= json_decode($reurnInfo, true);
  		if($returnInfo['error_code'] === 0) {
  			$cleanQlogData['status'] = 1;
  		}else{
  			$cleanQlogData['status'] = 0;
  		}
  	}else{
  		$card_code = '';
  		$cleanQlogData['status'] = -1;
  	}
  	$cleanQlogData['user_id']  	= $openid;
  	$cleanQlogData['card_code'] 	= $card_code;
  	$cleanQlogData['return_status'] = $returnInfo['error_code'];
  	$cleanQlogData['type']		= 2;
  	$cleanQlogData['cdate']		= date('Y-m-d H:i:s');
  	M('interface_log')->add($cleanQlogData);
  }
  
  public function cleanKQ($openid,$cardid,$codeid) {
  	$getCleanQurl = $this->_artisans_url.$cardid.'&openid='.$openid;
  	$getCleanInfo = send_get_curl($getCleanQurl);
  	$getCleanInfo = json_decode($getCleanInfo, true);
  	if($returnInfo['error_code'] === 0 && $getCleanInfo['data']['card_codes'][0]['id'] == $codeid) {
  		$card_code = $getCleanInfo['data']['card_codes'][0]['card_code'];
  		$url	   = $this->_sendCleanQ_url.'?code='.$card_code.'&consume_type=100';
  		$reurnInfo = send_get_curl($url);
  		$returnInfo = json_decode($reurnInfo, true);
  		if($returnInfo['error_code'] === 0) {
  			$cleanQlogData['status'] = 1;
  		}else{
  			$cleanQlogData['status'] = 0;
  		}
  	}else{
  		$card_code = '';
  		$cleanQlogData['status'] = -1;
  	}
  	$cleanQlogData['open_id'] = $openid;
  	$cleanQlogData['card_id'] = $cardid;
  	$cleanQlogData['code_id'] = $codeid;
  	$cleanQlogData['card_code'] = $card_code;
  	$cleanQlogData['return_status'] = $returnInfo['error_code'];
  	$cleanQlogData['updated_time'] = date('Y-m-d H:i:s');
  	M('artisans_card_log')->add($cleanQlogData);
  }
  
  //推送消息
  public function sendMessage($openId = '', $engineerId='', $userinfo = array()) {
  	$user_url = 'http://localhost/'.C("TP_PROJECT_NAME").'/index.php/Craft/qcsstatus2?ordernum='.$userinfo['ordernum'];
  	$art_url  = 'http://localhost/'.C("TP_PROJECT_NAME").'/index.php/Craft/qcsstatus2?ordernum='.$userinfo['ordernum'];
  	file_put_contents($this->pay_log_url."sendm.txt", date("Y-m-d H:i:s")."--->openid:".$openId."--->engineerId".$engineerId."\r\n", FILE_APPEND);
  	if($userinfo['forWho'] == 1) {
  		//为朋友
  		$message_i = '您已经帮朋友'.$userinfo['friendName'].'预约了XXX的【'.$userinfo['moduleName'].'】服务，XXX是'.$userinfo['artName'].'，服务开始时间'.$userinfo['time'].'，已经短信通知了您的朋友。<a href="'.$user_url.'">点击这里</a>查看详情；点击这里点击后进入预约单详情页面。';
  		$address = $userinfo['detailAddress'];
		$message_j = '客户'.$userinfo['friendName'].'已经预约你上门进行【'.$userinfo['moduleName'].'】服务，电话：'.$userinfo['friendPhone'].'，时间:'.$userinfo['time'].'；地点：'.$address.'；是Ta朋友帮他预约的。<a href="'.$art_url.'">点击这里</a>查看详情；同时请让上门后打开页面，请用户操作。';
		$message_k = "客户{$userinfo['friendName']}已经预约你上门进行【{$userinfo['moduleName']}】服务，电话：{$userinfo['friendPhone']}，时间:{$userinfo['time']}；地点：{$address}；是Ta朋友帮他预约的。";
		//发送微信消息
		$this->sendWeixinMsg($openId,$message_i);
		$this->sendWeixinMsg($engineerId,$message_j);
		//给好友发送短信
		$shortmessage = "您的好友为您预约了XXXXX {$userinfo['moduleName']}的服务，XXX是{$userinfo['artName']}，手机：{$userinfo['artPhone']}，服务时间：{$userinfo['time']}。";
		if($userinfo['shortmessage']){
			$shortmessage .= "您朋友还想和您说:{$userinfo['shortmessage']}";
		}
		$this->SendShortMessage($userinfo['friendPhone'],$shortmessage);
		$this->SendShortMessage($userinfo['artPhone'],$message_k);
  	}else{
  		//为自己
		$message_i  = '【服务预约】您已经预约XXX的【'.$userinfo['moduleName'].'】的服务，XXX是'.$userinfo['artName'].'，电话号码：'.$userinfo['artPhone'].'；服务开始开始时间'.$userinfo['time'].'，<a href="'.$user_url.'">点击这里</a>查看详情。';
		$short_msg_i = "【服务预约】您已经预约XXX的【{$userinfo['moduleName']}】的服务，XXX是{$userinfo['artName']}，电话号码：{$userinfo['artPhone']}；服务开始开始时间{$userinfo['time']}。";
		$short_msg_j = "【服务预约】用户{$userinfo['userName']}，电话{$userinfo['userPhone']}，已经预约你的【{$userinfo['moduleName']}】的服务，服务开始开始时间{$userinfo['time']}，请及时联系用户。";
		$message_j =  '【服务预约】用户名'.$userinfo['userName'].'，电话'.$userinfo['userPhone'].'，已经预约你的【'.$userinfo['moduleName'].'】的服务，时间'.$userinfo['time'].' <a href="'.$art_url.'">点击这里</a>可以查看详情，请记得提醒用户在我的服务中更新单据状态，并点评哦';
		//发送微信消息
		$this->sendWeixinMsg($openId,$message_i);   //为用户发送微信消息
		$this->sendWeixinMsg($engineerId,$message_j);  //为XXX发送微信消息
		//发送短信
		$this->SendShortMessage($userinfo['userPhone'],$short_msg_i);
		$this->SendShortMessage($userinfo['artPhone'],$short_msg_j);
  	}
  }

	//预约时间  减1
	public function reduceTimenum($userId,$time,$moduleId){
		$where = array(
				'userId'=>$userId,
				'startTime'=>array('elt',$time),
				'endTime'=>array('egt',$time),
				'moduleId'=>$moduleId,
		);
		$timeInfo = M('artisans_time_price')->where($where)->find();
		$useNum = $timeInfo['useNum']+1;
		$nouseNum =  $timeInfo['nouseNum']-1;
		$saveData = array(
				'useNum'=>$useNum,
				'nouseNum'=>$nouseNum,
				'udate'=>date('Y-m-d H:i:s'),
		);
		$whereNew = array(
				'userId'=>$userId,
				'startTime'=>array('elt',$time),
				'endTime'=>array('egt',$time),
		);
		$id = M('artisans_time_price')->where($whereNew)->save($saveData);
		return $id;
	}
	
	/**
	 *解析xml格式的文档
	 *@param xml文件或者相关数据
	 *@return array 返回要插入的数据数组
	 */
	public function parseXml($xml_data,$param){
		//初始化要插入的数组
		$returndata = array();
		//实例化 dom解析对象
		$doc = new DOMDocument ( '1.0', 'utf-8' );
		//保留原有的空格元素，默认清除
		$doc->preserveWhiteSpace = false;
		//加载xml元素
		$doc->loadXML( $xml_data );
		//实例化 xpath对象
		$xpath = new DOMXPath ( $doc );
		//根据用户所传数组参数来进行解析
		foreach($param as $key=>$value){
			//根据指定的字段实例化相关的数据对象
			$item = $xpath->query("//xml/".$value."[1]");
			if(!empty($item)){
				//获取数据长度
				$length = $item->length;
				//获取该字段的数据
				$nodeValue = $item->item(0)->nodeValue;
				//如果该节点有数据,才获取该数据
				if($length>0){
					$returndata[$value] = $nodeValue;
				}
			}
		}
		//返回解析好的结果
		return $returndata;
	}

	/*
	 * @desc 根据经纬度，计算两点之间的距离
	* @param float $lat 纬度
	* @param float $lng 经度
	*/
	public function getDistance($lat1, $lng1, $lat2, $lng2){
		$r = 6378.137;  //地球半径
		//角度转为弧度
		$radLat1 = deg2rad($lat1);
		$radLat2 = deg2rad($lat2);
		$radLng1 = deg2rad($lng1);
		$radLng2 = deg2rad($lng2);
		//sqrt：平方根，pow(x, n)：x的n次方的幂，asin：反正弦，sin：正弦
		$s = 2*asin(sqrt(pow(sin(($radLat1 - $radLat2)/2),2)+cos($radLat1)*cos($radLat2)*pow(sin(($radLng1-$radLng2)/2),2)))*$r;
		return $s;
	}
	
	public function qcslist(){
		$this -> display('qcs_list');
	}
	
	public function qcs_success()
	{
		$this->display('qcs_success');
	}
	
	public function qcs_failed()
	{
		$openid=I('openid');
		$this->assign('openid',$openid);
		$this->display('qcs_failed');
	}
	
	/**
	 *订单查询接口
	 */
	public function orderQueryApi($out_trade_no) {
		//这里只需要获取一个out_trade_no来完成
		$appid = C("APPID");//appid
		$partner = C("PARTNERID");//商户号
		$partnerkey = C("PARTNERKEY");//商户key
		$appkey = C("PAYSIGNKEY");//支付签名pagsignkey
		//生成sign签名
		$con_str = "out_trade_no=".$out_trade_no."&partner=".$partner."&key=".$partnerkey;
		$sign = strtoupper(md5($con_str));
		//生成package签名
		$package = "out_trade_no=".$out_trade_no."&partner=".$partner."&sign=".$sign ;
		//获取linux时间戳
		$timestamp = time();
		//生成app_signature签名
		$con_str ="appid=$appid&appkey=$appkey&package=$package&timestamp=$timestamp";
		$app_signature = sha1($con_str);
		//组装订单查询的参数
		$post_array = array();
		$post_array['appid'] = $appid;
		$post_array['package'] = $package;
		$post_array['timestamp'] = $timestamp;
		$post_array['app_signature'] = $app_signature;
		$post_array['sign_method'] = "sha1";
		//对该数组进行Json_encode编码
		$post_data = json_encode($post_array);
		//订单查询的接口
		$url = "https://api.weixin.qq.com/pay/orderquery?access_token=" . $this->token;
		$ret =  send_curl ( $url, $post_data, C('PROXY'));
		//分析订单的查询结果
		return  json_decode($ret,true);
	}
	
	public function apply() {
		$appid  = C("APP_ID");
		$this->appid    = $appid;
		if(I("code")){
			$code   = I("code");
			$shop   = D("WeiXinApi");
			$userinfo   = $shop->getOAuthAccessToken($code);
			$openid = $userinfo["openid"];
		}else{
			$openid = $this->reGetOAuthDebug(U("Craft/apply"));
		}
		$this->share_url = "http://localhost/".C("TP_PROJECT_NAME")."/index.php/PlanRepair/index";
		$this->imgUrl="http://localhost/".C("TP_PROJECT_NAME")."/Public/Images/PlanRepair/share.jpg";
		$this->assign('user_id',$openid);
		$this -> display('qcs_applay');
	}
