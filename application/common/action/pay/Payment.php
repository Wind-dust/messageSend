<?php

namespace app\common\action\pay;

use app\common\model\LogApi;
use app\common\action\notify\Note;
use app\facade\DbOrder;
use app\facade\DbUser;
use cache\Phpredis;
use Config;
use pay\wxpay\WxMiniprogramPay;
use think\Db;

/**
 * 支付
 * @package app\common\action\pay
 */
class Payment {
    private $redis;

    public function __construct() {
        $this->redis            = Phpredis::getConn();
        $this->redisAccessToken = Config::get('redisKey.weixin.redisAccessToken');
    }

    public function payment($orderNo, int $payment, int $platform, $code) {
        $orderOutTime = Config::get('conf.order_out_time');//订单过期时间
        if ($payment == 2) {//购买会员订单
            $payType        = 2; //支付类型 1.支付宝 2.微信 3.银联 4.商券
            $memberOrderRow = $this->memberDiamond($orderNo);
            if (empty($memberOrderRow)) {
                return ['code' => '3000']; //订单号不存在
            }
            if ($memberOrderRow['pay_status'] == 2) { //取消
                return ['code' => '3004']; //订单已取消
            } else if ($memberOrderRow['pay_status'] == 3) { //关闭
                return ['code' => '3005']; //订单已关闭
            } else if ($memberOrderRow['pay_status'] == 4) { //已付款
                return ['code' => '3006']; //订单已付款
            }
//            if ($memberOrderRow['create_time'] < date('Y-m-d H:i:s', time() - $orderOutTime)) {
            //                return ['code' => '3007'];//订单已过期
            //            }
            $orderId    = $memberOrderRow['id'];
            $payMoney   = $memberOrderRow['pay_money']; //要支付的金额
            $uid        = $memberOrderRow['uid'];
            $payType    = $memberOrderRow['pay_type'];
            $logTypeRow = DbOrder::getLogPay(['order_id' => $orderId, 'payment' => $payment, 'status' => 1], 'pay_no', true);
            if (!empty($logTypeRow)) {
                return ['code' => '3008']; //第三方支付已付款
            }
            if ($payType == 2) {//微信支付
                $parameters = $this->wxpay($uid, $platform, $payment, $payMoney, $orderId, $code);
                if ($parameters === false) {
                    return ['code' => '3010']; //创建支付订单失败
                }
                return ['code' => '200', 'parameters' => $parameters];
            }
        } else if ($payment == 1) { //普通订单
            $nomalOrder = $this->nomalOrder($orderNo);
            if (empty($nomalOrder)) {
                return ['code' => '3000']; //不存在需要支付的订单
            }
            if ($nomalOrder['order_status'] == 2) { //取消
                return ['code' => '3004']; //订单已取消
            } else if ($nomalOrder['order_status'] == 3) { //关闭
                return ['code' => '3005']; //订单已关闭
            } else if ($nomalOrder['order_status'] != 1) { //已付款
                return ['code' => '3006']; //订单已付款
            }
            if ($nomalOrder['create_time'] < date('Y-m-d H:i:s', time() - $orderOutTime)) {
                return ['code' => '3007']; //订单已过期
            }
            $orderId      = $nomalOrder['id'];
            $uid          = $nomalOrder['uid'];
            $payType      = $nomalOrder['pay_type']; //支付类型 1.所有第三方支付 2.商券
            $thirdPayType = $nomalOrder['third_pay_type']; //第三方支付类型1.支付宝 2.微信 3.银联
            $thirdMoney   = $nomalOrder['third_money']; //第三方支付金额
            $logTypeRow   = DbOrder::getLogPay(['order_id' => $orderId, 'payment' => $payment, 'status' => 1], 'pay_no', true);
            if (!empty($logTypeRow)) {
                return ['code' => '3008']; //第三方支付已付款
            }
            Db::startTrans();
            try {
                if ($thirdPayType == 2) {//微信支付
                    $parameters = $this->wxpay($uid, $platform, $payment, $thirdMoney, $orderId, $code);
                    if ($parameters === false) {
                        Db::rollback();
                        return ['code' => '3010']; //创建支付订单失败
                    }
                    Db::commit();
                    return ['code' => '200', 'parameters' => $parameters];
                }
            } catch (\Exception $e) {
                Db::rollback();
                return ['code' => '3010']; //创建支付订单失败
            }
        }
        return ['code' => '3009']; //支付方式暂不支持
    }

    /**
     * 微信支付
     * @param $uid
     * @param $platform
     * @param $payment
     * @param $payMoney
     * @param $orderId
     * @param $code
     * @return array
     * @author zyr
     */
    private function wxpay($uid, $platform, $payment, $payMoney, $orderId, $code) {
        //获取openid
//        $openType   = Config::get('conf.platform_conf')[Config::get('app.deploy')];
//        $userWxinfo = DbUser::getUserWxinfo(['uid' => $uid, 'platform' => $platform, 'openid_type' => $openType], 'openid', true);
        $userWxinfo = getOpenid($code);
        if (empty($userWxinfo['openid'])) {
            return false;
        }
        $openid = $userWxinfo['openid'];
        $payNo  = createOrderNo('wpy');
        $data   = [
            'pay_no'   => $payNo,
            'uid'      => $uid,
            'payment'  => $payment,
            'pay_type' => 2,
            'order_id' => $orderId,
            'money'    => bcmul($payMoney, 100, 0),
        ];
        $addRes = DbOrder::addLogPay($data);
        if (!empty($addRes)) {
            $wxPay  = new WxMiniprogramPay($openid, $data['pay_no'], $data['money']);
            $result = $wxPay->pay();
            /* 调用模板消息ID 2019/04/28 */
            $logPayRes = DbOrder::getLogPay(['pay_no' => $payNo], 'id,order_id,payment', true);
            DbOrder::updateLogPay(['prepay_id' => $result['prepay_id']], $logPayRes['id']);
            return $result;
        }
        return false;
    }

    /**
     * 购买会员订单
     * @param $orderNo
     * @return mixed
     * @author zyr
     */
    private function memberDiamond($orderNo) {
        $memberOrderRow = DbOrder::getMemberOrder(['order_no' => $orderNo], 'id,uid,pay_money,pay_status,pay_type,create_time,from_uid', true);
        return $memberOrderRow;
    }

    /**
     * 普通商品购买订单
     * @param $orderNo
     * @return mixed
     * @author zyr
     */
    private function nomalOrder($orderNo) {
        $field      = 'id,uid,order_status,pay_money,deduction_money,third_money,pay_type,third_pay_type,create_time';
        $nomalOrder = DbOrder::getOrder($field, ['order_no' => $orderNo, 'order_status' => 1], true);
        return $nomalOrder;
    }

    /**
     * 支付回调
     * @param $res
     * @author zyr
     */
    public function wxPayCallback($res) {
        $wxReturn   = $this->xmlToArray($res);
        $notifyData = $wxReturn;
        $sign       = $wxReturn['sign']; //微信返回的签名
        unset($wxReturn['sign']);
        $makeSign = $this->makeSign($wxReturn, Config::get('conf.wx_pay_key'));
        if ($makeSign == $sign) { //验证签名
            $logPayRes    = DbOrder::getLogPay(['pay_no' => $wxReturn['out_trade_no'], 'status' => 2], 'id,order_id,payment', true);
            $data         = [
                'notifydata' => json_encode($notifyData),
                'status'     => 1,
                'pay_time'   => time(),
            ];
            $orderRes     = [];
            $memOrderRes  = [];
            $orderData    = [];
            $memOrderData = [];
            if ($logPayRes['payment'] == 1) { //1.普通订单
                $orderRes  = DbOrder::getOrder('id,uid,create_time,pay_time,order_status,order_no', ['id' => $logPayRes['order_id'], 'order_status' => 1], true);
                $orderData = [
                    'third_order_id' => $wxReturn['transaction_id'],
                    'order_status'   => 4,
                    'pay_time'       => time(),
                    'third_time'     => time(),
                ];
            } else if ($logPayRes['payment'] == 2) { //2.购买会员订单
                $memOrderRes  = DbOrder::getMemberOrder(['id' => $logPayRes['order_id'], 'pay_status' => 1], 'id', true);
                $memOrderData = [
                    'pay_time'   => time(),
                    'pay_status' => 4,
                ];
            }
            if (!empty($orderRes) || !empty($memOrderRes)) {

                Db::startTrans();
                try {
                    DbOrder::updateLogPay($data, $logPayRes['id']);
                    if (!empty($orderData)) {
                        DbOrder::updataOrder($orderData, $orderRes['id']);
                        $redisListKey = Config::get('rediskey.order.redisOrderBonus');
                        $this->redis->rPush($redisListKey, $orderRes['id']);

                        $order_list = DbOrder::getOrderDetail(['o.id' => $orderRes['id']], '*');
                        $skus       = [];
                        $sku_goodsids  = [];
                        foreach ($order_list as $order => $list) {
                            $sku_goodsids[] = $list['goods_id'];
                            if (in_array( $list['goods_id'],[1888,1887,1886])) {
                                $userInfo = DbUser::getUserInfo(['id' => $orderRes['uid']], 'user_identity', true);
                                if ($userInfo['user_identity'] > 1) {
                                    break;
                                }
                                $receiveDiamondvip                   = [];
                                $receiveDiamondvip['uid']            = $orderRes['uid'];
                                $receiveDiamondvip['share_uid']      = '24648';
                                DbRights::receiveDiamondvip($receiveDiamondvip);
                                break;
                            }
                        }

                    }
                    if (!empty($memOrderData)) {
                        DbOrder::updateMemberOrder($memOrderData, ['id' => $memOrderRes['id']]);
                        $redisListKey = Config::get('rediskey.order.redisMemberOrder');
                        $this->redis->rPush($redisListKey, $memOrderRes['id']);
                    }
                    Db::commit();
              /*       if (!empty($orderData)) { //活动订单发送取货码
                        $orderRes = DbOrder::getOrder('id,order_type,order_status,order_no,uid', ['id' => $logPayRes['order_id']], true);
                        if ($orderRes['order_type'] == 2) { //线下取货发送取货码
                            $order_list = DbOrder::getOrderDetail(['o.id' => $orderRes['id']], '*');
                            $skus       = [];
                            $sku_goods  = [];
                            $goods_name = [];
                            foreach ($order_list as $order => $list) {
                                if (!$list['province_id'] && !$list['city_id'] && !$list['area_id']) {

                                    if (in_array($list['sku_id'], $skus)) {
                                        $sku_goods[$list['sku_id']] = $sku_goods[$list['sku_id']] + 1;
                                    } else {
                                        $skus[]                     = $list['sku_id'];
                                        $sku_goods[$list['sku_id']] = 1;
                                        $sku_json                   = json_decode($list['sku_json'], true);
                                        // print_r($sku_json);die;
                                        $goods_name[$list['sku_id']] = $list['goods_name'] . '规格[' . join(',', $sku_json) . ']';
                                    }

                                }
                                // print_r($goods_name);die;
                            }
                            $message       = '您购买的商品：{';
                            $admin_message = '订单号:' . $orderRes['order_no'] . '商品:{';
                            foreach ($goods_name as $goods => $name) {
                                $message       .= $name . '数量[' . $sku_goods[$goods] . ']';
                                $admin_message .= $name . '数量[' . $sku_goods[$goods] . ']';
                            }
                            $message       = $message . '}订单号为' . $orderRes['order_no'] . '取货码为：Off' . $orderRes['id'];
                            $admin_message = $admin_message . '取货码为：Off' . $orderRes['id'];

                            $user_phone = DbUser::getUserInfo(['id' => $orderRes['uid']], 'mobile', true);

                            //取消发送取货码 
                            // $Note       = new Note;
                            // $send1      = $Note->sendSms($user_phone['mobile'], $message);
                            // $send2      = $Note->sendSms('17091858983', $admin_message);

                            // Db::table('pz_log_error')->insert(['title' => '/pay/pay/wxPayCallback', 'data' => json_encode($send1)]);
                            // Db::table('pz_log_error')->insert(['title' => '/pay/pay/wxPayCallback', 'data' => json_encode($send2)]);

                        }

                    } */
                } catch (\Exception $e) {
                    $this->apiLog('pay/pay/wxPayCallback', json_encode($e));
                    Db::rollback();
                    Db::table('pz_log_error')->insert(['title' => '/pay/pay/wxPayCallback', 'data' => $e]);
                }
            } else { //写错误日志(待支付订单不存在)
                echo 'error order';
            }
        } else { //写错误日志(签名错误)
            echo 'error sign';
        }

//        $res = '{"appid":"wx112088ff7b4ab5f3","attach":"255","bank_type":"CFT","cash_fee":"1425","fee_type":"CNY","is_subscribe":"Y","mch_id":"1330663401","nonce_str":"0lfvboi6rnpxe2g49ksunp1298e008mu","openid":"o83f0wLtc3Wlx9sv8yyECXv_Enh0","out_trade_no":"PAYSN201807041721287496","result_code":"SUCCESS","return_code":"SUCCESS","sign":"C0B76E319EDEC158036882A56044B2D7","time_end":"20180704172134","total_fee":"1425","trade_type":"JSAPI","transaction_id":"4200000128201807043248657648"}';
        //        print_r(json_decode($res, true));

//        "<xml><appid><![CDATA[wxa8c604ce63485956]]></appid><bank_type><![CDATA[CFT]]></bank_type><cash_fee><![CDATA[1]]></cash_fee><fee_type><![CDATA[CNY]]></fee_type><is_subscribe><![CDATA[N]]></is_subscribe><mch_id><![CDATA[1505450311]]></mch_id><nonce_str><![CDATA[aid38or91hq8r4w5ttg4caru18w3v4yq]]></nonce_str><openid><![CDATA[oAuSK5U76yO10U0cJSbzSiRLPXW0]]></openid><out_trade_no><![CDATA[mem19021818075357545052]]></out_trade_no><result_code><![CDATA[SUCCESS]]></result_code><return_code><![CDATA[SUCCESS]]></return_code><sign><![CDATA[789B26EBB62417381005C5FDFCAF59F8]]></sign><time_end><![CDATA[20190218180816]]></time_end><total_fee>1</total_fee><trade_type><![CDATA[JSAPI]]></trade_type><transaction_id><![CDATA[4200000255201902181171403485]]></transaction_id></xml>";
    }

    //xml转换成数组
    private function xmlToArray($xml) {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val       = json_decode(json_encode($xmlstring), true);
        return $val;
    }

    private function makeSign($params, $key) {
        //签名步骤一：按字典序排序数组参数
        ksort($params);
        $string = $this->ToUrlParams($params); //参数进行拼接key=value&k=v
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    private function toUrlParams($params) {
        $string = '';
        if (!empty($params)) {
            $array = array();
            foreach ($params as $key => $value) {
                $array[] = $key . '=' . $value;
            }
            $string = implode("&", $array);
        }
        return $string;
    }

    private function apiLog($apiName, $param) {
        $user = new LogApi();
        $user->save([
            'api_name' => $apiName,
            'param'    => json_encode($param),
            'stype'    => 1,
//            'code'     => $code,
//            'admin_id' => $adminId,
        ]);
    }

    /**
     * 获取微信access_token
     * @return array
     * @author rzc
     */
    private function getWeiXinAccessToken() {
        $access_token = $this->redis->get($this->redisAccessToken);
        if (empty($access_token)) {
            $appid = Config::get('conf.weixin_miniprogram_appid');
            // $appid         = 'wx1771b2e93c87e22c';
            $secret = Config::get('conf.weixin_miniprogram_appsecret');
            // $secret        = '1566dc764f46b71b33085ba098f58317';
            $requestUrl       = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
            $requsest_subject = json_decode(sendRequest($requestUrl), true);
            $access_token     = $requsest_subject['access_token'];
            if (!$access_token) {
                return false;
            }
            $this->redis->set($this->redisAccessToken, $access_token);
            $this->redis->expire($this->redisAccessToken, 6600);
        }

        return $access_token;
    }
}