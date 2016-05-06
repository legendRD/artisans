<?php
namespace Artisans\Model;
use Think\Model;
class CommModel extends Model {
      protected $autoCheckFields = false;
      private   $_sendmsg_url    = '/share/weixinLog/sendShortMsg/';
      
      //获取广告位
      public function getbanner($source = 1, $num = 0) {
             $where = array(
                 'apo.Osid'=>$source,
                 'ab.IsDelete'=>0
             );
             if($num > 0) {
                  $info = M()->table('app_banner_os apo')
                             ->join('app_banner ab ON apo.BannerId = ab.BannerId')
                             ->where($where)
                             ->order(' OrderId asc ')
                             ->limit($num)
                             ->field('api.BannerId, Osid, Title, ImgUrl, ImgCdnUrl, Url')
                             ->select();
             }else{
                  $info = M()->table('app_banner_os apo')
                             ->join('app_banner ab ON apo.Banner = ab.BannerId')
                             ->where($where)
                             ->order(' OrderId asc ')
                             ->limit($num)
                             ->field('apo.BannerId, Osid, Title, ImgUrl, ImgCdnUrl, Url')
                             ->select();
             }
             return $info;
      }
      
      //post
      public function postHttp($url, $arguments) {
             if(is_array($arguments)) {
                         $postData = http_build_query($arguments);
             }else{
                         $postData = $arguments;
             }
             $ch = curl_init();
             curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
             curl_setopt($ch, CURLOPT_URL, $url);
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
             curl_setopt($ch, CURLOPT_POST, 1);
             curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
             $returnValue = curl_exec($ch);
             curl_close($ch);
             return $returnValue;
      }
      
      //发送短信
      public function sendShortMsg($phone, $content, $source='') {
             import('@.Tool.soap');
             $soap = new \SoapClient('http://localhost/webservice/deliverMessage/SmsService.asmx?WSDL');
             $data = $saop->SendMesssage(array(
                   'UserName'=>'XXX', 
                   'Password'=>'XXX',
                   'Mobile'=>$phone,
                   'Contents'=>$content
             ));
             file_put_contents($this->_sendmsg_url.date('Ymd').'.txt', 
                                      date('Y-m-d H:i:s')."--->phone:".$phone.",source:".$source.",sendphone:".serialize($data)."\r\n", 
                                      FILE_APPEND);
             $hash = obj_to_arr($data);
             unset($data);
             return $hash;
      }
}
