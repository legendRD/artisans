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
    	
    	//获取XX
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
