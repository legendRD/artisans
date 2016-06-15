<?php
namespace Artisans\Controller;
use Think\Controller;
class CommonController extends Controller {
      private $_test_id = '';
      private $_wxpay_action = array('selectCard', 'simplePay', 'expertService'); //用到微信支付的action
      
      //获取用户openid
      public function getOAuthOpenId() {
             $code = I("code");
             $ret  = D("WeiXinApi")->getOAuthAccessToken($code);
             return $ret['openid'];
      }
      
      public function reGetOAuthDebug($url) {
             if(C('ProductStatus') === false || I('get.Plat') == 'app') {
                  return $this->_test_id;
             }else{
                  $redirect_uri = "http://localhost".$url;
                  $url  = "https://open.weixin.qq.com/connect/oauth2/authorize";
                  $url .= "?appid=".C(APP_ID);
                  $url .= "&redirect_uri=$redirect_uri";
                  $url .= "&response_type=code";
                  $url .= "&scope=snsapi_base";
                  $url .= "&state=123";
                  $url .= "#wechat_redirect";
                  redirect($url);
             }
      }
      
      public function reGetOAuthUserInfo($url) {
             $redirect_url = "http://localhost".$url;
             $url  = "https://open.weixin.qq.com/connect/oauth2/authorize";
             $url .= "?appid=".C(APP_ID);
             $url .= "&redirect_uri=$redirect_uri";
             $url .= "&response_type=code";
             $url .= "&scope=snsapi_userinfo";
             $url .= "&state=123";
             $url .= "#wechat_redirect";
             redirect($url);
      }
}
