<?php
namespace Artisans\Controller;
class WxPayController extends CommonController {
      private $_pay_log_url = '/share/pay_log_url/weixin/original';                       //订单支付日志
      private $_error_url   = '/share/pay_log_url/weixin/original/error.log';             //订单失败日志
      private $_success_url = '/share/pay_log_url/weixin/original/caifutong_success.log'; //财付通成功返回日志
      private $_update_url  = '/share/pay_log_url/weixin/original/update_status.log';     //更新订单日志
      private $_findwx_order_url = '/share/pay_log_url/weixin/original/findwx_order.lgo'; //重新生成订单
      
      /*
      *解析xml格式的文档
      *@param xml文件或者相关数据
      *@return array 返回要插入的数据数组
      */
      public function parseXml($xml_data, $param) {
      	     //初始化要插入的数组
      	     $returndata = array();
      	     //实例化dom解析对象
      	     $doc = new \DOMDocument('1.0', 'utf-8');
      	     //保留原有空格元素，默认清楚
      	     $doc->preserveWhiteSpace = false;
      	     //加载xml元素
      	     $doc->loadXML($xml_data);
      	     //实例化xpath对象
      	     $xpath = new \DOMXPath($doc);
      	     //根据用户所传数组参数来进行解析
      	     foreach($param as $value) {
      	     	     //根据指定的字段实例化相关的数据对象
      	     	     $item = $xpath->query("//xml/".$value."[1]");
      	     	     if(!empty($item)) {
      	     	     	       //获取数据长度
      	     	     	       $length = $item->length;
      	     	     	       //获取字段的数据
      	     	     	       $nodeValue = $item->item(0)->nodeValue;
      	     	     	       //如果该节点有数据，才获取该数据
      	     	     	       if($length>0) {
      	     	     	       	         $returndata[$value] = $nodeValue;
      	     	     	       }
      	     	     }
      	     }
      	     //返回解析好的结果
      	     return $returndata;
      }
      
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
	     $userinfo               = $this->parseXml($userParam, $payment_user_fields);
      }
      
      
}
