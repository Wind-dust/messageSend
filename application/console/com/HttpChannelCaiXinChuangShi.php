<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

//http 通道,通道编号10
class HttpChannelCaiXinChuangShi extends Pzlife
{

    //创蓝
    public function content($content = 59)
    {
        return [
            'account' => 'xd001768',
            'apikey' => '',
            'password' => 'cd3dds',
            'send_api'    => 'https://dx.ipyy.net/mms.aspx', //正式发送地址
            'test_api'    => '', //正式发送地址
            'call_api'    => '', //上行地址
            'call_back'    => 'https://dx.ipyy.net/statusJsonApi.aspx', //回执回调地址
            'overage_api' => '', //余额地址
            // 'receive_api' => 'http://api.1cloudsp.com/report/status', //回执，报告
        ];

        //'account'    => 'yuxi',
        // 'appid'    => '674',
    }

    public function Send()
    {
        $redis = Phpredis::getConn();
        // $a_time = 0;
        try {
            ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G

         $sign = '';
        $user_info               = $this->content();
        $time = time();
      
 
        $content                 = 174;
        $redisMessageCodeSend    = 'index:meassage:code:send:' . $content; //彩信发送任务rediskey
        $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver:' . $content; //彩信MsgId
        $user_info               = $this->content();
        // $send                 = $redis->rPush($redisMessageCodeSend, '{"mobile":"15201926171","title":"\u3010YSL\u5723\u7f57\u5170\u3011YSL \u730e\u5ba0\u6d3e\u300c\u5151\u300d\u5012\u8ba1\u65f65\u5929\uff0c\u5373\u5c06\u71c3\u60c5\u5f00\u573a","mar_task_id":92221,"content":[{"id":184229,"content":"","num":1,"image_path":"20200608\/6475740968addfecf66cf682ecfdeccd5ede0950e0dfa.jpg","image_type":"jpg"},{"id":184230,"content":"YSL\u3010\u9996\u6b21\u3011\u53cc\u500d\u79ef\u5206\u5373\u5c06\u5f00\u542f\uff0c\u591a\u6b3e\u6b63\u88c5\u661f\u54c1\u7b49\u4f60\u62a2\u5151\uff01\n\u5173\u6ce8\u5e76\u7ed1\u5b9aysl\u5b98\u65b9\u5fae\u4fe1\uff08yslbeauty\uff09\uff0c\u5f00\u542f\u60ca\u559c\u9650\u65f6\u5151\u793c\u3002\n\u5962\u793c\u730e\u5fc3\uff0c\u53ea\u4e3a\u6781\u81f4\u5ba0\u7231\uff0c\u66f4\u591a\u60ca\u559c\u7b49\u60a8\u8385\u4e34\u89e3\u9501\u3002\/\u56deTD\u9000\u8ba2 ","num":2,"image_path":"","image_type":""}],"from":"yx_user_multimedia_message","send_msg_id":"","uid":1}');
        // $message =  '{"mobile":"15172413692,15821193682","title":"\u3010YSL\u5723\u7f57\u5170\u3011YSL \u730e\u5ba0\u6d3e\u300c\u5151\u300d\u5012\u8ba1\u65f65\u5929\uff0c\u5373\u5c06\u71c3\u60c5\u5f00\u573a","mar_task_id":92221,"content":[{"id":184229,"content":"","num":1,"image_path":"20200608\/6475740968addfecf66cf682ecfdeccd5ede0950e0dfa.jpg","image_type":"jpg"},{"id":184230,"content":"YSL\u3010\u9996\u6b21\u3011\u53cc\u500d\u79ef\u5206\u5373\u5c06\u5f00\u542f\uff0c\u591a\u6b3e\u6b63\u88c5\u661f\u54c1\u7b49\u4f60\u62a2\u5151\uff01\n\u5173\u6ce8\u5e76\u7ed1\u5b9aysl\u5b98\u65b9\u5fae\u4fe1\uff08yslbeauty\uff09\uff0c\u5f00\u542f\u60ca\u559c\u9650\u65f6\u5151\u793c\u3002\n\u5962\u793c\u730e\u5fc3\uff0c\u53ea\u4e3a\u6781\u81f4\u5ba0\u7231\uff0c\u66f4\u591a\u60ca\u559c\u7b49\u60a8\u8385\u4e34\u89e3\u9501\u3002\/\u56deTD\u9000\u8ba2 ","num":2,"image_path":"","image_type":""}],"from":"yx_user_multimedia_message","send_msg_id":"","uid":1}';

        $callback = [];
        $sendTask = [];
        $SendMobile = [];
        $SendTitle = [];
        $nobile_num = 0;
        $task_num = 0;
        while(true){
            while(true){
                $send = $redis->lPop($redisMessageCodeSend);
                $send_message = json_decode($send,true);
                if (empty($send_message)){
                    break;
                }
                $callback[$send_message['mar_task_id']][] = $send; 
                //彩信内容处理
                if (!key_exists($send_message['mar_task_id'],$sendTask)) {
                    $array=array();
                    $element_split=array(0=>0);
                    $i = 0;
                    $realsend_content = '';
                    $nm = "\0";
                    $send_name = '';
                    $smil_start = '<smil> 
                    <head> 
                    <meta name="title" content=""/> 
                    <layout> 
                    <root-layout title="" width="240" height="320" background-color="#FFFFFF" color="#FF000008" font-size="8" font-family="Tahoma" font-style="" font-variant="" font-weight=""/> 
                    <region left="0%" top="0%" width="100%" height="50%" id="id1_text" z-index="0"/>
                    <region left="0%" top="50%" width="100%" height="50%" id="id1_img1" z-index="0" fit="meet"/>
                    </layout> 
                    </head>  
                    <body> ';
                    $smil_end = '</body> 
                    </smil>';
                    $part = '';
                    foreach ($send_message['content'] as $key => $value) {
                        $par = '';
                        $par = '<par dur="5s">';
                    if (!empty($value['content'])) {
                        
                            $i ++;
                            $semdname = $i.'.txt';
                            $par .= '<text region="id'.$i.'_text" src="'.$semdname.'"/>';
                            $len = 0;
                            $value['content'] =iconv( "UTF-8", "gb2312//IGNORE" , $value['content']);
                            $txt2_name=$semdname;
                            $txt2_name_bytes=$this->getBytes($txt2_name);
                            $txt2_len=strlen($value['content']);
                            $txt2_len_bytes=$this->integerToBytes($txt2_len);
                            // $txt2_bytes=$this->getFileBytes($value['content']);
                            $txt2_bytes=$this->getBytes($value['content']);
                            $array=array_merge($array,$txt2_name_bytes);
                            $array=array_merge($array,$element_split);
                            $array=array_merge($array,$txt2_len_bytes);
                            $array=array_merge($array,$txt2_bytes);
                            
                    }
                    
                    if (!empty($value['image_path'])) {
                            $i ++;
                            $semdname = $i.".".explode('.',$value['image_path'])[1];
                            $par .= '<img region="id'.$i.'_img1" src="'.$semdname.'"/>';
                            $image_info = file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']);
                        
                            // $jpg1_path=Config::get('qiniu.domain') . '/' . $value['image_path'];
                            $jpg1_name=$semdname;
                            $jpg1_name_bytes=$this->getBytes($jpg1_name);
                            // $jpg1_len=filesize($jpg1_path);
                            $jpg1_len=strlen($image_info);
                            $jpg1_len_bytes=$this->integerToBytes($jpg1_len);
                            // $jpg1_bytes=$this->getFileBytes($jpg1_path);
                            $jpg1_bytes=$this->getBytes($image_info);
            
                            $array=array_merge($array,$jpg1_name_bytes);
                            $array=array_merge($array,$element_split);
                            $array=array_merge($array,$jpg1_len_bytes);
                            $array=array_merge($array,$jpg1_bytes);
                            
                            // $i ++;
                    }
                    $par .= '</par>';
                    $part .= $par;
                    }
                    $i++;
                    // echo $part;
                    $smil =$smil_start.$part.$smil_end;
                    $smil_name='mms.smil';
                    $smil_name_bytes=$this->getBytes($smil_name);
                    // $smil_len=filesize($smil_path);
                    $smil_len=strlen($smil);
                    $smil_len_bytes=$this->integerToBytes($smil_len);
                    $smil_bytes=$this->getBytes($smil);
            
                    $array=array_merge($array,$smil_name_bytes);
                    $array=array_merge($array,$element_split);
                    $array=array_merge($array,$smil_len_bytes);
                    $array=array_merge($array,$smil_bytes);
                    $base64Str=base64_encode($this->toStr($array));
                    $sendTask[$send_message['mar_task_id']] = $base64Str;
                    $SendTitle[$send_message['mar_task_id']] = $send_message['title'];
                    $task_num++;
                    
                }
                $SendMobile[$send_message['mar_task_id']][] = $send_message['mobile'];
                $nobile_num ++;
                
                if ($task_num > 100 || $nobile_num >  2000) {
                    foreach ($SendMobile as $key => $value) {
                        $real_send = [];
                        $real_send = [
                            'account' => $user_info['account'],
                            'password' => $user_info['password'],
                            'mobile' => join(',',$value),
                            'subject' => $SendTitle[$key],
                            'content' => $sendTask[$key],
                            'sendTime' => '',
                            'action' => 'send',
                            // 'extno' => '6579',
                        ];
                        // print_r($real_send);
                        $res = sendRequest($user_info['send_api'], 'post', $real_send);
                       /*  $res = '<?xml version="1.0" encoding="utf-8" ?>
                        <returnsms>
                            <returnstatus>Success</returnstatus>
                            <message>操作成功</message>
                            <remainpoint>16</remainpoint>
                            <taskID>2010283731447233</taskID>
                            <successCounts>1</successCounts>
                        </returnsms>
                        '; */
                        // print_r($callback);
                        $receive_data = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                        if ($receive_data['returnstatus'] == 'Success') {//发送成功
                            unset($SendMobile[$key]);
                            unset($SendTitle[$key]);
                            unset($sendTask[$key]);
                            unset($callback[$key]);
                            $redis->hset('index:meassage:code:back_taskno:' . $content, $receive_data['taskID'], $SendMobile['mar_task_id']);
                            $task_num =0;
                            $nobile_num =0;
                        }else{
                            foreach ($callback as $key => $value) {
                                foreach ($value as $ne => $val) {
                                    $redis->rpush($redisMessageCodeSend, $val);
                                }
                            }
                            $this->writeToRobot($content,$receive_data,'创世彩信通道');
                            exit;
                        }
                       
                        // print_r($receive_data);
                    }
                }

                $real_send = [];
                $real_send = [
                    'account' => $user_info['account'],
                    'password' => $user_info['password'],
                    'mobile' => $send_message['mobile'],
                    'subject' => $send_message['title'],
                    'content' => $base64Str,
                    'sendTime' => '',
                    'action' => 'send',
                    // 'extno' => '6579',
                ];
            
                // $res = sendRequest($user_info['send_api'], 'post', $real_send);
                // print_r($send_message['title']);
                // $receive_data = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                // if ($receive_data == 'Success') {
                    
                // }
                // print_r($receive_data);
                // die;
            }
            if (!empty($SendMobile)){
                // print_r($SendMobile);
                // print_r($SendTitle);
                // print_r($sendTask);
                foreach ($SendMobile as $key => $value) {
                    $real_send = [];
                    $real_send = [
                        'account' => $user_info['account'],
                        'password' => $user_info['password'],
                        'mobile' => join(',',$value),
                        'subject' => $SendTitle[$key],
                        'content' => $sendTask[$key],
                        'sendTime' => '',
                        'action' => 'send',
                        // 'extno' => '6579',
                    ];
                    // print_r($real_send);
                    $res = sendRequest($user_info['send_api'], 'post', $real_send);
                   /*  $res = '<?xml version="1.0" encoding="utf-8" ?>
                    <returnsms>
                        <returnstatus>Success</returnstatus>
                        <message>操作成功</message>
                        <remainpoint>16</remainpoint>
                        <taskID>2010283731447233</taskID>
                        <successCounts>1</successCounts>
                    </returnsms>
                    '; */
                    // print_r($callback);
                    $receive_data = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                    if ($receive_data['returnstatus'] == 'Success') {//发送成功
                        unset($SendMobile[$key]);
                        unset($SendTitle[$key]);
                        unset($sendTask[$key]);
                        unset($callback[$key]);
                        $redis->hset('index:meassage:code:back_taskno:' . $content, $receive_data['taskID'], $SendMobile['mar_task_id']);
                    }else{
                        foreach ($callback as $key => $value) {
                            foreach ($value as $ne => $val) {
                                $redis->rpush($redisMessageCodeSend, $val);
                            }
                        }
                        $this->writeToRobot($content,$receive_data,'创世彩信通道');
                        exit;
                    }
                   
                    // print_r($receive_data);
                }
             
            }

            //回执
           
            // $getreceiptrequest = [];
            // $getreceiptrequest = [
            //     'account' => 'xd001768',
            //     'password' => 'B7BF78F9FFF98594387B4B932442C801',
            // ];
            // $getreceiptrequest = sendRequest($user_info['call_back'], 'post', $getreceiptrequest);
            $receipt_info = '{"error":"1","remark":"成功","statusbox":[{"mobile":"15201926171","taskid":"2010283347066108","receivetime":"2020-10-28 09:47:07","errorcode":"MY:0001"},{"mobile":"15821193682","taskid":"2010283428129746","receivetime":"2020-10-28 10:30:39","errorcode":"DELIVRD"},{"mobile":"15172413692","taskid":"2010283428129746","receivetime":"2020-10-28 10:30:39","errorcode":"DELIVRD"},{"mobile":"15201926171","taskid":"2010283402254813","receivetime":"2020-10-28 10:25:38","errorcode":"DELIVRD"},{"mobile":"15201926171","taskid":"2010283731447233","receivetime":"2020-10-28 13:50:32","errorcode":"DELIVRD"}]}';
            $receipt_info_array = [];
            $receipt_info_array = json_decode($receipt_info,true);
            if ($receipt_info_array['statusbox']){
                foreach ($receipt_info_array['statusbox'] as $key => $value) {
                    $task_id = $redis->hGet('index:meassage:code:back_taskno:'.$content, $value['taskid']);
                    if ($task_id) {
                        $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver'; //创蓝彩信回执通道
                        $stat = $value['errorcode'];
                        
                        $send_task_log = [
                                'task_id'        => $task_id,
                                'mobile'         => $value['mobile'],
                                'status_message' => $stat,
                                'send_time'      => strtotime($value['receivetime']),
                            ];
                        $redis->rpush($redisMessageCodeDeliver, json_encode($send_task_log));
                    }
                    
                }
            }
           
            sleep(1);
            // print_r($send_message);die;
              /* 接口调试代码 */
            
            // print_r($send_message['content']);
            
        }
       
        
        } catch (\Exception $th) {
            //throw $th;
            // exception($th);
            foreach ($callback as $key => $value) {
                foreach ($value as $ne => $val) {
                    $redis->rpush($redisMessageCodeSend, $val);
                }
            }
            $this->writeToRobot($content,$receive_data,'创世彩信通道');
            exit;
        }
       

       
    }

    public function getSendTask($id)
    {
        $task = Db::query("SELECT `task_no`,`uid` FROM yx_user_multimedia_message WHERE `id` =" . $id);
        if ($task) {
            return $task[0];
        }
        return false;
    }

    /**
    * 将字符串转换成二进制
    * @param type $str
    * @return type
    */
    private function StrToBin($str){
        //1.列出每个字符
        $arr = preg_split('/(?<!^)(?!$)/u', $str);
        //2.unpack字符
        foreach($arr as &$v){
            $temp = unpack('H*', $v);
            $v = base_convert($temp[1], 16, 2);
            unset($temp);
        }

        return join('',$arr);
    }

    /**
    * 将二进制转换成字符串
    * @param type $str
    * @return type
    */
    private function BinToStr($str){
        $arr = explode(' ', $str);
        foreach($arr as &$v){
            $v = pack("H".strlen(base_convert($v, 2, 16)), base_convert($v, 2, 16));
        }
        return join('', $arr);
    }

        
    /** 
         
    * 转换一个String字符串为byte数组 
        
    * @param $str 需要转换的字符串 
        
    * @param $bytes 目标byte数组 
        
    * @author Zikie 
        
    */
    public static function getBytes($string) { 
        $bytes = array(); 
        for($i = 0; $i < strlen($string); $i++){ 
            $bytes[] = ord($string[$i]); 
        } 
        return $bytes; 
    } 

    private function Md5Encrypt($text){
        $ret = md5($text);
        return strtoupper($ret);
    }
    
    // private function getBytes($string) {
    //     $bytes = array();
    //     for($i = 0; $i < strlen($string); $i++){
    //         $bytes[] = ord($string[$i]);
    //     }
    //     return $bytes;
    // }
    
    private function toStr($bytes) {
        $str = '';
        foreach($bytes as $ch) {
            $str .= chr($ch);
        }
        return $str;
     }
    
     private function integerToBytes($val){
        $val=(int)$val;
        $byte=array();
        $byte[0]=($val & 0xFF);
        $byte[1]=($val >> 8  & 0xFF);
        $byte[2]=($val >> 16 & 0xFF);
        $byte[3]=($val >> 24 & 0xFF);
        return $byte;
    }
    
    //��ȡ�ļ����ֽ�����
    private function getFileBytes($path){
        $file=fopen($path,'rb');
        $arrayy=fread($file,filesize($path));
        return getBytes($arrayy);
    }

    function writeToRobot($content, $error_data, $title)
    {
        $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=fa1c9682-f617-45f9-a6a3-6b65f671b457';
        // $api = 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=693a91f6-7xxx-4bc4-97a0-0ec2sifa5aaa';
        $check_data = [];
        $check_data = [
            'msgtype' => "text",
            'text' => [
                "content" => "Hi，错误提醒机器人\n您有一条通道出现故障\n通道编号【" . $content . "】\n【错误信息】：" . $error_data . "\n通道名称【" . $title . "】",
            ],
        ];
        $headers = [
            'Content-Type:application/json'
        ];
        $this->sendRequest2($api, 'post', $check_data, $headers);
    }

    function sendRequest2($requestUrl, $method = 'get', $data = [], $headers)
    {
        $methonArr = ['get', 'post'];
        if (!in_array(strtolower($method), $methonArr)) {
            return [];
        }
        if ($method == 'post') {
            if (!is_array($data) || empty($data)) {
                return [];
            }
        }
        $curl = curl_init(); // 初始化一个 cURL 对象
        curl_setopt($curl, CURLOPT_URL, $requestUrl); // 设置你需要抓取的URL
        curl_setopt($curl, CURLOPT_HEADER, 0); // 设置header 响应头是否输出
        if ($method == 'post') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Chrome/53.0.2785.104 Safari/537.36 Core/1.53.2372.400 QQBrowser/9.5.10548.400'); // 模拟用户使用的浏览器
        }
        // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        // 1如果成功只将结果返回，不自动输出任何内容。如果失败返回FALSE
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($curl); // 运行cURL，请求网页
        curl_close($curl); // 关闭URL请求
        return $res; // 显示获得的数据
    }
}
