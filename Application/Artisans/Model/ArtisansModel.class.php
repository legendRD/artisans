<?php
namespace Artisans\Model;
use Think\Model;
class ArtisansModel extends Model{
  protected $autoCheckFields = False;
  //生成订单号地址
  private $_getTrade_url = 'http://localhost/order/addor';
  //支付接口
  private $_orderPay_url = 'http://localhost/Paycenter/CreatePayInfo';
  //获取订单状态地址
  private $_getStatus = 'http://localhost/order/list';
  //更新用户状态和XX
  private $_pay_log = '/share/pay_log_url/webArtisans/';
  //支付完成之后请求地址
  private $_send_url = 'http://localhost/paycenter/return_verify';
  //长连接变为短链接
  private $_changeUrl = 'http://localhost/Qrcode/shorturl';
  
  /**
	 * 获取广告位
	 * @access public
	 * @param number $source 平台
	 * @param number $num 获取广告位的数量
	 * @return unknown
	 */
	 public function getbanner($source=1, $num=3) {
	   
	 }
}
