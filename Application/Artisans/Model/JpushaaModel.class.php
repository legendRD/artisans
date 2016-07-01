<?php
namespace Artisans\Model;
use Think\Model;
use JPush\Model as M;
use JPush\JPushClient;
use JPush\Exception\APIConnectionException;
use JPush\Exception\APIRequestException;

class JpushaaModel extends Model {
      protected $autoCheckFields = false;
      //极光推送消息,格式如下
      /*$data['platform'] = 'all';
        $data['audience'] = '{"registration_id":["020ada8a498"]}';
        $data['msg_content'] = '内容';
        $data['title'] = '标题';
        $data['content_type'] = '类型';
        $data['extras'] = array("type"=>"10", "order_id"=>"20");*/
      //返回：ret['isok']  $ret['msg'];
      
      public function jpushpush($data) {
             import("@.tool.vendor.autoload");
             $client = new JPushClient(C('JPUSH_APP_KEY'), C('JPUSH_APP_SECRET'));
             if(!$data) {
                 $ret['isok'] = false;
                 $ret['msg']  = 'no data';
             }elseif(!$data['audience']) {
                 $ret['isok'] = false;
                 $ret['msg']  = 'no audience';
             }elseif($data && $data['audience']) {
                 $data['platform'] = $data['platform'] ? $data['platform'] : 'all';
                 $data['msg_content'] = $data['msg_content'] ? $data['msg_content'] : '内容';
                 $data['title'] = $data['title'] ? $data['title'] : '标题';
                 $data['content_type'] = $data['content_type'] ? $data['content_type'] : '类型';
                 $data['extras'] = array("type"=>'10', "order_id"=>'20');
                 
                 $result = $client->push()
                                  ->setPlatform($data['platform'])
                                  ->setAudience($data['audience'])
                                  ->setMessage(M\message($data['msg_content'], $data['title'], $data['content_type'], $data['extras']))
                                  ->send();
                
                $ret['isok'] = $result->isOk;
                $ret['msg']  = '';
                $ret['sendno'] = $result->sendno;
                
             }
      }
}
