<?php
namespace Artisans\Controller;
class RedbagController extends CommonController {
      private $_openid         = '';        //用户openid;
      private $_usercenter_uid = 0;         //用户中心uid
      private $_city_id        = 0;
      
      //产品活动API
      private $_user_coupons_url = 'http://localhost/artisans_test/index.php/Artisans/ProActiveApi/getUserCoupons';
      private $_kq_openid        = '';  //测试卡券openid
      
      //产品对应的卡券Id,用户显示用户的卡券信息
      private $_cardid = array(1=>array(), 6=>array(), 8=>array(85));
      private $_artisans_url = '';  //卡券接口路径
      
      //微信支付页，选择优惠券页
      public function select_redbag() {
             if(I("code")) {
                   $code = I("code");
                   $shop = D("WeiXinApi");
                   $userinfo = $shop->getOAuthAccessToken($code);
                   $openid = $userinfo["openid"];
                   if(!$openid) {
                       wlog('/share/weixinLog/artisans/user_center_api/no_find_openid.log', $userinfo); //auth验证没有获取到openid
                   }
             }else{
                  $openid = $this->reGetOAuthDebug(U('Redbag/'.ACTION_NAME));
             }
             $info   = M('ord_submit_info')->where("UserOpenid='%s'", $openid)->order('InfoId desc')->find();
             $pro_id = $info['ProductId']; 
             
             //用户卡券
             $transfer_data['city_id'] = $this->_city_id;
             $transfer_data['pro_id']  = $pro_id;
             $transfer_data['uid']     = $uid;
             $user_card_info = send_curl($this->_user_coupons_url, $transfer_data);
             $parse_data = json_decode($user_card_info, true);
             if($parse_data['code'] == 200 && $parse_data['code']['data']) {
                 $data = $parse_data['code']['data'];
             }else{
                 $data = array();
             }
             
             //卡券列表
             $cardid = $this->_card[$pro_id];
             $num = count($cardid);
             $cardinfo = array();
             for($i=0;$i<$num;$i++) {
                 $cardinfo = $this->getcard_info($cardid[$i], $openid);
             }
             $card_num = count($cardinfo);
             if($card_num>0) {
                $this->assign('status', 200);
             }else{
                $this->assing('status', 500);
             }
             $this->assign('list', array($cardinfo));
             
             //跳转链接
             $jump_url = U('Craft/selectCard').'?haoren=1';
             
             $this->assign('jump_url', $jump_url);
             $this->display(T('Craft/qcs_card'));
      }
      
      public function getcard_info($cardid, $openid) {
             if($cardid) {
                   $getcard_artisans_url  = $this->_artisans_url.'?id='.$cardid.'&openid='.$openid;
                   $getcard_artisans_info = send_get_curl($getcard_artisans_url);
                   $getcard_artisans_info = json_decode($getcard_artisans_info, true);
                   $today = date('Y-m-d H:i:s');
                   if($getcard_artisans_info['error_code'] === 0 
                        && $getcard_artisans_info['data']['card']['start_time'] < $today
                        //&& $getcard_artisans_info['data']['card']['end_time'] > $today
                        && $getcard_artisans_info['data']['card']['state'] == 100
                        //&& $getcard_artisans_info['data']['card_codes'][0]['cost_count']==="0") {
                        ) {
                              $cardinfo['cash'] =  $getcard_artisans_info['data']['card']['reduce_cose'];
                              $cardinfo['id']   =  $getcard_artisans_info['data']['card_codes'][0]['card_id'];
                              $cardinfo['name'] =  $getcard_artisans_info['data']['card']['title'];
                              if($getcard_artisans_info['data']['card_codes'][0]['cost_count']>0) {
                                    $cardinfo['status'] = 1;      //已使用
                              }elseif($getcard_artisans_info['data']['card']['end_time']<$today) {
                                    $cardinfo['status'] = 2;      //已过期
                              }else{
                                    $cardinfo['status'] = 0;
                              }
                              $cardinfo['daytime'] = $getcard_artisans_info['data']['card']['end_time'];
                              $cardinfo['daytime'] = date('Y-m-d', strtotime($cardinfo['daytime']));
                              $cardinfo['last']    = (int)floor((strtotime($cardinfo['daytime'])-time())/86400);
                              $cardinfo['range']   = $cardid==81?'XXXX/XXXX':$getcard_artisans_info['data']['card']['sub_title'];
                              $cardinfo['pram']    = '&cardid='.$cardinfo['id'].'&codeid='.$getcard_artisans_info['data']['card_codes'][0]['id'];
                              return $cardinfo;
                        }
             }
             return false;
      }
}
