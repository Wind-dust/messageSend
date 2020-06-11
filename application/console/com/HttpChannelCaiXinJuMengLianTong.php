<?php

namespace app\console\com;

use app\console\Pzlife;
use cache\Phpredis;
use Config;
use Env;
use Exception;
use think\Db;

//http 通道,通道编号10
class HttpChannelCaiXinJuMengLianTong extends Pzlife
{

    //杭州迈远
    public function content($content = 13)
    {
        return [
            'userid'      => '240664',
            'account'     => '240664',
            'password'    => '888KKK',
            'send_api'    => 'http://47.106.127.182:27507/mms/mt', //下发地址
            'call_api'    => '', //上行地址
            'overage_api' => '', //余额地址
            'receive_api' => '', //回执，报告
        ];

        //'account'    => 'yuxi',
        // 'appid'    => '674',
    }

    public function test(){
        $userid="240664";
        $account="240664";
        $password="888KKK";
        $mobile="15201926171";
        $title="来自【丝芙兰】：IAPM环贸广场店5.30盛大开业！";

        // $str=file_get_contents($file);

        $image = 'http://imagesdev.shyuxi.com/20200413/b29288993583d9cc5de893a8704fecf95e93cc10d9775.jpg';
        $imageinfo = base64_encode(file_get_contents($image));
        $content = '1.jpg,'.$imageinfo;

        $str = '【丝芙兰】IAPM环贸广场店5.30盛大开业！

        五重礼遇，邀您尊享！

        1.开业期间会员到店即可获赠惊喜开业礼盒（价值40元，共500份，送完即止）

        2.会员到店任意消费，即可获赠丝芙兰独家品牌福袋（内含2件丝芙兰独家品牌蔚蓝之美中样，价值60元，限量500份，送完即止）

        3.任意消费满688元，即可获赠丝芙兰大眼随身眼影盘或丝芙兰染唇膏一个（价值99元，限量500份，礼品随机，送完即止）

        4.任意消费满888元，即可获赠丝芙兰葡萄籽鲜活滋润喷雾一份（价值139元，限量300份，送完即止）

        * 如上两档满赠不同享。

        5. 开业起14天内，注册成为丝芙兰会员，到店消费尊享双倍积分。购满750元更可体验黑卡礼遇，5.30-6.3黑卡会员限时私享8折

        开业当天，更推出精彩的“迷你彩妆秀”，为您呈现当季美妆流行趋势。

        诚邀您的光临！ 
        地址：上海市徐汇区淮海中路999号上海环贸广场一层（L1）136,137及139室

        －－－－－－－－－－

        SEPHORA客服热线400-670-0055 

        SEPHORA官网: www.sephora.cn
        编辑短信TD回复至本号码，即可取消订阅';

        $str=iconv('utf-8','gb2312',$str);

        $str = base64_encode($str);

        $content = $content.";"."2.txt,".$str;
      /*   echo $content;
        die; */
        $content="1.txt,PGh0bWw+PGJvZHk+PGgxPkl0IHdvcmtzITwvaDE+PC9ib2R5PjwvaHRtbD4NCg==";

        $url='http://47.106.127.182:27507/mms/mt';
        $post_data='userid='.$userid.'&account='.$account.'&password='.$password.'&mobile='.$mobile.'&title='.$title.'&content='.$content;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $result = curl_exec($ch);
        curl_close($ch);

        echo $result;
    }

    public function Send()
    {
        $redis = Phpredis::getConn();
        // $a_time = 0;

        ini_set('memory_limit', '3072M'); // 临时设置最大内存占用为3G

        /*         $XML = '<?xml version="1.0" encoding="utf-8" ?>
        <returnsms>
        <statusbox>
        <mobile>15023239810</mobile>-------------对应的手机号码
        <taskid>1212</taskid>-------------同一批任务ID
        <status>10</status>---------状态报告----10：发送成功，20：发送失败
        <receivetime>2011-12-02 22:12:11</receivetime>-------------接收时间
        <errorcode>DELIVRD</errorcode>-上级网关返回值，不同网关返回值不同，仅作为参考
        <extno>01</extno>--子号，即自定义扩展号
        </statusbox>
        <statusbox>
        <mobile>15023239811</mobile>
        <taskid>1212</taskid>
        <status>20</status>
        <receivetime>2011-12-02 22:12:11</receivetime>
        <errorcode>2</errorcode>
        <extno></extno>
        </statusbox>
        </returnsms>
        ';
        $XML = json_decode(json_encode(simplexml_load_string($XML, 'SimpleXMLElement', LIBXML_NOCDATA)), true); */
        // print_r($XML);die;
        // $image = imagecreatefromjpeg('http://imagesdev.shyuxi.com/20191209/6b97bc91cda37dfbde62dba15b447ca85dee1b09a5251.jpg');
        // print_r(base64_encode(file_get_contents('http://imagesdev.shyuxi.com/20191209/6b97bc91cda37dfbde62dba15b447ca85dee1b09a5251.jpg')));die;

        $content                 = 97;
        $redisMessageCodeSend    = 'index:meassage:code:send:' . $content; //彩信发送任务rediskey
        $redisMessageCodeDeliver = 'index:meassage:multimediamessage:deliver:' . $content; //彩信MsgId
        $user_info               = $this->content();
        /*    $send                 = $redis->rPush($redisMessageCodeSend, json_encode([
        'mar_task_id' => 1,
        'mobile' => '13476024461',
        'content' =>'【鼎业装饰】鼎礼相祝！跨年巨惠！定单送欧派智能晾衣架一套。选欧派产品可秒杀欧派智能马桶999元一个。终极预存大礼，来店给你个超大的惊喜！！！大到超乎您想象！一年只有这一次！电话3236788回T退订',
        ])); */
        try {
            while (true) {
                $send_task    = [];
                $send_num     = [];
                $send_content = [];
                $send_title   = [];
                $receive_id   = [];
                $roallback    = [];
                $image_data   = [];
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
                            $send_title[$send_data['mar_task_id']] = $send_data['title'];
                            //处理内容
                            $real_send_content = '';
                            $vc                = '';
                            foreach ($send_data['content'] as $key => $value) {
                                if (!empty($value['content'])) {
                                    // $value['content'] = iconv('utf-8','gb2312',$value['content']);
                                    // $real_send_content .= $vc . $value['num'] . '.txt,' . base64_encode($value['content']);
                                    $real_send_content .= $vc . $value['num'] . '.txt,' . base64_encode(mb_convert_encoding($value['content'], 'gb2312', 'utf8'));
                                    // echo $value['content'];die;
                                    // echo base64_encode(mb_convert_encoding($value['content'], 'gb2312', 'utf8'));die;
                                    // echo base64_encode(iconv('utf-8','gb2312',$value['content']));die;
                                    // $real_send_content .= '.';

                                }
                                // $real_send_content .= $vc . $value['num'] . ',txt,' . base64_encode($value['content']);
                                if (!empty($value['image_path'])) {
                                   
                                    $md5 = md5(Config::get('qiniu.domain') . '/' . $value['image_path']);
                                    if (isset($image_data[$md5])) {
                                        if ($value['image_type'] == 'jpg') {
                                            // $real_send_content .= 'jpg,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                            $real_send_content .= $value['num'] .'.jpg,' . $image_data[$md5];
                                        } elseif ($value['image_type'] == 'gif') {
                                            $real_send_content .=$value['num'] . '.gif,' . $image_data[$md5];
                                            // $real_send_content .= 'gif,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                        }
                                    } else {
                                        $imagebase        = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                        $image_data[$md5] = $imagebase;
                                        // $frame['content'] =$imagebase;
                                        if ($value['image_type'] == 'jpg') {
                                            // $real_send_content .= 'jpg,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                            $real_send_content .= $value['num'] .'.jpg,' . $image_data[$md5];
                                        } elseif ($value['image_type'] == 'gif') {
                                            $real_send_content .= $value['num'] . '.gif,' . $image_data[$md5];
                                            // $real_send_content .= 'gif,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                        }
                                    }
                                }
                                $vc = ';';
                            }
                            // $send_content[$send_data['mar_task_id']] = $send_data['content'];
                            $send_content[$send_data['mar_task_id']] = $real_send_content;
                        } elseif (!in_array($send_data['mar_task_id'], $send_task)) {
                            $send_task[]                           = $send_data['mar_task_id'];
                            $send_title[$send_data['mar_task_id']] = $send_data['title'];
                            //处理内容
                            // $send_content[$send_data['mar_task_id']] = $send_data['content'];
                            $real_send_content = '';
                            $vc                = '';
                            foreach ($send_data['content'] as $key => $value) {
                                // $real_send_content .= $vc . $value['num'] . '.txt,' . base64_encode(mb_convert_encoding($value['content'], 'gb2312', 'utf8'));
                                if (!empty($value['content'])) {
                                    $real_send_content .= $vc . $value['num'] . '.txt,' . base64_encode(iconv('utf-8','gb2312',$value['content']));
                                }
                                if (!empty($value['image_path'])) {
                                    $real_send_content .= '.';
                                    /* if ($value['image_type'] == 'jpg') {
                                    $real_send_content .= 'jpg,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                    } elseif ($value['image_type'] == 'gif') {
                                    $real_send_content .= 'gif,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                    } */
                                    $md5 = md5(Config::get('qiniu.domain') . '/' . $value['image_path']);
                                    if (isset($image_data[$md5])) {
                                        if ($value['image_type'] == 'jpg') {
                                            // $real_send_content .= 'jpg,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                            $real_send_content .= $value['num'] .'.jpg,' . $image_data[$md5];
                                        } elseif ($value['image_type'] == 'gif') {
                                            $real_send_content .= $value['num'] . '.gif,' . $image_data[$md5];
                                            // $real_send_content .= 'gif,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                        }
                                    } else {
                                        $imagebase        = base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                        $image_data[$md5] = $imagebase;
                                        // $frame['content'] =$imagebase;
                                        if ($value['image_type'] == 'jpg') {
                                            // $real_send_content .= 'jpg,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                            $real_send_content .= $value['num'] .'.jpg,' . $image_data[$md5];
                                        } elseif ($value['image_type'] == 'gif') {
                                            $real_send_content .= $value['num'] . '.gif,' . $image_data[$md5];
                                            // $real_send_content .= 'gif,' . base64_encode(file_get_contents(Config::get('qiniu.domain') . '/' . $value['image_path']));
                                        }
                                    }
                                }
                                $vc = ';';
                            }
                            $send_content[$send_data['mar_task_id']] = $real_send_content;
                        }
                        $send_num[$send_data['mar_task_id']][] = $send_data['mobile'];
                        foreach ($send_num as $send_taskid => $num) {
                            $new_num = array_unique($num);
                            if (count($new_num) >= 5000) { //超出2000条做一次提交
                                $real_send = [];
                                $real_send = [
                                    'action'    => "send",
                                    'userid'    => $user_info['userid'],
                                    'account'   => $user_info['account'],
                                    'password'  => $user_info['password'],
                                    // 'timestamp' => date('YmdHis',time()),
                                    // 'sign' => strtolower(md5($user_info['username'].$user_info['password'].date('YmdHis',time()))),
                                    'mobile'    => join(',', $new_num),
                                    'starttime' => '',
                                    'title'     => $send_title[$send_taskid],
                                    'content'   => $send_content[$send_taskid],
                                    // 'content'   => urlencode($send_content[$send_taskid]),
                                ];

                                $res = sendRequest($user_info['send_api'], 'post', $real_send);
                                // $result = json_decode($res, true);
                                $result = explode(':', $res);
                                // $result['code'] = 2;
                                if (isset($result[1])) {
                                    $receive_id[$result[1]] = $send_taskid;
                                    $redis->hset('index:meassage:code:back_taskno:' . $content, $result[1], $send_taskid);
                                    unset($roallback[$send_taskid]);
                                } else {
                                    foreach ($roallback as $key => $value) {
                                        foreach ($value as $ne => $val) {
                                            $redis->rpush($redisMessageCodeSend, $val);
                                        }
                                    }
                                    exit(); //关闭通道
                                }

                                unset($send_num[$send_taskid]);
                                usleep(12500);
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
                        $real_send = [];
                        $real_send = [
                            'userid'    => $user_info['userid'],
                            'account'   => $user_info['account'],
                            'password'  => $user_info['password'],
                            // 'timestamp' => date('YmdHis',time()),
                            // 'sign' => strtolower(md5($user_info['username'].$user_info['password'].date('YmdHis',time()))),
                            'mobile'    => join(',', $new_num),
                            'starttime' => '',
                            'title'     => $send_title[$send_taskid],
                            'content'   => $send_content[$send_taskid],
                            // 'content'   => urlencode($send_content[$send_taskid]),
                        ];
                        // print_r($real_send);
                        $log_path = realpath("") . "/sign.log";
                        $myfile = fopen($log_path, 'w');

                        foreach ($real_send as $key => $value) {
                            fwrite($myfile, $key . ":" . $value . "\n");
                        }
                        fclose($myfile);
                        $res    = sendRequest($user_info['send_api'], 'post', $real_send);

                        $result = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                        print_r($result);
                        die;
                        // $result['code'] = 2;
                        if (isset($result[1])) {
                            $receive_id[$result[1]] = $send_taskid;
                            $redis->hset('index:meassage:code:back_taskno:' . $content, $result[1], $send_taskid);
                            unset($roallback[$send_taskid]);
                        } else {
                            foreach ($roallback as $key => $value) {
                                foreach ($value as $ne => $val) {
                                    $redis->rpush($redisMessageCodeSend, $val);
                                }
                            }
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
                        usleep(12500);
                    }
                }
                // $receive_id = [
                //     '866214' => '15745'
                // ];
                // print_r($receive_id);
                // die;
                $receive = sendRequest($user_info['receive_api'], 'post', ['userid' => $user_info['userid'], 'account' => $user_info['account'], 'password' => $user_info['password']]);
                if (empty($receive)) {
                    sleep(10);
                    continue;
                }

                $send_status  = 2;
                $receive_data = json_decode(json_encode(simplexml_load_string($receive, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                print_r($receive_data);
                // $receive = '1016497,15201926171,DELIVRD,2019-11-21 17:39:42';
                // $receive_data = explode(';', $receive);
                if (isset($receive_data['statusbox'])) {
                    foreach ($receive_data['statusbox'] as $key => $value) {
                        // $receive_info = [];
                        // $receive_info = explode(',', $value);
                        // $task_id      = $receive_id[$value['taskid']];
                        if (is_array($value)) {
                            $task_id = $redis->hget('index:meassage:code:back_taskno:' . $content, trim($value['taskid']));
                            $task    = $this->getSendTask($task_id);
                            if ($task == false) {
                                echo "error task_id" . "\n";
                            }
                            $stat          = $value['errorcode'];
                            $send_task_log = [];
                            if ($value['status'] == '10') {

                                $send_status = 3;
                                $stat        = 'DELIVRD';
                            } else {
                                $send_status = 4;
                            }
                            $send_task_log = [
                                'task_no'        => $task['task_no'],
                                'uid'            => $task['uid'],
                                'mobile'         => $value['mobile'],
                                'status_message' => $stat,
                                'send_status'    => $send_status,
                                'send_time'      => strtotime($value['receivetime']),
                            ];
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
                        } else {
                            $task_id = $redis->hget('index:meassage:code:back_taskno:' . $content, trim($receive_data['statusbox']['taskid']));
                            $task    = $this->getSendTask($task_id);
                            if ($task == false) {
                                echo "error task_id" . "\n";
                                break;
                            }
                            $stat          = $receive_data['statusbox']['errorcode'];
                            $send_task_log = [];
                            if ($receive_data['statusbox']['status'] == '10') {

                                $send_status = 3;
                                $stat        = 'DELIVRD';
                            } else {
                                $send_status = 4;
                            }
                            $send_task_log = [
                                'task_no'        => $task['task_no'],
                                'uid'            => $task['uid'],
                                'mobile'         => $receive_data['statusbox']['mobile'],
                                'status_message' => $stat,
                                'send_status'    => $send_status,
                                'send_time'      => strtotime($receive_data['statusbox']['receivetime']),
                            ];
                            $redis->rpush($redisMessageCodeDeliver, json_encode($send_task_log));
                            break;
                        }
                    }
                } else {
                    sleep(10);
                }
                // print_r($receive_data);die;
                // sleep(10);

                unset($send_num);
                unset($send_content);
                unset($receive_id);
                echo "success";
            }
        } catch (\Exception $th) {
            foreach ($roallback as $key => $value) {
                foreach ($value as $ne => $val) {
                    $redis->rpush($redisMessageCodeSend, $val);
                }
            }

            $log_path = realpath("") . "/error/" . $content . ".log";
            $myfile   = fopen($log_path, 'a+');
            fwrite($myfile, date('Y-m-d H:i:s', time()) . "\n");
            fwrite($myfile, $th . "\n");
            fclose($myfile);
            $redis->rpush('index:meassage:code:send' . ":" . 22, json_encode([
                'mobile'  => 15201926171,
                'content' => "【钰晰科技】邦之信移动彩信通道出现异常",
            ])); //三体营销通道
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
     * 返回数组的维度
     * @param  [type] $arr [description]
     * @return [type]      [description]
     */
    function arrayLevel($arr)
    {
        $al = array(0);
        function aL($arr, &$al, $level = 0)
        {
            if (is_array($arr)) {
                $level++;
                $al[] = $level;
                foreach ($arr as $v) {
                    aL($v, $al, $level);
                }
            }
        }
        aL($arr, $al);
        return max($al);
    }
}
