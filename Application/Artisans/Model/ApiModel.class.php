<?php
namespace Artisans\Model;
use Think\Model;
class ApiModel extends Model {
  
  protected $autoCheckFields    = false;
  private $_thirdlogin_url      = "http://localhost/user/login";                  //第三方登录
  private $_login_url           = "http://localhost/user/login";                  //用户登录
  private $_userinfo_url        = "http://localhost/user/getByUid"                //用户信息
  private $_get_token           = "http://localhost/user/auth";                   //获取接口token地址
  private $_access_token        = "";
  private $_shortmsg_verifycode = '/share/weixinLog/sendShortMsg/sendverify/';    //发送验证码log
  private $_user_api_reg        = '/UserCenterApi/regUser';                       //用户中心api
  
  /**
   * 支付获取商品名
   * @param int $order_id	订单号id
   * @return mixed
   */
  public function getOrderShopInfo($order_id) {
	   if(empty($order_id)) {
	     return false;
	   }
	   $order_shop_info	= M()->table('ord_orderinfo as oo')
	                         ->join(' left join ord_order_item as ooi on oo.OrderId=ooi.OrderId ')
	                         ->where(array('oo.OrderId'=>$order_id))->join('left join prd_productinfo as pp on pp.ProductId=ooi.ProductId')
		                       ->field('count(1) num,ooi.PackageId package_id,ooi.PackageName package_name,group_concat(ooi.ProductId) as pro_id,group_concat(ooi.ProductName)as pro_name,LogoImgUrl,LogoImgCdnUrl')
		                       ->group('oo.OrderId')
		                       ->find();
		 $tmp = array();
		 if($order_shop_info) {
		   if($order_shop_info['package_id']){
				$tmp['shop_name']	= $order_shop_info['package_name'];
				$tmp['shop_id']	= $order_shop_info['package_id'];
			 }elseif($order_shop_info['num']>1){
				$tmp['shop_name']	= $order_shop_info['pro_name'];
				$tmp['shop_id']	= $order_shop_info['pro_id'];
				$tmp['pro_img']	= $this->getImgUrl($order_shop_info['LogoImgCdnUrl'],$order_shop_info['LogoImgUrl']);
			 }else{
				$tmp['shop_name']	= $order_shop_info['pro_name'];
				$tmp['shop_id']	= $order_shop_info['pro_id'];
				$tmp['pro_img']	= $this->getImgUrl($order_shop_info['LogoImgCdnUrl'],$order_shop_info['LogoImgUrl']);
			 }
		 }
		 unset($order_shop_info);
		 return $tmp;
	 }
        
        /*获取产品列表
         *
         *必传
         *平台id PlatformId
         *城市id CityId
         *
         *可传
         *排序 1 正序 2 倒序 Sorting
         *类型 查码表的类型  ClassType
         *（是否分页）
         *当前页	     page
         *每页显示多少条产品 limit
         */
        public function getProductList($postData) {
        	
        	//拼接where条件
        	$where['rela.PlatformId']  =  $postData['PlatformId'];
		$where['rela.CityId']  =  $postData['CityId'];
		
		if(isset($postData['CityId']) && isset($postData['PlatformId'])) {
			
			//分类的筛选条件
			if(isset($postData['ClassType'])) {
				$where['rela.ClassType'] = $postData['ClassType'];
			}
			
			//排序方式的筛选
			if(isset($postData['Sorting']) && $postData['Sorting'] == 2) {
				$order['rela.Sorting'] = 'desc';
			}else{
				$order['rela.Sorting'] = 'asc';
			}
			
			$field[]   =   'rela.RelationshipId';
			$field[]   =   'prd.ProductId';
			$field[]   =   'prd.ProductName as name';
			$field[]   =   'prd.ProductTitle as title';
			$field[]   =   'prd.AddressInfo as addressInfo';
			$field[]   =   'prd.LogoImgUrl as classImg';
			$field[]   =   'prd.LogoImgCdnUrl as classImg_cdn';
			$field[]   =   'prd.DetailImgUrl as headImg';
			$field[]   =   'prd.DetailImgCdnUrl as headImg_cdn';
			$field[]   =   'prd.ProductType as prdtype';
			$field[]   =   'prd.Reserve1 as position';
			$field[]   =   'prd.Price as proPrice';
			
			$where['prd.IsShelves']  =  1;
			$where['prd.IsDelete']  =  0;
			
			if(isset($postData['limit']) && isset($postData['page'])) {
				$return['count'] = M('prd_product_platform_city')->where($where)->order($order)->count();
				$data = M('prd_product_platform_city as rela')
					->join("left join prd_productinfo prd on prd.ProductId=rela.ProductId")
					->where($where)
					->order($order)
					->field($field)
					->page($postData['page'], $postData['limit'])
					->select();
			}else{
				$data = M('prd_product_platform_city as rela')
					->join("left join prd_productinfo prd on prd.ProductId=rela.ProductId")
					->where($where)
					->field($field)
					->order($order)
					->select();
			}
		}
        }
}
