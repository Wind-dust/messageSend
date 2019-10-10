<?php

namespace app\pay\Controller;

use app\pay\PayController;
use think\App;
use Env;

class Pay extends PayController {
    protected $beforeActionList = [
        //        'first',//所有方法的前置操作
        //        'second' => ['except' => 'hello'],//除去hello其他方法都进行second前置操作
        //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    public function __construct(App $app = null) {
        parent::__construct($app);
    }

    /**
     * @api              {post} / 支付订单
     * @apiDescription   pay
     * @apiGroup         pay_wxpay
     * @apiName          pay
     * @apiParam (入参) {String} order_no 订单号
     * @apiParam (入参) {Int} payment 1.普通订单 2.购买会员订单 3.虚拟商品订单
     * @apiParam (入参) {String} [platform] 环境 1.小程序 2.公众号(默认1)
     * @apiSuccess (返回) {String} code 200:成功 / 3000:不存在需要支付的订单 / 3001.订单号错误 / 3002.订单类型错误 / 3004:订单已取消 / 3005:订单已关闭 / 3006:订单已付款 3007:订单已过期 / 3008:第三方支付已付款 / 3009:支付方式暂不支持 / 3010:创建支付订单失败 / 3011:code有误
     * @apiSuccess (返回) {String} parameters 发起支付加密数据
     * @apiSampleRequest /pay/pay/pay
     * @author zyr
     */
    public function pay() {
        $orderNo     = trim($this->request->post('order_no'));
        $payment     = trim($this->request->post('payment'));
        $platform    = trim($this->request->post('platform'));
        $code        = trim($this->request->post('code'));
        $paymentArr  = [1, 2, 3];
        $platformArr = [1, 2];
        if (strlen($orderNo) != 23) {
            return ['code' => '3001'];//订单号错误
        }
        if (!in_array($payment, $paymentArr)) {
            return ['code' => '3002'];//订单类型错误
        }
        if (strlen($code) != 32) {
            return ['code' => '3011']; //code有误
        }
        $platform = in_array($platform, $platformArr) ? intval($platform) : 1;
        $result   = $this->app->payment->payment($orderNo, intval($payment), $platform, $code);
        return $result;
    }


    /**
     * @api              {post} / 微信支付回调
     * @apiDescription   wxPayCallback
     * @apiGroup         pay_wxpay
     * @apiName          wxPayCallback
     * @apiParam (入参) {Number} order_no
     * @apiSuccess (返回) {String} code 200:成功 / 3000:未获取到数据 / 3001.skuid错误 / 3002.con_id错误 /3003:city_id必须为数字 / 3004:商品售罄 / 3005:商品未加入购物车 / 3006:商品不支持配送 3007:商品库存不够
     * @apiSuccess (返回) {Int} goods_count 购买商品总数
     * @apiSampleRequest /pay/pay/wxPayCallback
     * @author zyr
     */
    public function wxPayCallback() {
        $res    = file_get_contents('php://input');
        $result = $this->app->payment->wxPayCallback($res);
        $str    = '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        echo $str;
        return $str;

    }
}
