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
					
				}
			}
		}
	}
  }
