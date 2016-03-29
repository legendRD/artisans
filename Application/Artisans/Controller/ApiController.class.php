<?php
namespace Artisans\Controller;
class ApiController extends CommonController {
  
  //获取产品列表接口
  // 必传
		// 平台id  PlatformId
		// 城市id  CityId
	// 可传
		// 排序  1 正序    2倒序  Sorting
		// 类型  查码表的类型     ClassType   
		// （是否分页）
		// 当前页                  page
		// 每页显示多少条产品      limit
      public function getProductList($param = null) {
             if(isset($param)) {
             	$postData = $param;
             }else{
             	$postData = I('param.');
             }
             
             $where['rela.PlatformId'] = $postData['PlatformId'];
             $where['rela.CityId']     = array('in', array('0', $postData['CityId']));
             
             if(isset($postData['CityId']) && isset($postData['PlatformId'])) {
             	      //分类的筛选条件
             	      if(isset($postData['ClassType'])) {
             	      		$where['rela.ClassType'] = $postData['ClassType'];
             	      }
             	      if(isset($postData['Sorting']) && $postData['Sorting'] == 2) {
             	      		$order['rela.Sorting'] = 'desc';
             	      }else{
             	      		$order['rela.Sorting'] = 'asc';
             	      }
             	      
             	      $field[] = 'rela.RelationshipId';
             	      $field[] = 'prd.ProductId';
             	      $field[] = 'prd.ProductName as name';
             	      $field[] = 'prd.ProductTitle as title';
             	      $field[] = 'prd.AddressInfo as addressInfo';
             	      $field[] = 'prd.LogoImgUrl as classImg';
             	      $field[] = 'prd.LogoImgCdnUrl as classImg_cdn';
             	      $field[] = 'prd.DetailImgUrl as headImg';
             	      $field[] = 'prd.DetailImgCdnUrl as headImg_cdn';
             	      $field[] = 'prd.ProductType as prdtype';
             	      $field[] = 'prd.Reservel as position';
             	      $field[] = 'prd.Price as proPrice';
             	      $field[] = 'prd.Reserve2 as colorClass';
             	      
             	      $where['prd.IsShelves'] = is_numeric($postData['IsShelves']) ? $postData['IsShelves']:1;
             	      $where['prd.IsDelete']  = 0;
             	      
             	      if(isset($postData['limit']) && isset($postData['page'])) {
             	      		$return['count'] = M('prd_product_platform_city as rela')
             	      				   ->join('left join prd_productinfo prd on prd.ProductId = rela.ProductId')
             	      				   ->where($where)
             	      				   ->order($order)
             	      				   ->count();
             	      		
             	      		$data = M('prd_product_platform_city')
             	      			->join('left join prd_productinfo prd on prd.ProductId = rela.ProductId')
             	      			->where($where)
             	      			->order($order)
             	      			->field($field)
             	      			->page($postData['page'], $postData['limit'])
             	      			->select();
             	      }else{
             	      	
             	      }
             }
      }
      
   // 获取产品详情的接口 
     // 平台id    ProductId
  	// 城市id    CityId
  	// 产品Id    PlatformId
      public function getProductInfo($param = null) {
        
      }
      
      //获取XXX列表接口
      // 必传
    	// 城市id    City
    	// 经纬度    lat  lng
    	// 时间点    Capacity  TimeId
    	// 产品Id    ProductId
    
    	// 可传
    	// 分页      page  limit
    	// 排序规则  order        
      public function getCraftsManList($param = null) {
        
      }
      
      //获取XXX产品列表接口
      //XXXId CraftsmanId
      public function getArtisansProductList($param = null) {
        
      }
      
      //获取XXX的详细信息接口
      // 手艺人Id    CraftsmanId
	    // 经纬度 lat lng
      public function getCraftsManInfo($param = null) {
        
      }
      
      //获取XXX的评价信息
      // 手艺人Id
    	// 产品Id
    	// 分页  page limit
    	public function getEvaluationList($param = null) {
    	  
    	}
    	
    	//获取产品关键步骤
    	public function getProductStep($post_data = null) {
    	  
    	}
    	
    	// 获取XX接口
    	// 产品Id    ProductId
    	// 城市Id    CityId
    	// 日期      Date
    
    	// 可传参数  CraftsmanId
    	public function capacityByCityProIdDate($param = null) {
    	  
    	}
    	
    	// 更新订单状态接口
    	public function updateOrderStatus($param = null) {
    	  
    	}
    	
    	    //发送验证码接口
    	    // 电话号 phone
	    // 平台来源 type
	    public function SendCode($param = null) {
	      
	    }
	    
	    //验证验证码接口
	    // 电话号 phone
      	    // 验证码 code
	    public function CheckCode($param = null) {
	      
	    }
	    
	    //保存评价接口
	    public function SaveEvaluation($param = null) {
	      
	    }
	    
	    public function api() {
	           C('TMPL_L_DELIM', '<{');
	           C('TMPL_R_DELIM', '>}');
	           $this->display('api');
	    }
	    
	    //获取XXX基本信息接口
	    public function getcraftinfo() {
	      
	    }
}
