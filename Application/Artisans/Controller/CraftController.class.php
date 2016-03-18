<?php
namespace Artisans\Controller;
class CraftController extends CommonController {
      private $_user_api_reg     = '/UserCenterApi/regUser';    //用户中心api
      private $_create_order_api = '/OrderApi/createOrder';     //订单中心api【创建订单】
      private $_get_order_api    = '/OrderApi/getOneOrder';     //单个订单信息
      private $_get_more_order   = '/OrderApi/getMoreOrder';    //多个订单信息
      private $_cancle_order     = '/OrderApi/updateOrder';     //取消订单
      private $_order_step       = '/OrderApi/updateOrderStep'; //订单关键步骤
      private $_daogou           = '/OrderApi/getDaogou';       //客服导购产品信息
      private $_product_api      = '/Api/getProductInfo';       //产品详情信息api
      private $_product_list_api = '/Api/getProductList';       //产品列表
      private $_Product_step     = '/Api/getProductStep';       //产品关键步骤
      private $_get_craft_info   = '/Api/getCraftsManInfo';     //XXX信息api
      
      private $_get_param        = array();                     //get请求传递的参数
      private $_openid           = '';                          //用户openid
      private $_usercenter_uid   = 0;                           //用户中心uid
      private $_city_id          = 0;                           //当前的城市id
      private $_ip               = '';
      
      private $_wxpay_action       = array('selectcard', 'simplepay', 'expertservice');   //用到微信支付的action
      private $_clean_session      = array('submitUserinfo');                             //清空session('auth_get_param')的action
      private $_pay_offline_cityid = array(1, 73, 198);                                   //支持线下支付的城市Id, 【北上广】
      private $_order_window_status= array(100, 200, 300, 400);                           //100跳转支付成功页， 200关闭窗口。
      private $_city_list          = array('北京市'=>1,'上海市'=>73,'广州市'=>198,'天津市'=>2,'深圳市'=>200,'济南市'=>135,'成都市'=>237,'西安市'=>290,'杭州市'=>87,'南京市'=>74,'武汉市'=>170);   //开通的城市
      
      //获取用户openid
      public function _initialize() {
          /*if(in_array(ACTION_NAME, $this->_clean_session)) {
              //清空提交页session('auth_get_param')的值
              session('auth_get_param', null);
          }*/
      }
      
      protected function checkAuthGetParam() {
          $get = I("get.");
          foreach($get as $key=>$value) {
              $getinfo .= $key.'='.$value.'**';
          }
          if(I("code")) {
              $code = I("code");
              $shop = D("WeiXinApi");
              $userinfo = $shop->getOAuthAccessToken($code);
              $openid = $userinfo["openid"];
              if(!$openid) {
                  wlog('/share/weixinLog/artisans/user_center_api/no_find_openid.log', $userinfo);    //auth验证没有获取到openid
              }
          }else{
              if(in_array(strtolower(ACTION_NAME), $this->_wxpay_action)) {
                  $openid = $this->reGetOAuthDebug(C("WeixinPayUrl").ACTION_NAME.'?getinfo='.$getinfo);
              }else{
                  $openid = $this->reGetOAuthDebug(U('Craft/'.ACTION_NAME).'?getinfo='.$getinfo);
              }
          }
          if($openid) {
              $third_name = M('cut_uid_thirdname')->where(" ThirdName='%s' ", $openid)->getField('UserId');
              if($third_name) {
                  $openid_user_id = $third_name;
              }else{
                  $base_info_s    = array(
                      'source_from'=>'weixin',
                      'user_type'=>'other',
                      'type'=>200,
                      'username'=>$openid
                  );
                  $user_center_info = send_curl(C("ArtisansApi")).$this->_user_api_reg, $base_info_s);
                  $parse_data       = json_decode($user_center_info, true);
                  if(!$parse_data['data']['uid']) {
                      $parse_data['data']['uid']  = 0;
                      wlog('/share/weixinLog/artisans/user_center_api/no_find_uid.log', $openid);     //调用用户中心接口， 获取不到用户openid
                      wlog('/share/weixinLog/artisans/user_center_api/no_find_uid.log', $parse_data);
                  }else{
                      $third_data['ThirdName']	= $openid;
            					$third_data['UserId']		= $parse_data['data']['uid'];
            					$third_data['CreaterTime']	= date('Y-m-d H:i:s');
            					
            					M('cut_uid_thirdname')->add($third_data);
                  }
                  $openid_user_id = $parse_data['data']['uid'];
              }
          }
          $this->_openid         = $openid;
          $this->_usercenter_uid = $openid_user_id;
          $this->_ip             = get_client_ip();
          if(C('ProductStatus') === false) {
              $this->_get_param = I('get.');
          }else{
              $getinfo    = $get['getinfo'];
            //$getinfo    = urldecode($getinfo);
              $this->_get_param  = array();
              foreach($get_param_s as $key=>$value) {
                   if($value) {
                      $tmp = array();
                      $tmp = explode('=', $value);
                      $this->_get_param[$tmp[0]] = $tmp[1];
                   }
              }
          }
          
          $this->assign('getData', $this->_get_param);
          $this->count_source($this->_get_param);
          
          //城市配对
          $this->assign('cityList', json_encode($this->_city_list));
          
          $cityinfo = M('cut_customer_city')->where(array('Uid'=>$this->_usercenter_uid))->find()['cityid'];
          if($cityinfo) {
              $this->_city_id = $cityinfo;
          }else{
              $this->_city_id = $this->_get_param['city_id'];
          }
          //默认为北京
          if(empty($this->_city_id)) {
              $this->_city_id = 1;
          }
          
          $this->assign('uid', $this->_usercenter_uid);
          $this->assign('openid', $this->_openid);
          $this->assign('city_id', $this->_city_id);
          $this->assign('city', array_serach($this->_city_id, $this->_city_list));
      }
      
      //用户提交信息
      public function submitUserinfo() {
          $this->checkAuthGetParam();
          $openid = $this->_openid;
          
          //测试数据
          /*session('auth_cityid_craft',1);
      		$this->_get_param['craft_id']	= 6;
      		$this->_get_param['pro_id']	= 1;
      		$this->_get_param['order_date']	= '2015-05-03';
      		$this->_get_param['order_time_id']	= 1;
      		$this->_get_param['for_who']	= 200;
      		$this->_get_param['lat']	= '39.74629731';
      		$this->_get_param['lng']	= '116.32575479';
      		$this->_get_param['pay_process']	= 4;
      		if (empty($this->_get_param['city_id'])) {
      		    $cityinfo  = M('cut_customer_city')->where(array('Uid'=>$this->_usercenter_uid))->find()['Cityid'];
      			  $this->_get_param['city_id']  =  $cityinfo;
      			  unset($cityinfo);
      		}*/
      		
      		$this->assign('openid', $openid);
      		$this->assign('get_param', $this->_get_param);
      		$this->assign('pageHome', 'craftSubmitOrder');
      		$this->assign('pagename', 'XXXXX-提交订单页');
      		
      		$this->display('qcs_sbmit_order');
      }
      
      public function cSubinfo() {
          $post_data	= I('post.');
          $data['ProductId']	  = $post_data['pro_id'];
      		$data['CraftsmanId']	= $post_data['craft_id'];
      		$data['UserOpenid']	  = $post_data['openid'];
      		$data['Name']	        = $post_data['name'];
      		$data['CityName']	    = $post_data['city_id'];
      		$data['Address']	    = $post_data['address'];
      		$data['Phone']	      = $post_data['phone'];
      		$data['OrderDate']	  = $post_data['order_date'];
      		$data['OrderTimeId']	= $post_data['order_time_id'];
      		$data['ForWho']	      = $post_data['for_who'];
      		$data['ShortMessage']	= $post_data['wish'];
      		$data['Lat']	        = $post_data['lat'];
      		$data['Lng']	        = $post_data['lng'];
      		$data['Codeid']	      = $post_data['codeid'];
      		$data['Cardid']	      = $post_data['cardid'];
      		$data['PayProcess']	  = $post_data['pay_process'];
      		$data['CreaterTime']	= date('Y-m-d H:i:s');
      		
      		$id	= M('ord_submit_info')->add($data);
      		if($id) {
      		    return json_encode(array('status'=>200));
      		}else{
      		    return json_encode(array('status'=>300));
      		}
      }
}
