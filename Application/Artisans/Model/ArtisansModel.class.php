<?php
namespace Artisans\Model;
use Think\Model;
class ArtisansModel extends Model{
  
  protected $autoCheckFields = False;
  
  private $_getTrade_url = 'http://localhost/order/addor';	       //生成订单号地址
  private $_orderPay_url = 'http://localhost/Paycenter/CreatePayInfo'; //支付接口
  private $_getStatus    = 'http://localhost/order/list';              //获取订单状态地址
  private $_pay_log 	 = '/share/pay_log_url/webArtisans/';	       //更新用户状态和XX
  private $_send_url 	 = 'http://localhost/paycenter/return_verify'; //支付完成之后请求地址
  private $_changeUrl    = 'http://localhost/Qrcode/shorturl';	       //长连接变为短链接
  
  /**
   * 获取广告位
   * @access public
   * @param number $source 平台
   * @param number $num 获取广告位的数量
   * @return unknown
   */
  public function getbanner($source=1, $num=3) {
	        $where = array(
	        	'apo.Osid'=>$source,
	        	'ab.IsDelete'=>0
	        );
	        if($num>0) {
	        	$info = M()->table('app_banner_os apo')
	        		   ->join('app_banner ab ON apo.BannerId = ab.BannerId')
	        		   ->where($where)
	        		   ->order(' OrderId asc ')
	        		   ->limit($num)
	        		   ->field('apo.BannerId, Osid, Title, ImgUrl, ImgCdnUrl, Url')
	        		   ->select();
	        }else{
	        	$info = M()->table('app_banner_os apo')
	        		   ->join('app_banner ab ON apo.BannerId = ab.BannerId')
	        		   ->where($where)
	        		   ->order(' OrderId asc ')
	        		   ->field('apo.BannerId, Osid, Title, ImgUrl, ImgCdnUrl, Url')
	        		   ->select();
	        }
	        return $info;
    }
	 
    /**
     * 获取单个产品信息
     * @access public
     * @param int $pro_id 产品id 
     * @param int $city_id 城市id
     * @param int $is_shelves 是否上架
     * @return boolean|Ambigous <multitype:, multitype:unknown >
     */	 
     public function getOenProInfo($pro_id, $city_id, $is_shelvs=true) {
     	    if(!($pro_id && $city_id)) {
     	    	 return false;
     	    }
     	    $where = array(
     	    	 'ppc.`CityId`'=>$city_id,
     	    	 'ppc.`ProductId`'=>$pro_id,
     	    	 'ppinfo.IsDelete'=>0
     	    );
     	    if($is_shelves) {
     	    	$where['ppinfo.IsShelves'] = 1;
     	    }
     	    $info = $this->getProInfo($where);
     	    return $info;
     }
     
     /**
      * 获取某个城市下的产品信息
      * @access public 
      * @param int $city_id 城市id
      * @param int $is_shelves 获取上架的产品,0获取所有的产品
      * @return mixed
      */
      public function getCityProInfo($city_id, $is_shelves, $num=0) {
      	     if(empty($city_id)) {
      	     	return false;
      	     }
      	     $where = array(
      	     	    'ppc.`CityId`'=>$city_id,
      	     	    'ppinfo.IsDelete'=>0
      	     );
      	     if($is_shelves) {
      	     	    $where['ppinfo.IsShelves'] = 1;
      	     }
      	     $info = $this->getProInfo($where, $num);
      	     return $info;
      }
      
      /*
       * 获取产品信息
       * @access public
       * @param array $where	查询的where条件
       * @return array
       */
       public function getProInfo($where, $num=0) {
       	      $num = (int)$num;
       	      if($num) {
       	      	        $info = M()->table('prd_product_city ppc')
       	      	        	   ->join('left join prd_productinfo ppinfo on ppc.`ProductId`=ppinfo.`ProductId`')
       	      	        	   ->where($where)
       	      	        	   ->order(' ppc.`Orderid` asc ')
       	      	        	   ->limit($num)
       	      	        	   ->field('ppinfo.`ProductId` as product_id, ppinfo.`IsShelves` as is_shelves, `ProductName` as product_name, `Price` as base_price, `ProductTitle` as title, LogoImgUrl, LogoImgCdnUrl, DetailImgUrl, DetailImgCdnUrl')
       	      	        	   ->select();
       	      }else{
       	      		$info = M()->table('prd_product_city ppc')
       	      	        	   ->join('left join prd_productinfo ppinfo on ppc.`ProductId`=ppinfo.`ProductId`')
       	      	        	   ->where($where)
       	      	        	   ->order(' ppc.`Orderid` asc ')
       	      	        	   ->field('ppinfo.`ProductId` as product_id, ppinfo.`IsShelves` as is_shelves, `ProductName` as product_name, `Price` as base_price, `ProductTitle` as title, LogoImgUrl, LogoImgCdnUrl, DetailImgUrl, DetailImgCdnUrl')
       	      	        	   ->select();
       	      }
       	      $tmp = array();
       	      if($info && is_array($info)) {
       	      		  foreach($info as $value) {
       	      		  	  if($value['product_id']) {
       	      		  	  	$promotion_info = array();
       	      		  	  	$promotion_info = $this->getPromotion($value['product_id']);
       	      		  	  	if($promotion_info['PromotionId']) {
       	      		  	  		$value['true_price'] = $promotion_info['ActivityPrice'];
       	      		  	  		$value['discount']   = $promotion_info['Discount'];
       	      		  	  	}else{
       	      		  	  		$value['true_price'] = $value['base_price']
       	      		  	  		$value['discount']   = '';
       	      		  	  	}
       	      		  	  	$tmp[] = $value;
       	      		  	  }
       	      		  }
       	      }
       	      return $tmp;
       }
       
       /**
	 * 获取产品促销信息
	 * @param int $pro_id
	 * @return mixed
	 */
       public function getPromotion($pro_id) {
       	      if(empty($pro_id)) {
       	      	       return false;
       	      }
       	      if(!is_numeric($pro_id)) {
       	      	       return false;
       	      }
       	      $time = date('Y-m-d H:i:s');
       	      $where = array(
       	      	     'ProductId'=>$pro_id,
       	      	     'StartTime'=>array('elt', $time),
       	      	     'EndTime'  =>array('egt', $time);
       	      	     'IsDelete' =>0,
       	      	     'IsUse'    =>1
       	      );
       	      $promotion_info = M('prd_promotion')->where($where)->find();
       	      return $promotion_info;
       }
       
       /**
	 * 获取城市信息
	 * @access public
	 * @param string $city 城市名
	 * @return mixed
	 */
	 public function getCityInfo($city_name) {
	 	if(empty($city_name)) {
	 		return false;
	 	}
	 	$city_info = M('sys_city')->where('CityName'=>array('like', $city_name.'%'))->find();
	 	return $city_info;
	 }
	 
	 public function cityInfo($city_id) {
	 	if(empty($city_id)) {
	 		return false;
	 	}
	 	$city_info = M('sys_city')->find($city_id);
	 	return $city_info;
	 }
	 
	 /*
	  * 获取城市下的XXX
	  * @param     array $var
	  * city_id    int   城市id 【必传】
	  * lng 经度
	  * lat 纬度
	  * order_type int   排序【3距离， 1好评率， 2完成订单量】
	  * currpage   int   当前页
	  * page_size  int   显示条数
	  * @return    mixed
	  */
	  public function getCityUserInfo($var) {
	  	
	  }
}
