<?php
// 应用公共文件

/**
 * 过滤图片路径的http域名
 * @param $image
 * @return mixed
 * @author rzc
 */
function filtraImage($domain, $image)
{
    return str_replace($domain . '/', '', $image);
}

/**
 * 验证手机号
 * @param $mobile
 * @return bool
 * @author rzc
 */
function checkMobile($mobile)
{
    $end_num = substr($mobile, -6);
    if (in_array($end_num, ['000000', '111111', '222222', '333333', '444444', '555555', '666666', '777777', '888888', '999999'])) {
        return false;
    }
    if (!empty($mobile) && preg_match('/^1[3-9]{1}\d{9}$/', $mobile)) {
        return true;
    }
    return false;
}

function checkEmail($email)
{
    if (!empty($email) && preg_match('/^\w+@[a-z0-9]+\.[a-z]{2,4}$/', $email)) {
        return true;
    }
    return false;
}

/**
 * 验证验证码格式
 * @param $code
 * @return bool
 * @author zyr
 */
function checkVercode($code)
{
    if (!empty($code) && preg_match('/^\d{6}$/', $code)) {
        return true;
    }
    return false;
}

/**
 * 验证密码强度
 * @param $password
 * @return bool
 * @author zyr
 */
function checkPassword($password)
{
    // /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[\s\S]{8,16}$/  至少8-16个字符，至少1个大写字母，1个小写字母和1个数字，其他可以是任意字符：
    if (!empty($password) && preg_match('/^(?=.*[a-zA-Z])(?=.*\d)[\s\S]{6,16}$/', $password)) { //6-16个字符，至少1个字母和1个数字，其他可以是任意字符
        return true;
    }
    return false;
}

/**
 * cms验证密码强度
 * @param $password
 * @return bool
 * @author zyr
 */
function checkCmsPassword($password)
{
    if (!empty($password) && preg_match('/^(?=.*)[\s\S]{6,16}$/', $password)) { //6-16个字符,可以是任意字符
        return true;
    }
    return false;
}

/**
 * 获取验证码短信内容
 * @param $code
 * @return string
 * @author zyr
 */
function getVercodeContent($code, $type = 0)
{
    if ($type == 5) {
        return '您参与报名活动的验证码是:' . $code . '，在10分钟内有效。如非本人操作请忽略本短信。';
    }
    return '您的验证码是:' . $code . '，在10分钟内有效。如非本人操作请忽略本短信。';
}

/**
 * 随机生成数字字符串
 * @param int $num
 * @return string
 * @author zyr
 */
function randCaptcha($num)
{
    $key     = '';
    $pattern = '1234567890';
    for ($i = 0; $i < $num; $i++) {
        $key .= $pattern[mt_rand(0, 9)];
    }
    return $key;
}

/**
 * @param $uid
 * @return int|string
 * @author zyr
 */
function enUid($uid)
{
    $str    = 'AcEgIkMoQs';
    $newuid = strrev($uid);
    $newStr = '';
    for ($i = 0; $i < strlen($newuid); $i++) {
        $newStr .= $str[$newuid[$i]];
    }
    $tit    = getOneNum($newuid);
    $result = $str[getOneNum($tit)] . $newStr;
    return $result;
    //    $cryptMethod = Env::get('cipher.userAesMethod', 'AES-256-CBC');
    //    $cryptKey    = Env::get('cipher.userAesKey', 'pzlife');
    //    $cryptIv     = Env::get('cipher.userAesIv', '1111111100000000');
    //    if (strlen($uid) > 15) {
    //        return 0;
    //    }
    //    $uid     = intval($uid);
    //    $encrypt = base64_encode(openssl_encrypt($uid, $cryptMethod, $cryptKey, 0, $cryptIv));
    //    return $encrypt;
}

/**
 * @param $enUid
 * @return int|string
 * @author zyr
 */
function deUid($enUid)
{
    if (empty($enUid)) {
        return '';
    }
    $str      = 'AcEgIkMoQs';
    $newEnUid = substr($enUid, 1);
    if (empty($newEnUid)) {
        return '';
    }
    $id = '';
    for ($i = 0; $i < strlen($newEnUid); $i++) {
        $f = strpos($str, $newEnUid[$i]);
        if ($f === false) {
            return '';
        }
        $id .= $f;
    }
    if ($str[getOneNum($id)] != substr($enUid, 0, 1)) {
        return '';
    }
    return strrev($id);
    //    $cryptMethod = Env::get('cipher.userAesMethod', 'AES-256-CBC');
    //    $cryptKey    = Env::get('cipher.userAesKey', 'pzlife');
    //    $cryptIv     = Env::get('cipher.userAesIv', '1111111100000000');
    //    $decrypt     = openssl_decrypt(base64_decode($enUid), $cryptMethod, $cryptKey, 0, $cryptIv);
    //    if ($decrypt) {
    //        return $decrypt;
    //    } else {
    //        return 0;
    //    }
}

/**
 * @param $adminId
 * @return int|string
 * @author zyr
 */
function enAdminId($adminId)
{
    $cryptMethod = Env::get('cipher.userAesMethod', 'AES-128-CBC');
    $cryptKey    = Env::get('cipher.userAesKey', 'pzlife');
    $cryptIv     = Env::get('cipher.userAesIv', '1111111100000000');
    if (strlen($adminId) > 15) {
        return 0;
    }
    $adminId = intval($adminId);
    $encrypt = base64_encode(openssl_encrypt($adminId, $cryptMethod, $cryptKey, 0, $cryptIv));
    return $encrypt;
}

/**
 * @param $enAdminId
 * @return int|string
 * @author zyr
 */
function deAdminId($enAdminId)
{
    $cryptMethod = Env::get('cipher.userAesMethod', 'AES-128-CBC');
    $cryptKey    = Env::get('cipher.userAesKey', 'pzlife');
    $cryptIv     = Env::get('cipher.userAesIv', '1111111100000000');
    $decrypt     = openssl_decrypt(base64_decode($enAdminId), $cryptMethod, $cryptKey, 0, $cryptIv);
    if ($decrypt) {
        return $decrypt;
    } else {
        return 0;
    }
}

function getOneNum($num)
{
    if ($num < 10) {
        return $num;
    }
    $res = 0;
    for ($i = 0; $i < strlen($num); $i++) {
        $res = bcadd($num[$i], $res, 0);
    }
    return getOneNum($res);
}

/**
 * 发送请求
 * @param $requestUrl
 * @param string $method
 * @param $data
 * @return array|mixed
 * @author zyr
 */
function sendRequest($requestUrl, $method = 'get', $data = [])
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
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
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

/**
 * 获取所在的项目入口(index,admin)
 * @param $file
 * @return bool|string
 * @author zyr
 */
function controllerBaseName($file)
{
    $path  = dirname(dirname($file));
    $index = intval(strrpos($path, '/'));
    return substr($path, bcadd($index, 1, 0));
}

/**
 * 获取不包含命名空间的类名
 * @param $class
 * @return string
 * @author zyr
 */
function classBasename($class)
{
    $class = is_object($class) ? get_class($class) : $class;
    return basename(str_replace('\\', '/', $class));
}

/**
 * 创建唯一订单号
 * @param $prefix (1.odr:购买商品订单 2.mem:购买会员订单 3.wpy:微信支付订单号)
 * @return string
 * @author zyr
 */
function createOrderNo($prefix = 'odr')
{
    $orderNo = $prefix . date('ymdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    return $orderNo;
}

/**
 * 获取商品的可分配利润
 * @param $retailPrice 商品售价
 * @param $costPrice 商品成本价
 * @param $marginPrice 其他成本
 * @return decimal
 * @author zyr
 */
function getDistrProfits($retailPrice, $costPrice, $marginPrice)
{
    $otherPrice   = bcmul($retailPrice, 0.006, 4); //售价的0.6%
    $profits      = bcsub(bcsub(bcsub($retailPrice, $costPrice, 4), $marginPrice, 4), $otherPrice, 4); //利润(售价-进价-其他成本-售价*0.006)
    $distrProfits = bcmul($profits, 0.9, 2); //可分配利润
    $distrProfits = $distrProfits < 0 ? 0 : $distrProfits;
    return $distrProfits;
}

/**
 * 获取微信的openid unionid 及详细信息
 * @param $code
 * @param string $encrypteddata
 * @param string $iv
 * @return array|bool|int
 * @author zyr
 */
function getOpenid($code, $encrypteddata = '', $iv = '')
{
    $appid         = Env::get('weixin.weixin_miniprogram_appid');
    $secret        = Env::get('weixin.weixin_miniprogram_appsecret');
    $get_token_url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $secret . '&js_code=' . $code . '&grant_type=authorization_code';
    $res           = sendRequest($get_token_url);
    $result        = json_decode($res, true);
    // Array([session_key] => N/G/1C4QKntLTDB9Mk0kPA==,[openid] => oAuSK5VaBgJRWjZTD3MDkTSEGwE8,[unionid] => o4Xj757Ljftj2Z6EUBdBGZD0qHhk)
    if (empty($result['session_key'])) {
        return false;
    }
    $sessionKey = $result['session_key'];
    unset($result['session_key']);
    if (!empty($encrypteddata) && !empty($iv) && empty($result['unionId'])) {
        $result = decryptData($encrypteddata, $iv, $sessionKey);
    }
    if (is_array($result)) {
        $result = array_change_key_case($result, CASE_LOWER); //CASE_UPPER,CASE_LOWER
        return $result;
    }
    return false;
    //[openId] => oAuSK5VaBgJRWjZTD3MDkTSEGwE8,[nickName] => 榮,[gender] => 1,[language] => zh_CN,[city] =>,[province] => Shanghai,[country] => China,
    //[avatarUrl] => https://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJiaWQI7tUfDVrvuSrDDcfFiaJriaibibBiaYabWL5h6HlDgMMvkyFul9JRicr0ZMULxs66t5NBdyuhEokhA/132
    //[unionId] => o4Xj757Ljftj2Z6EUBdBGZD0qHhk
}

/**
 * 解密微信信息
 * @param $encryptedData
 * @param $iv
 * @param $sessionKey
 * @return int|array
 * @author zyr
 * -40001: 签名验证错误
 * -40002: xml解析失败
 * -40003: sha加密生成签名失败
 * -40004: encodingAesKey 非法
 * -40005: appid 校验错误
 * -40006: aes 加密失败
 * -40007: aes 解密失败
 * -40008: 解密后得到的buffer非法
 * -40009: base64加密失败
 * -40010: base64解密失败
 * -40011: 生成xml失败
 */
function decryptData($encryptedData, $iv, $sessionKey)
{
    $appid = Env::get('weixin.weixin_miniprogram_appid');
    if (strlen($sessionKey) != 24) {
        return -41001;
    }
    $aesKey = base64_decode($sessionKey);
    if (strlen($iv) != 24) {
        return -41002;
    }
    $aesIV     = base64_decode($iv);
    $aesCipher = base64_decode($encryptedData);
    $result    = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
    $dataObj   = json_decode($result);
    if ($dataObj == null) {
        return -41003;
    }
    if ($dataObj->watermark->appid != $appid) {
        return -41003;
    }
    $data = json_decode($result, true);
    unset($data['watermark']);
    return $data;
}

/**
 * 快递编码公司对应物流
 * @return array
 * @author rzc
 */
function getExpressList()
{
    $ExpressList = [
        'shunfeng'       => '顺丰速运',
        'zhongtong'      => '中通快递',
        'shentong'       => '申通快递',
        'yunda'          => '韵达快递',
        'tiantian'       => '天天快递',
        'huitongkuaidi'  => '百世快递',
        'ems'            => 'EMS',
        'youshuwuliu'    => '优速物流',
        'kuayue'         => '跨越速运',
        'debangwuliu'    => '德邦物流',
        'yuantong'       => '圆通速递',
        'jiuyescm'       => '九曳快递',
        'zhaijibian'     => '黑猫宅急便(宅急便)',
        'ane66'          => '安能快递',
        'youzhengguonei' => '中国邮政',
        'rufengda'       => '如风达',
        'wanxiangwuliu'  => '万象物流',
        'SJPS'           => '商家派送',
    ];
    return $ExpressList;
}

/**
 * 检测银行卡号是否合法
 * @return array
 * @author rzc
 */
function checkBankCard($cardNum)
{
    $arr_no = str_split($cardNum);
    $last_n = $arr_no[count($arr_no) - 1];
    krsort($arr_no);
    $i     = 1;
    $total = 0;
    foreach ($arr_no as $n) {
        if ($i % 2 == 0) {
            $ix = $n * 2;
            if ($ix >= 10) {
                $nx = 1 + ($ix % 10);
                $total += $nx;
            } else {
                $total += $ix;
            }
        } else {
            $total += $n;
        }
        $i++;
    }
    $total -= $last_n;
    $x = 10 - ($total % 10);

    if ($x == 10) {
        $x = 0;
    }

    if ($x == $last_n) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获取银行卡号银行信息
 * @return array
 * @author rzc
 */
function getBancardKey($cardNo)
{
    $url = 'https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?_input_charset=utf-8&cardNo=';
    $url .= $cardNo;
    $url .= "&cardBinCheck=true";
    $cardmessage = sendRequest($url);
    $cardmessage = json_decode($cardmessage, true);
    // print_r($cardmessage);die;
    if (!isset($cardmessage['bank'])) {
        return false;
    }
    return ['bank' => $cardmessage['bank'], 'cardNo' => $cardNo];
}

/**
 * 校验身份证号码
 * @return array
 * @author rzc
 */
function checkIdcard($idcard)
{
    $idcard    = strtoupper($idcard);
    $regx      = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
    $arr_split = array();
    if (!preg_match($regx, $idcard)) {
        return false;
    }

    if (15 == strlen($idcard)) //检查15位
    {
        $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";

        @preg_match($regx, $idcard, $arr_split);
        //检查生日日期是否正确
        $dtm_birth = "19" . $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
        if (!strtotime($dtm_birth)) {
            return false;
        } else {
            return true;
        }
    } else //检查18位
    {
        $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";

        @preg_match($regx, $idcard, $arr_split);
        $dtm_birth = $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];

        if (!strtotime($dtm_birth)) //检查生日日期是否正确
        {
            return false;
        } else {
            //检验18位身份证的校验码是否正确。
            //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
            $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
            $arr_ch  = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
            $sign    = 0;

            for ($i = 0; $i < 17; $i++) {
                $b = (int) $idcard{
                    $i};
                $w = $arr_int[$i];
                $sign += $b * $w;
            }

            $n       = $sign % 11;
            $val_num = $arr_ch[$n];

            if ($val_num != substr($idcard, 17, 1)) {
                return false;
            } else {
                return true;
            }
        }
    }
}

/**
 * @param $str 加密的内容
 * @param $key
 * @param $algo
 * @return string
 * @author zyr
 */
function getPassword($str, $key, $algo = 'sha256')
{
    //    $algo   = Config::get('conf.cipher_algo');
    $md5    = hash_hmac('md5', $str, $key);
    $key2   = strrev($key);
    $result = hash_hmac($algo, $md5, $key2);
    return $result;
}

/**
 * 查询
 * @param $obj
 * @param bool $row
 * @param string $orderBy
 * @param string $limit
 * @return mixed
 * @author zyr
 */
function getResult($obj, $row = false, $orderBy = '', $limit = '')
{
    if (!empty($orderBy)) {
        $obj = $obj->order($orderBy);
    }
    if (!empty($limit)) {
        $obj = $obj->limit($limit);
    }
    if ($row === true) {
        $obj = $obj->findOrEmpty();
    } else {
        $obj = $obj->select();
    }
    return $obj->toArray();
}

/**
 * 生成字母加数字随机组合
 * @param $len
 * @param string $chars
 * @return mixed
 * @author rzc
 */
function getRandomString($len, $chars = null)
{
    if (is_null($chars)) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    }
    mt_srand(10000000 * (float) microtime());
    for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
        $str .= $chars[mt_rand(0, $lc)];
    }
    return $str;
}
