<?php
namespace Artisans\Controller;
class WxPayController extends CommonController {
      private $_pay_log_url = '/share/pay_log_url/weixin/original';                       //订单支付日志
      private $_error_url   = '/share/pay_log_url/weixin/original/error.log';             //订单失败日志
      private $_success_url = '/share/pay_log_url/weixin/original/caifutong_success.log'; //财付通成功返回日志
      private $_update_url  = '/share/pay_log_url/weixin/original/update_status.log';     //更新订单日志
      private $_findwx_order_url = '/share/pay_log_url/weixin/original/findwx_order.lgo'; //重新生成订单
      
      //微信回调更新订单
      public function notice() {
             $orderParam = I('get.');     //获取订单参数
             $userParam  = $GLOBALS['HTTP_RAW_POST_DATA'];  //获取用户信息
             $dateString = date('Ymd');
             
             //将原始信息存入文件中
             $log_url = $this->_pay_log_url.'/'.$dateString.'.log';
             wlog($log_url,'---------------start---------------');
		 wlog($log_url,'---------------GET---------------');
		 wlog($log_url,$orderParam);
		 wlog($log_url,'---------------POSTBEBIN---------------');
		 wlog($log_url,$userParam);
		 wlog($log_url,'---------------END---------------');
		 
		 //将数据解析后存入表中 获取支付用户表的字段数组
		 $payment_user_fields    = M("pay_payment_userinfo")->getDbFields();
      }
}
