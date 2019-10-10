<?php

namespace app\console\com;

use app\console\Pzlife;
use Config;
use Env;
use Overtrue\Pinyin\Pinyin;
use think\Db;
use cache\Phpredis;

class TemporaryScript extends Pzlife {
    private $redis;

    public function __construct() {
        parent::__construct();
        $this->redis = Phpredis::getConn();
    }

    /**
     * 修改数据库脚本
     *
     */
    public function ModifyDataScript() {
       Db::startTrans();
       try {
//            /* 将大鲨鱼(13122511746)钻石购买关系挂在黄甍(13381867868)下面  2019/04/12 */
//
//            $user    = Db::query("SELECT * FROM pz_users WHERE mobile = 13122511746 AND delete_time=0 ");
//            $up_user = Db::query("SELECT * FROM pz_users WHERE mobile = 13381867868 AND delete_time=0 ");
//            Db::table('pz_diamondvip_get')->where('uid', $user[0]['id'])->update(['share_uid' => $up_user[0]['id']]);
//
//            /* 画(18033698601)钻石购买关系挂在葛小薇(13280730253)下面  2019/04/12 */
//            $user    = Db::query("SELECT * FROM pz_users WHERE mobile = 18033698601 AND delete_time=0 ");
//            $up_user = Db::query("SELECT * FROM pz_users WHERE mobile = 13280730253 AND delete_time=0 ");
//            Db::table('pz_diamondvip_get')->where('uid', $user[0]['id'])->update(['share_uid' => $up_user[0]['id']]);
//
//            /* 画(18033698601)层级关系挂在葛小薇(13280730253)下面  2019/04/12 */
//            $user    = Db::query("SELECT * FROM pz_users WHERE mobile = 18033698601 AND delete_time=0 ");
//            $up_user = Db::query("SELECT * FROM pz_users WHERE mobile = 13280730253 AND delete_time=0 ");
//            Db::table('pz_user_relation')->where('uid', $user[0]['id'])->update(['relation' => $up_user[0]['id'] . ',' . $user[0]['id'], 'pid' => $up_user[0]['id']]);
//
//            /* 开店关系修正
//            应江总（江胜）要求开店邀请未接收到邀请者ID造成的数据出错
//            王恒念(13914041717)店铺开通关系关系归属于张学军(13606221728)  2019/04/15 */
//            Db::table('pz_shop_apply')->where('id', 3)->update(['refe_uid' => 15122, 'refe_uname' => '张学军', 'create_time' => '1555218828']);
//            Db::table('pz_user_relation')->where('uid', '26379')->update(['relation' => '15122,26379', 'pid' => '15122']);
//            Db::table('pz_log_invest')->where('id', 75)->update(['uid' => 15122]);
//            Db::table('pz_shop_apply')->where('id', 2)->update(['create_time' => '1555210837']);
//            Db::table('pz_shop_apply')->where('id', 1)->update(['create_time' => '1555139928']);
//
//            /* 老商城相关账户明细  2019/04/15 */
//
//            $mysql_connect = Db::connect(Config::get('database.db_config'));
//            $memberdata    = $mysql_connect->query("SELECT `mw`.`unionid`,`m`.* FROM pre_member_wxunion AS mw LEFT JOIN pre_member AS m USING(`uid`) ");
//            foreach ($memberdata as $key => $value) {
//                $member_count = $mysql_connect->query('SELECT * FROM pre_member_count WHERE `uid` = ' . $value['uid']);
//                $new_user     = [];
//                if ($member_count) {
//                    $new_user['balance']    = $member_count[0]['redmoney'];
//                    $new_user['commission'] = $member_count[0]['commission'];
//                    $new_user['integral']   = $member_count[0]['bonuspoints'];
//                    if ($member_count[0]['bonuspoints'] > 0) { //积分
//                        Db::table('pz_log_integral')->insert(
//                            [
//                                'order_no'        => '',
//                                'uid'             => $value['uid'],
//                                'result_integral' => $member_count[0]['bonuspoints'],
//                                'stype'           => 3,
//                                'status'          => 2,
//                                'message'         => '老商城转入',
//                                'create_time'     => 1552147200,
//                                'delete_time'     => 0,
//                            ]
//                        );
//                    }
//                    //佣金
//                    if ($member_count[0]['commission'] > 0) {
//                        Db::table('pz_log_trading')->insert(
//                            [
//                                'uid'          => $value['uid'],
//                                'trading_type' => 2,
//                                'change_type'  => 11,
//                                'money'        => $member_count[0]['commission'],
//                                'befor_money'  => 0,
//                                'after_money'  => $member_count[0]['commission'],
//                                'change_type'  => 11,
//                                'message'      => '老商城转入',
//                                'create_time'  => 1552147200,
//                            ]
//                        );
//                    }
//                    //商券
//                    if ($member_count[0]['redmoney'] > 0) {
//                        Db::table('pz_log_trading')->insert(
//                            [
//                                'uid'          => $value['uid'],
//                                'trading_type' => 1,
//                                'change_type'  => 11,
//                                'money'        => $member_count[0]['redmoney'],
//                                'befor_money'  => 0,
//                                'after_money'  => $member_count[0]['redmoney'],
//                                'change_type'  => 11,
//                                'message'      => '老商城转入',
//                                'create_time'  => 1552147200,
//                            ]
//                        );
//                    }
//                }
//            }
            // 提交事务

            /* 将支改改(13661691673)登录手机号改为(17891936793)  2019/04/23 */
            // $user    = Db::query("SELECT * FROM pz_users WHERE mobile = 13661691673 AND delete_time=0 ");
            // Db::table('pz_users')->where('id', $user[0]['id'])->update(['mobile' => '17891936793']);

            /* 因政策调整，所有合伙人免费钻石卡全部关停作废 */
            // Db::query("UPDATE `pz_diamondvips` SET `status` = 3 WHERE `delete_time` = 0");

            /* 2019/05/25 挂人关系调整 活动用户挂在王金  23926 下面*/
    /*         $user    = Db::query("SELECT * FROM pz_users WHERE `create_time` > '1558713600' AND `create_time` < '1558780200' AND delete_time=0 ");
            foreach ($user as $key => $value) {
                $relation = Db::query("SELECT * FROM pz_user_relation WHERE `uid` = ".$value['id']);
                if ($relation) {
                    $pid = $relation[0]['pid']== 1  ? 23926 : $relation[0]['pid'];
                    $olduser_relation = explode(',',$relation[0]['relation']);
                    if ($value['user_identity'] == 2) {
                        
                        $from_diamonduid = $olduser_relation[0];
                        // 查询钻石领取记录
                        $diamondvip_get = Db::query("SELECT * FROM pz_diamondvip_get WHERE `uid` = ".$value['id'] . " AND `share_uid` = ".$from_diamonduid);
                        Db::table('pz_diamondvip_get')->where('id', $diamondvip_get[0]['id'])->update(['share_uid' => 23926]);
                        // print_r($diamondvip_get);die;
                        // 查询订单号 
                        $diamond_member_order = Db::query("SELECT `id`,`order_no` FROM pz_member_order WHERE `uid` = ".$value['id'] . " AND `from_uid` = ".$from_diamonduid. " AND `pay_status` = 4 ");
                        $log_trading = Db::query("SELECT * FROM pz_log_trading WHERE `trading_type` = 3 AND `order_no` = '".$diamond_member_order[0]['order_no']."'");

                        $this_user = Db::query("SELECT `bounty` FROM pz_users WHERE  `id` = 23926 AND delete_time=0 ");
                        
                        $bounty = $this_user[0]['bounty'] + $log_trading[0]['money'];

                        Db::table('pz_users')->where('id', 23926)->update(['bounty' => $bounty]);
                        // print_r($this_user);die;
                        $from_user = Db::query("SELECT `bounty` FROM pz_users WHERE  `id` = ".$from_diamonduid." AND delete_time=0 ");
                        $subbounty = $from_user[0]['bounty'] - $log_trading[0]['money'];
                        Db::table('pz_users')->where('id', $from_diamonduid)->update(['bounty' => $subbounty]);
                        Db::table('pz_log_trading')->where('id', $log_trading[0]['id'])->update(['uid' => 23926]);
                        Db::table('pz_member_order')->where('id', $diamond_member_order[0]['id'])->update(['from_uid' => 23926]);
                        $olduser_relation[0] = 23926; 
                        $user_relation = implode(',',$olduser_relation);
                        $pid = 23926;
                    }else {
                        $user_relation = '23926,'.$relation[0]['relation'];
                    }
                    // 
                    // 
                    // print_r($user_relation);die;
                    if ($pid == 2) {
                        continue;
                        $olduser_relation[0] = 23926; 
                        $user_relation = implode(',',$olduser_relation);
                        $pid = 23926;
                    }
                    Db::table('pz_user_relation')->where('id', $relation[0]['id'])->update(['pid' => $pid,'relation' => $user_relation]);
                   
                }
               
            } */
            /* 老商城未注册会员变成以阅读数量*/

   /*          $mysql_connect = Db::connect(Config::get('database.db_config'));
            ini_set('memory_limit', '1024M');
            $member     = "SELECT * FROM pre_member  ";
            $memberdata = $mysql_connect->query($member);
            foreach ($memberdata as $key => $value) {
                $member_relationship = $mysql_connect->query('SELECT * FROM pre_member_relationship WHERE `uid` = ' . $value['uid']);
                if (!$member_relationship) {
                        continue;
                }
                $user_union = $mysql_connect->query("SELECT * FROM pre_member_wxunion WHERE `uid` = ".$value['uid']);
                $user_openid = $mysql_connect->query("SELECT * FROM pre_member_weixin WHERE `uid` = ".$value['uid']);
                $hierarchy = json_decode($member_relationship[0]['hierarchy']);
                if (empty($user_openid)) {
                    continue;
                }
                if ($user_union) {
                    // print_r($user_union);die;
                    $user_unionid = $user_union[0]['unionid'];
                    // print_r($user_unionid);die;
                    $new_database = Db::query("SELECT * FROM pz_users WHERE `unionid` = '".$user_unionid."'");
                    if ($new_database) {//已注册
                        continue;   
                    }
                }
                if ($hierarchy) {
                   
                    foreach ($hierarchy as $hie => $chy) {
                        // print_r($hierarchy);die;
                        if (!Db::query("SELECT * FROM pz_user_read WHERE `view_uid` = ".$chy. " AND `openid` = '".$user_openid[0]['wx_openid']."'")) {
                            $view_user = Db::query("SELECT `user_identity` FROM  pz_users WHERE `id` = ".$chy);
                            if (empty($view_user)) {
                                continue;
                            }
                            Db::table("pz_user_read")->insert(['openid' => $user_openid[0]['wx_openid'],'view_uid' => $chy,'view_identity' => $view_user[0]['user_identity']]);
                        }
                    }
                }
            } */

            /* 将爱心传播-友珍(15848209233)钻石购买关系挂在请叫我Mr.cai(18555858157)下面  2019/04/12 */
            $user    = Db::query("SELECT * FROM pz_users WHERE mobile = 15848209233 AND delete_time=0 ");
            $up_user = Db::query("SELECT * FROM pz_users WHERE mobile = 18555858157 AND delete_time=0 ");
            if (!empty($user) && !empty($up_user)) {
                $up_relation = Db::query("SELECT * FROM pz_user_relation WHERE uid = ".$up_user[0]['id']." AND delete_time=0 ");
                if ($up_user[0]['user_identity'] == 4) {
                    $have_up_relation =  $up_user[0]['id'].','.$user[0]['id'];
                } else {
                    $have_up_relation = $up_relation[0]['relation'].','.$user[0]['id'];
                }
                Db::table('pz_user_relation')->where(['uid' => $user[0]['id']])->update(['pid' => $up_user[0]['id'],'relation' => $have_up_relation]);
            }
            Db::commit();
       } catch (\Exception $e) {
           // 回滚事务
           exception($e);
           Db::rollback();
       }
       exit('ok!!');
    }

    /**
     * 标签库redis缓存脚本
     */
    public function labelScript() {
        $goodsRelation = Db::query('select lgr.label_lib_id from pz_label_goods_relation as lgr join pz_goods as g on lgr.goods_id=g.id where g.delete_time=0 and lgr.delete_time=0 and g.status=1');
        $labelIdList   = array_values(array_unique(array_column($goodsRelation, 'label_lib_id')));
//        print_r($labelIdList);die;
        $list = Db::query('select id,label_name,the_heat from pz_label_library where delete_time=0 and id in (' . implode(',', $labelIdList) . ')');
        foreach ($list as $l) {
            $this->setTransform($this->getTransformPinyin($l['label_name']), $l['id']);
            $this->setLabelLibrary($l['id'], $l['label_name']);
            $this->setLabelHeat($l['id'], $l['the_heat']);
        }
    }

    /**
     * 标签转换后存储
     * @param $trans 标签转换后的列表
     * @param $labelLibId 标签库id
     * @author zyr
     */
    private function setTransform($trans, $labelLibId) {
        $redisKey = Config::get('rediskey.label.redisLabelTransform');
        foreach ($trans as $t) {
            if (!$this->redis->hSetNx($redisKey, $t, json_encode([$labelLibId]))) {
                $transLabel = json_decode($this->redis->hGet($redisKey, $t), true);
                if (!in_array($labelLibId, $transLabel)) {
                    array_push($transLabel, $labelLibId);
                    $this->redis->hSet($redisKey, $t, json_encode($transLabel));
                }
            }
        }
    }

    /**
     * @description:
     * @param $labelLibId 标签库id
     * @param $name 标签名
     * @author zyr
     */
    private function setLabelLibrary($labelLibId, $name) {
        $redisKey = Config::get('rediskey.label.redisLabelLibrary');
        $this->redis->hSetNx($redisKey, $labelLibId, $name);
    }

    private function setLabelHeat($labelLibId, $heat) {
        $redisKey = Config::get('rediskey.label.redisLabelLibraryHeat');
        $this->redis->zAdd($redisKey, $heat, $labelLibId);
    }

    private function getTransformPinyin($name) {
        if (empty($name)) {
            return [];
        }
        $pinyin       = new Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
        $withoutTone2 = implode('', $pinyin->convert($name, PINYIN_UMLAUT_V));
        $withoutTone  = $pinyin->permalink($name, '', PINYIN_UMLAUT_V);
        $ucWord       = $pinyin->abbr($name, '');
        $ucWord2      = $pinyin->abbr($name, '', PINYIN_KEEP_NUMBER);
        $ucWord3      = $pinyin->abbr($name, '', PINYIN_KEEP_ENGLISH);
        $data         = [
            strtolower($name), //全名
            strtolower($withoutTone), //包含非中文的全拼音
            strtolower($withoutTone2), //不包含非中文的全拼音
            strtolower($ucWord3), //拼音首字母,包含字母
            strtolower($ucWord2), //拼音首字母,包含数字
            strtolower($ucWord), //拼音首字母,不包含非汉字内容
        ];
        return array_filter(array_unique($data));
    }

    public function imagePath(){
        $goodsRelation = Db::query('select `id`,`image_path` from pz_recommend where `image_path`<> '."''");
        // print_r($goodsRelation);
        Db::startTrans();
        try {
            foreach ($goodsRelation as $key => $value) {
                $image_path = $this->filtraImage($value['image_path']);
                Db::table('pz_recommend')->where('id',$value['id'])->update(['image_path' => $image_path]);
            }
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            exception($e);
            Db::rollback();
        }
        exit('ok!!');
    }

    function filtraImage( $image) {
        $image = str_replace('https://imagesdev.pzlive.vip' . '/', '', $image);
        return str_replace('https://images.pzlive.vip' . '/', '', $image);
    }
}
