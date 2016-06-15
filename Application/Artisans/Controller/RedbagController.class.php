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
}
