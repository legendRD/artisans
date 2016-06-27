<?php
namespace Artisans\Controller;
class TestController extends CommonController {
      
      public function jpush_push() {
             $data['platform']    = $data['platform'] ? $data['platform'] : 'all';
             $data['audience']    = '{"registration_id":["020ada8a498"]}';
             $data['msg_content'] = '内容';
             $data['title']       = '标题';
             $data['content_type']= '类型';
             $data['extras']      = array("type"=>"10", "order_id"=>"20");
             $ret = D('Jpushaa')->jpushpush($data);
             dump($ret);
      }
}
