<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

//http 通道,通道编号10
class HttpChannelCaiXinMeiLian extends Pzlife
{

    //创蓝
    public function content($content = 59)
    {
        return [
            'username' => 'shyxyx',
            'apikey' => 'db9103c2ca1da121448932ddf4801329',
            'password' => 'asdf1234',
            'send_api'    => 'http://m.5c.com.cn/api/send/caixin_send.php ', //正式发送地址
            'test_api'    => '', //正式发送地址
            'call_api'    => '', //上行地址
            'call_back'    => 'm.5c.com.cn/api/recv/index.php', //回执地址
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

        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G

         $sign = '';
        $user_info               = $this->content();
        $time = time();
      
 
        $content                 = 63;
        $redisMessageCodeSend    = 'index:meassage:code:send:' . $content; //彩信发送任务rediskey
        $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver:' . $content; //彩信MsgId
        $user_info               = $this->content();
        /*    $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
        'mar_task_id' => 1,
        'mobile' => '13476024461',
        'content' =>'【鼎业装饰】鼎礼相祝！跨年巨惠！定单送欧派智能晾衣架一套。选欧派产品可秒杀欧派智能马桶999元一个。终极预存大礼，来店给你个超大的惊喜！！！大到超乎您想象！一年只有这一次！电话3236788回T退订',
        ])); */
          /* 接口调试代码 */
         /*  $sendtask = $send = $redis->lPop($redisMessageCodeSend);
          $send_data = json_decode($send, true);
          $redis->rpush($redisMessageCodeSend, $sendtask);
         ;
          $title =urlencode(mb_convert_encoding($send_data['title'],'UTF-8'));
          $content = $send_data['content'];
          $mobile = $send_data['mobile'];
          $mobile = 15601607386;
          $send_content = '';
          foreach ($content as $key => $value) {
              $send_content .= ';'; 
            if (!empty($value['content'])) {
                $send_content.= $value['num']."_1.txt,".base64_encode(mb_convert_encoding($value['content'],'UTF-8'));
            }
            if (!empty($value['image_path'])) {
                $type = explode('.', $value['image_path']);
                $send_content.= $value['num']."_2.".$type[1].",".base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
            }
          }
          $real_send = [
            'username'    => $user_info['username'],
            'apikey'    => $user_info['apikey'],
            'password'    => $user_info['password'],
            'mobile'    => $mobile,
            'title'    => $title,
            'content'    => $send_content,
            'encode'=>'utf8',
        ];
        $log_path = realpath("") . "/report.log";
        $myfile = fopen($log_path, 'w');

        foreach ($real_send as $key => $value) {
            fwrite($myfile, $key . ":" . $value . "\n");
        }
       
        $res = sendRequest($user_info['send_api'], 'post', $real_send);
        fclose($myfile);
        print_r($res);
          die; */

        while (true) {
            $send_task    = [];
            $send_num     = [];
            $send_content = [];
            $send_title   = [];
            $receive_id   = [];
            $roallback = [];
            // if (date('H') >= 18 || date('H') < 8) {
            //     exit("8点前,18点后通道关闭");
            // }
            do {
                $send = $redis->lPop($redisMessageCodeSend);
                // $redis->rpush($redisMessageCodeSend, $send);
                $send_data = json_decode($send, true);
                if ($send_data) {
                    $roallback[$send_data['mar_task_id']][] = $send;
                    if (empty($send_task)) {
                        $send_task[]                           = $send_data['mar_task_id'];
                        $send_title[$send_data['mar_task_id']] = urlencode($send_data['title']);
                        //处理内容
                        $content_api = '';
                        foreach ($send_data['content'] as $key => $value) {
                            $content .= ';'; 
                          if (!empty($value['content'])) {
                              $content_api.= $value['num']."_1.txt,".base64_encode(mb_convert_encoding($value['content'],'UTF-8'));
                          }
                          if (!empty($value['image_path'])) {
                              $type = explode('.', $value['image_path']);
                              $content_api.= $value['num']."_2.".$type[1].",".base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                          }
                        }
                       
                        // $send_content[$send_data['mar_task_id']] = $send_data['content'];
                        $send_content[$send_data['mar_task_id']] = $content_api;
                    } elseif (!in_array($send_data['mar_task_id'], $send_task)) {
                        $send_task[]                           = $send_data['mar_task_id'];
                        $send_title[$send_data['mar_task_id']] = urlencode($send_data['title']);
                        //处理内容
                     
                        $content_api = '';
                        foreach ($send_data['content'] as $key => $value) {
                            $content_api .= ';'; 
                          if (!empty($value['content'])) {
                              $content_api.= $value['num']."_1.txt,".base64_encode(mb_convert_encoding($value['content'],'UTF-8'));
                          }
                          if (!empty($value['image_path'])) {
                              $type = explode('.', $value['image_path']);
                              $content_api.= $value['num']."_2.".$type[1].",".base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                          }
                        }
                       
                        $send_content[$send_data['mar_task_id']] = $content_api;
                        
                    }
                    $send_num[$send_data['mar_task_id']][] = $send_data['mobile'];
                    foreach ($send_num as $send_taskid => $num) {
                        $new_num = array_unique($num);
                        if (count($new_num) >= 500) { //超出500条做一次提交
                            //单条测试
                            $real_send = [
                                'username'    => $user_info['username'],
                                'apikey'    => $user_info['apikey'],
                                'password'    => $user_info['password'],
                                'mobile'    => join(',', $new_num),
                                'title'    =>  $send_title[$send_taskid],
                                'content'    =>$send_content[$send_taskid],
                                'encode'=>'utf8',
                            ];

                            $res = sendRequest($user_info['send_api'], 'post', $real_send);
                            $result = explode(':', $res);
                            // $result['code'] = 2;
                            if ($result[0] == 'success') { //提交成功
                                $redis->hset('index:meassage:code:back_taskno:' . $content, $result[1], $send_taskid);
                                unset($roallback[$send_taskid]);
                            } else {
                                foreach ($roallback as $key => $value) {
                                    foreach ($value as $ne => $val) {
                                        $redis->rpush($redisMessageCodeSend, $val);
                                    }
                                }
                                print_r($result);
                                $redis->rpush('index:meassage:code:send' . ":" . 22, json_encode([
                                    'mobile'      => 15201926171,
                                    'content'     =>"【钰晰科技】美联软通彩信通道异常，错误信息：". $res
                                ])); //三体营销通道
                                exit(); //关闭通道
                            }
                            unset($send_num[$send_taskid]);
                            usleep(5000);
                        }
                    }
                }
            } while ($send);
            //剩下的号码再做提交
            // print_r($send_num);die;
            if (!empty($send_num)) {
                foreach ($send_num as $send_taskid => $num) {
                    $new_num = array_unique($num);
                    if (empty($new_num)) {
                        continue;
                    }
                    $real_send = [
                        'username'    => $user_info['username'],
                        'apikey'    => $user_info['apikey'],
                        'password'    => $user_info['password'],
                        'mobile'    => join(',', $new_num),
                        'title'    =>  $send_title[$send_taskid],
                        'content'    =>$send_content[$send_taskid],
                        'encode'=>'utf8',
                    ];
                    $res = sendRequest($user_info['send_api'], 'post', $real_send);
                    $result = json_decode($res, true);
                    if ($result['code'] == 1) {
                         $redis->hset('index:meassage:code:back_taskno:' . $content, $result[1], $send_taskid);
                        unset($roallback[$send_taskid]);
                    } else {
                        foreach ($roallback as $key => $value) {
                            foreach ($value as $ne => $val) {
                                $redis->rpush($redisMessageCodeSend, $val);
                            }
                        }
                         $redis->rpush('index:meassage:code:send' . ":" . 22, json_encode([
                                    'mobile'      => 15201926171,
                                    'content'     =>"【钰晰科技】美联软通彩信通道异常，错误信息：". $res
                                ])); //三体营销通道
                                exit(); //关闭通道
                    }
                    // print_r($res);

                    // $result = explode(',', $res);
                    // if ($result['returnstatus'] == 'Success') { //成功
                    //     $receive_id[$result['taskID']] = $send_taskid;
                    //     $redis->hset('index:meassage:code:back_taskno:' . $content, $result['taskID'], $send_taskid);
                    // } elseif ($result['returnstatus'] == 'Faild') { //失败
                    //     echo "error:" . $result['message'] . "\n";die;
                    // }
                    unset($send_num[$send_taskid]);
                    usleep(5000);
                }
            }

            //调取回执接口
            $call_send = [];
            $call_send = [
                'username'    => $user_info['username'],
                'apikey'    => $user_info['apikey'],
                'password_md5'    =>strtoupper(md5($user_info['password'])),
            ];
            $callback =  sendRequest($user_info['call_back'], 'post', $call_send);
            $send_status = 2;
            if ($callback != 'no record') {
                $all_receive = explode(';',$callback);
                foreach ($all_receive as $ckey => $receive) {
                    $real_receive = explode(',',$receive);
                    $task_id = $redis->hget('index:meassage:code:back_taskno:' . $content, $real_receive[0]);
                    if (empty($task_id)) {
                        continue;
                    }
                    $task    = $this->getSendTask($task_id);
                    if ($task == false) {
                        echo "error task_id" . "\n";
                    }
                    $send_task_log = [];
                    if ($real_receive[2] == 'DELIVRD') {
                        $send_status = 3;
                    } else {
                        $send_status = 4;
                    }
                    $send_task_log = [
                        'task_no'        => $task['task_no'],
                        'uid'            => $task['uid'],
                        'mobile'         => $real_receive[1],
                        'status_message' => $real_receive[2],
                        'send_status'    => $send_status,
                        'send_time'      => strtotime($real_receive[3]),
                    ];
                    // print_r($send_task_log);
                    $redis->rpush($redisMessageCodeDeliver, json_encode($send_task_log));
                    // Db::startTrans();
                    // try {
                    //     Db::table('yx_user_send_task_log')->insert($send_task_log);
                    //     Db::commit();
                    // } catch (\Exception $e) {
                    //     Db::rollback();
                    //     return ['code' => '3009']; //修改失败
                    // }
                    unset($send_status);
                }
            }
            // $receive_id = [
            //     '866214' => '15745'
            // ];
            // print_r($receive_id);
            // die;

            // print_r($receive_data);die;

            unset($send_num);
            unset($send_content);
            unset($receive_id);
            echo "success";
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
}
