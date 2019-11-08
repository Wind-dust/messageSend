<?php
namespace CMPP30;

 date_default_timezone_set("PRC");
/**
 * 移动点对点CMPP协议接口
 * 
 * PHP version 5 
 */

/**
 * 命令或响应类型 Command_Id定义
 */
define('CMPP_CONNECT', 0x00000001); // 请求连接
define('CMPP_CONNECT_RESP', 0x80000001); // 请求连接应答
define('CMPP_TERMINATE', 0x00000002); // 终止连接
define('CMPP_TERMINATE_RESP', 0x80000002); // 终止连接应答
define('CMPP_SUBMIT', 0x00000004); // 提交短信
define('CMPP_SUBMIT_RESP', 0x80000004); // 提交短信应答
define('CMPP_DELIVER', 0x00000005); // 短信下发
define('CMPP_DELIVER_RESP', 0x80000005); // 下发短信应答
define('CMPP_QUERY', 0x00000006); // 发送短信状态查询
define('CMPP_QUERY_RESP', 0x80000006); // 发送短信状态查询应答
define('CMPP_CANCEL', 0x00000007); // 删除短信
define('CMPP_CANCEL_RESP', 0x80000007); // 删除短信应答
define('CMPP_ACTIVE_TEST', 0x00000008); // 激活测试
define('CMPP_ACTIVE_TEST_RESP', 0x80000008); // 激活测试应答

class Cmpp30{

    protected $_normal_service_id="";
    protected $_template_service_id="";
    protected $_msg_src="";
    protected $_serv_addr="";
    /**
     * Socket连接
     * @var resource
     */
    protected $_socket = NULL;
    
    /**
     * 消息流水号
     * 顺序累加,步长为1,循环使用（一对请求和应答消息的流水号必须相同）
     * @var int
     */
    protected $_sequence_number = 1;
    
    protected $_message_sequence = 1;
    
    /**
     * 发送的数据
     * 已经排除access_token值
     * @var mixed
     */
    protected $_sendData = NULL;
    
    /**
     * 收到的数据
     * @var string
     */
    protected $_responseData = NULL;

    public function __construct(){

        $this->_socket = NULL;
        $this->_sequence_number = 1;
        $this->_message_sequence = rand(1,255);
    }

    protected function _log($message)
    {
        printf("%s\n",$message);
    }
    /**
     * PDU数据包转化ASCII数字
     * @param string $pdu
     * @return string
     */
    public static function pduord($pdu){
        $ord_pdu = '';
        for ($i = 0; $i < strlen($pdu); $i++) {
            $ord_pdu .= sprintf("%02x",ord($pdu[$i])) . ' ';
        }
        
        if($ord_pdu){
            $ord_pdu = substr($ord_pdu, 0, -1);
        }
                
        return $ord_pdu;
    }
    //主机 端口 用户名 密码


     // template_service_id  template_service_id as  Service_Id 业务标识，是数字、字母和符号的组合。
     //$msg_src as  msg_src信息内容来源（SP_Id，SP的企业代码）
     //$serv_addr as Src_Id  源号码
    public function Start($host, $port, $username, $password,$normal_service_id,$template_service_id,$msg_src,$serv_addr)
    {
        $this->_normal_service_id=$normal_service_id;
        $this->_template_service_id=$template_service_id;
        $this->_serv_addr=$serv_addr;
        $this->_msg_src=$msg_src;

        $this->_socket = fsockopen($host, $port, $errno, $errstr,  600*1200);
        if (!$this->_socket) {
            $this->_log("Error opening CMPP session.");
            $this->_log("Error was: $errstr.");
            return false;
        }
        socket_set_timeout($this->_socket, 600*1200);
        $status = $this->_CMPP_CONNECT($username, $password);
        if (false===$status) {
            $this->_log("Error Connect to CMPP server. Invalid credentials?");
        }
        
        return $status;
    }
    public function End()
    {
        if (!$this->_socket) {
            // not connected
            return;
        }
        $status = $this->_CMPP_TERMINATE();
        if (false==$status) {
            $this->_log("CMPP Server returned error $status");
            return false;
        }
        fclose($this->_socket);
        $this->_socket = NULL;
        return $status;
    }

    protected function _SendPDU($command_id, $pdu, $requestd)
    {
        $length = strlen($pdu) + 12;
        $header = pack("NNN", $length, $command_id, $this->_sequence_number);
        $this->_log("Sending PDU, len == $length");
        $this->_log("Sending PDU, header-len == " . strlen($header));
        $this->_log("Sending PDU, command_id == " . $command_id);
        $this->_log("Sending PDU, sequence_number == " . $this->_sequence_number);
        $this->_log("Sending PDU, header == ".self::pduord($header));

        $this->_sendData = $requestd;
        
        fwrite($this->_socket, $header . $pdu, $length);usleep(3000);
        $status = $this->_ExpectPDU($this->_sequence_number, $requestd);
        $this->_sequence_number = $this->_sequence_number + 1;
        return $status;
    }
    protected function _ExpectPDU($our_sequence_number, $requestd=NULL)
    {
        do {
            $this->_log("Trying to read PDU.");
            if (feof($this->_socket)) {
                $this->_log("Socket was closed.!!");
                return false;
            }
            $elength = fread($this->_socket, 4);
            if (empty($elength)) {
                $this->_log("Connection lost.");
                return false;
            }
            extract(unpack("Nlength", $elength));
            $this->_log("Reading PDU     : $length bytes.");
            $stream = fread($this->_socket, $length - 4);
            $this->_log("Stream len      : " . strlen($stream));
            extract(unpack("Ncommand_id/Nsequence_number", $stream));
            $command_id &= 0x0fffffff;
            $this->_log("Command id      : $command_id");
            $this->_log("sequence_number : $sequence_number");
            $pdu = substr($stream, 8);
            
            $data = NULL;
            switch ($command_id) {
                case CMPP_CONNECT:
                    $this->_log("Got CMPP_CONNECT_RESP.");
                    $data = $this->_CMPP_CONNECT_RESP($pdu, $requestd);
                    break;
                case CMPP_SUBMIT:
                    $this->_log("Got CMPP_SUBMIT_RESP.");
                    $data = $this->_CMPP_SUBMIT_RESP($pdu, $requestd);
                    break;
                case CMPP_TERMINATE:
                    $this->_log("Got CMPP_TERMINATE_RESP.");
                    break;
                case CMPP_ACTIVE_TEST:
                    $this->_log("Got CMPP_ACTIVE_TEST.");
                    break;
                default:
                    $this->_log("Got unknown CMPP pdu.");
                    break;
            }
            
            $this->_log("Received PDU: " . self::pduord(pack('N', $elength).$stream));
            
        } while ($sequence_number != $our_sequence_number);
        
        $this->_responseData = $data;
        $command_status = false===$data?false:true;
        
        return $command_status;
    }
    protected function _CMPP_CONNECT($Source_Addr, $Shared_Secret, $Version=0x20)
    {
        $data = array();
        // 源地址，此处为SP_Id，即SP的企业代码。
        $data['Source_Addr'] = $Source_Addr;
        $Source_Addr_len = 6;    
        $data['Shared_Secret'] = $Shared_Secret;    
        $data['Timestamp'] = date('mdHis');        
        $data['Version'] = $Version;
        $AuthenticatorSource_ori = $data['Source_Addr'] . pack('a9', '') . $data['Shared_Secret'] . $data['Timestamp'];        
        $data['AuthenticatorSource'] = md5($AuthenticatorSource_ori, true);
        $AuthenticatorSource_len = 16;
        
        $format = "a{$Source_Addr_len}a{$AuthenticatorSource_len}CN";
        $pdu = pack($format, $data['Source_Addr'], $data['AuthenticatorSource'], $data['Version'], $data['Timestamp']);

        $debug_data = $data;
        $debug_data['AuthenticatorSource'] = self::pduord($debug_data['AuthenticatorSource']);
        
        $this->_log("CMPP_CONNECT PDU: " . self::pduord($pdu));
        $this->_log("CMPP_CONNECT SPEC: " . $format);
        $this->_log("CMPP_CONNECT DATA: " . print_r($debug_data, true));
        unset($debug_data);

        return $this->_SendPDU(CMPP_CONNECT, $pdu, $data);
    }

    protected function _CMPP_CONNECT_RESP($pdu, $requestd){
        $format = "CStatus/a16AuthenticatorISMG/CVersion";
        $data = unpack($format, $pdu);
        
        $this->_log("CMPP_CONNECT_RESP PDU: " . self::pduord($pdu));
        $this->_log("CMPP_CONNECT_RESP SPEC: " . $format);
        $this->_log("CMPP_CONNECT_RESP DATA: " . print_r($data, true));
        
        $this->_log("AuthenticatorISMG: " . self::pduord($data['AuthenticatorISMG']));
                
        $status = intval($data['Status']);
        if(strcasecmp('0', $status)){            
                return self::cmpp_connect_resp_status_error($status);
                    
        }elseif(0==strlen($data['AuthenticatorISMG'])){
            return 'ISMG认证码为空';
        }
        
        $checkAuthenticatorISMG = md5($data['Status'].$requestd['AuthenticatorSource'].$requestd['Shared_Secret'], true);
        if(strcmp($checkAuthenticatorISMG, $data['AuthenticatorISMG'])){
            return 'ISMG认证码校验失败';
        }
        
        return $data;
    }
    

    protected function _CMPP_TERMINATE()
    {
         $data="";
        $pdu = "";
        $this->_log("CMPP_TERMINATE PDU: " . self::pduord($pdu));
        return $this->_SendPDU(CMPP_TERMINATE, $pdu, $data);
    }
    
    public static function cmpp_connect_resp_status_error($status){
        $errors = array(
                0 => '正确',
                1 => '消息结构错',
                2 => '非法源地址',
                3 => '认证错',
                4 => '版本太高',
        );
        
        if($status>=0){
            if($status>=5){
                return '其他错误';
            }else{
                return $errors[$status];
            }
        }else{
            return '未知错误';
        }
    }

    protected function _CMPP_SUBMIT($data){
        $Dest_terminal_Id_len = 21*$data['DestUsr_tl'];
        $Msg_Content_len = strlen($data['Msg_Content']);
        
        //$format = "N2CCCCa10CC21CCCa6a2a6a17a17a21Ca{$Dest_terminal_Id_len}Ca{$Msg_Content_len}a8";
        //$format = "a8CCCCa10Ca32CCCCa6a2a6a17a17a21Ca32CCa140a20";
        $format = "a8CCCCa10Ca32CCCCa6a2a6a17a17a21Ca32CCa{$Msg_Content_len}a20";
        $pdu = pack($format
                , $data['Msg_Id']
                , $data['Pk_total'], $data['Pk_number'], $data['Registered_Delivery'], $data['Msg_level']
                , $data['Service_Id']
                , $data['Fee_UserType']
                , $data['Fee_terminal_Id'], $data['Fee_terminal_Type']
                , $data['TP_pId'], $data['TP_udhi'], $data['Msg_Fmt']
                , $data['Msg_src']
                , $data['FeeType']
                , $data['FeeCode']
                , $data['ValId_Time'], $data['At_Time']
                , $data['Src_Id']
                , $data['DestUsr_tl']
                , $data['Dest_terminal_Id'], $data['Dest_terminal_Type']
                , $data['Msg_Length']
                , $data['Msg_Content']
                , $data['Reserve']
        );
 
        $this->_log("CMPP_SUBMIT PDU: " . self::pduord($pdu));
        $this->_log("CMPP_SUBMIT SPEC: " . $format);
        $this->_log("CMPP_SUBMIT DATA: " . print_r($data, true));
        
        return $this->_SendPDU(CMPP_SUBMIT, $pdu, $data);
    }
    
    protected function _CMPP_SUBMIT_RESP($pdu, $requestd){
        $format = "N2Msg_Id/NResult";
        //$format = "C8Msg_Id/CResult";
        $data = unpack($format, $pdu);
        
        $this->_log("CMPP_SUBMIT_RESP PDU: " . self::pduord($pdu));
        $this->_log("CMPP_SUBMIT_RESP SPEC: " . $format);
        $this->_log("CMPP_SUBMIT_RESP DATA: " . print_r($data, true));
    
        $result = intval($data['Result']);
        if(strcasecmp('0', $result)){
            return self::cmpp_connect_resp_status_error($result);
        }
        
        return $data;
    }
    
    public static function cmpp_submit_resp_result_error($result){
        $errors = array(
                0 => '正确',
                1 => '消息结构错',
                2 => '命令字错',
                3 => '消息序号重复',
                4 => '消息长度错',
                5 => '资费代码错',
                6 => '超过最大信息长',
                7 => '业务代码错',
                8 => '流量控制错',
        );
    
        if($result>=0){
            if($result>=9){
                return '其他错误';
            }else{
                return $errors[$result];
            }
        }else{
            return '未知错误';
        }
    }

    public function SendSms($mobile,$context,$text_encoding='UTF-8')
    {
        $data=Array();

        $data['Msg_Id']=0;        
        $data['Registered_Delivery']=0;
        $data['Msg_level']=0;
        $data['Service_Id']=$this->_normal_service_id;
        $data['Fee_UserType']=3;
        $data['Fee_terminal_Id']=$mobile;
        $data['Fee_terminal_Type']=0;
        $data['TP_pId']=0;
        $data['TP_udhi']=1;
        $data['Msg_Fmt']=8;
        $data['Msg_src']=$this->_msg_src;
        $data['FeeType']="01";
        $data['FeeCode']="000000";
        $data['ValId_Time']="";
        $data['At_Time']="";
        $data['Src_Id']=$this->_serv_addr;
        $data['DestUsr_tl']=1;
        $data['Dest_terminal_Id']=$mobile;
        $data['Dest_terminal_Type']=0;
        
        $txt_head = 6;
        $txt_len = 140;
        $max_len = $txt_len-$txt_head;
        $Msg_Content=mb_convert_encoding($context, "UCS-2BE", $text_encoding);
        $msg_sequence = $this->_message_sequence++;

        if(strlen($Msg_Content)<$max_len)
        {
            $data['Pk_total']=1;
            $data['Pk_number']=1;
            $udh = pack("cccccc", 5, 0, 3, $msg_sequence, 1, 1);
            $data['Msg_Content']=$udh .$Msg_Content;
            $data['Msg_Length']=strlen($data['Msg_Content']);
            $data['Reserve']='';
            $this->_log("CMPP_SUBMIT_DATA PDU: " . self::pduord($data['Msg_Content']));
            $status = $this->_CMPP_SUBMIT($data);
            $this->_log($status);
            return ;
        }
        $pos = 0;
        $num_messages = ceil(strlen($Msg_Content) / $max_len);
        $this->_log($num_messages);
        for($i=0;$i<$num_messages;$i++)
        {
            $data['Pk_total']=$num_messages;
            $data['Pk_number']=$i+1;
            $udh = pack("cccccc", 5, 0, 3, $msg_sequence, $num_messages, $i+1);
            $data['Msg_Content']=$udh .substr($Msg_Content, $i*$max_len, $max_len);
            $data['Msg_Length']=strlen($data['Msg_Content']);
            $data['Reserve']='';
            $this->_log("CMPP_SUBMIT_DATA PDU: " . self::pduord($data['Msg_Content']));            
            $status = $this->_CMPP_SUBMIT($data);
            $this->_log($status);
        }            
    }

    public function SendSmsT($mobile,$strKey,$param,$text_encoding='UTF-8')
    {
        $data=Array();

        $data['Msg_Id']=0;
        $data['Pk_total']=1;
        $data['Pk_number']=1;
        $data['Registered_Delivery']=0;
        $data['Msg_level']=0;
        $data['Service_Id']=$this->_template_service_id;
        $data['Fee_UserType']=3;
        $data['Fee_terminal_Id']=$mobile;
        $data['Fee_terminal_Type']=0;
        $data['TP_pId']=0;
        $data['TP_udhi']=0;
        $data['Msg_Fmt']=8;
        $data['Msg_src']=$this->_msg_src;
        $data['FeeType']="01";
        $data['FeeCode']="000000";
        $data['ValId_Time']="";
        $data['At_Time']="";
        $data['Src_Id']=$this->_serv_addr;
        $data['DestUsr_tl']=1;
        $data['Dest_terminal_Id']=$mobile;
        $data['Dest_terminal_Type']=0;
        
        if(!is_array($param)) { $this->_log("Your params none!");return ;}

        $context="<cmppTemplate><template>{$strKey}</template>";
        for($i=0;$i<count($param);$i++)
        {
            $context.=sprintf("<node%d>%s</node%d>",$i+1,$param[$i],$i+1);
        }
        $context.="</cmppTemplate>";
        $this->_log($context);

        $data['Msg_Content']=mb_convert_encoding($context, "UCS-2BE", $text_encoding);
        $data['Msg_Length']=strlen($data['Msg_Content']);
        $data['Reserve']='';

        $status = $this->_CMPP_SUBMIT($data);
        $this->_log($status);
        
    }    

    
    
    public function Active()
    {
        $data="";
        $this->_SendPDU(CMPP_ACTIVE_TEST, "", $data);
    }
}

$context="经济观察网 实习记者 骆贝贝7月18日，欧盟宣布，对全球第一大芯片制造商高通公司处以高达2.42亿欧元（约合18.69亿元人民币）的罚款，原因是高通在2009年至2011年期间，为了让英国手机软件制造商Icera退出竞争，“以掠夺性定价方式伤害竞争对手”。而这是一年多时间以来，高通第二次受到欧盟的反垄断处罚。";

$cmpp=new Cmpp30();
$cmpp->Start("",7891,"","@","","","","");
//$cmpp->SendSms("13888888888","123131313112313131311231313131123131313112313131311231313131123131313112313131311231313131123131313112313131311231313131123131313112313131311231313131123131313112313131311231313131");


?>


<?php
//接收短信:  填入公司的接入码什么的,然后使用screen执行下面这个文件就可以了
class Cmpp {
    // 设置项
    public $host = "";   //服务商ip
    public $port = "17890";           //端口号
    public $Source_Addr = "";           //企业id  企业代码
    public $Shared_secret = '';         //网关登录密码
    public $Dest_Id = "";      //短信接入码 短信端口号
    public $SP_ID = "";
    public $SP_CODE = "";
    public $Service_Id = "";  
    public $deliver;
    private $socket;
    private $Sequence_Id = 1;
    private $bodyData;
    private $AuthenticatorSource;
    public $CMPP_CONNECT = 0x00000001; // 请求连接
    public $CMPP_CONNECT_RESP = 0x80000001; // 请求连接
    public $CMPP_DELIVER = 0x00000005; // 短信下发
    public $CMPP_DELIVER_RESP = 0x80000005; // 下发短信应答
    public $CMPP_ACTIVE_TEST = 0x00000008; // 激活测试
    public $CMPP_ACTIVE_TEST_RESP = 0x80000008; // 激活测试应答
    public $CMPP_SUBMIT = 0x00000004; // 短信发送
    public $CMPP_SUBMIT_RESP = 0x80000004; // 发送短信应答
    public static $msgid = 1;
    public function createSocket(){
        $this->socket =socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
        socket_connect($this->socket,$this->host, $this->port); 
    }
    public function CMPP_CONNECT(){
        date_default_timezone_set('PRC'); 
        $Source_Addr = $this->Source_Addr;
        $Version = 0x30;
        $Timestamp = date('mdHis');
        //echo $Timestamp;
        $AuthenticatorSource = $this->createAS($Timestamp);
        $bodyData = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
        $this->AuthenticatorSource = $AuthenticatorSource;
        $this->send($bodyData, "CMPP_CONNECT"); 
    }
    public function CMPP_CONNECT_RESP(){
        echo "connect success";
        $body = unpack("CStatus/a16AuthenticatorISMG/CVersion", $this->bodyData);
    }
    public function send($bodyData, $Command,$Sequence=0){
        $Command_Id=0x00000001;
        if($Command =="CMPP_CONNECT"){
            $Command_Id = 0x00000001;
        }elseif($Command =="CMPP_DELIVER_RESP"){
            $Command_Id = 0x80000005;
        }elseif($Command =="CMPP_ACTIVE_TEST_RESP"){
            $Command_Id = 0x80000008;
        }elseif($Command =="CMPP_SUBMIT"){
            $Command_Id = 0x00000004;
        }
        $Total_Length = strlen($bodyData) + 12;
        if($Sequence==0){
            if($this->Sequence_Id <10){
                $Sequence_Id = $this->Sequence_Id;
            }else{
                $Sequence_Id =1;
                $this->Sequence_Id=1;
            }
            $this->Sequence_Id = $this->Sequence_Id+1;
        }else{
            $Sequence_Id = $Sequence;
        }
        $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
        // 发送消息
        $this->log("send $Command_Id");
        socket_write($this->socket, $headData.$bodyData, $Total_Length);  
        // $this->listen($Sequence_Id);
        $i=1;
        do{
            $this->listen($Sequence_Id);
            //$i = $i-1;
            sleep(15);//等待时间，进行下一次操作
        }while($i>0);
    }
    public function listen($Sequence_Id){
            // 处理头
            $headData = '';
        try {
            echo 1;
            $headData = socket_read($this->socket, 12);
            if($headData===false){
                system("php -f ./smtp465/smtpsenderror.php");
                $this->resets();
            }
        } catch (Exception $e) {
            echo "reset now \n";
            $this->resets();
            return false;
        }
            if(empty($headData)){
                $this->log("0000");
                return;
            }
            $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
            $this->log("get ".($head['Command_Id'] & 0x0fffffff));
            $Sequence_Id = $head['Sequence_Id'];
            // 处理body
            $this->bodyData = socket_read($this->socket,$head['Total_Length'] - 12);
            //var_dump($this->bodyData);
            switch ( $head['Command_Id'] & 0x0fffffff ) {
                case 0x00000001:
                    $this->CMPP_CONNECT_RESP();
                    break;
                case 0x00000005:
                    $this->CMPP_DELIVER($head['Total_Length'],$Sequence_Id);
                    break;
                case 0x80000005:
                    $this->CMPP_DELIVER($head['Total_Length'],$Sequence_Id);
                    break;
                case 0x00000008:
                    $bodyData=pack("C",1);                   //数据联络包返回
                    $this->send($bodyData, "CMPP_ACTIVE_TEST_RESP",$Sequence_Id);
                    break;
                default:
                    $bodyData=pack("C",1);
                    $this->send($bodyData, "CMPP_ACTIVE_TEST_RESP",$Sequence_Id);
                    break;
            }
    }
    public function CMPP_DELIVER($Total_Length,$Sequence_Id){    //Msg_Id直接用N解析不行,N只有4位
        $contentlen = $Total_Length-109;
        $body = unpack("N2Msg_Id/a21Dest_Id/a10Service_Id/CTP_pid/CTP_udhi/CMsg_Fmt/a32Src_terminal_Id/CSrc_terminal_type/CRegistered_Delivery/CMsg_Length/a".$contentlen."Msg_Content/a20LinkID",$this->bodyData);
        var_dump($body);
        if($body['Msg_Length']>0){
            $data = $body['Msg_Content'];
            //$Msg_Id = $body['Msg_Id'];
            $Msg_Id = ($body['Msg_Id1']& 0x0fffffff);
            $Msg_Idfu = $body['Msg_Id2'];
            $msgidz = unpack("N",substr($this->bodyData,0,8));
            $msgidzz = '0000' .$msgidz[1];
            $kahao = $body['Src_terminal_Id'];
            mysql_connect('localhost','','');
            mysql_select_db('');
            mysql_query('set names utf8');
            $data = trim($data);
            $sql1 = "select id from socket_yd where msgid='".$Msg_Id."'";
            $chongfu = mysql_query($sql1);
            $arrs =array();
            while($arr= mysql_fetch_assoc($chongfu) ){
                $arrs[] = $arr;
            }
            if( $arrs==array() || $arrs[0] == null ){
                $sql = "insert into socket_yd set msgid='".$Msg_Id."',kahao='".$kahao."', content='".addslashes($data)."', add_time='".date('Y-m-d H:i:s')."'";
                mysql_query($sql);
            }
            mysql_close();
            //echo $Msg_Id."\n";
            echo $data."\n";
            echo $Msg_Id.'...'.$kahao."\n";
            $this->CMPP_DELIVER_RESP($msgidzz,$Msg_Idfu,$Sequence_Id);
        }
    }
    // N打包只有4位
    public function CMPP_DELIVER_RESP($Msg_Id,$Msg_Idfu,$Sequence_Id){
        $sendda2 = 0x00;
        $bodyData = pack("N", $Msg_Id).pack("N", $Msg_Idfu).pack("N",$sendda2);
        $this->send($bodyData, "CMPP_DELIVER_RESP",$Sequence_Id);
    }
    /**AuthenticatorSource = MD5(Source_Addr+9 字节的0 +shared secret+timestamp) */
    public function createAS($Timestamp){
        $temp = $this->Source_Addr . pack("a9","") . $this->Shared_secret . $Timestamp;
        return md5($temp, true);
    }
    /*** AuthenticatorISMG =MD5(Status + AuthenticatorSource + shared secret) */
    public function cheakAISMG($Status, $AuthenticatorISMG){
        $temp = $Status . $this->AuthenticatorSource . $this->Shared_secret;
        $this->debug($temp.pack("a",""), 1, 1);
        $this->debug($AuthenticatorISMG.pack("a",""), 2, 1);
        if($AuthenticatorISMG != md5($temp, true)){
            $this->throwErr("ISMG can't pass check .", __LINE__);
        }
    }
public function closes(){
        socket_close($this->socket);
    }
    public function resets(){
        socket_close($this->socket);
        $this->createSocket();
        $this->CMPP_CONNECT();
    }
    public function log($data, $line = null){
        if($line){
            $data = $line . " : ".$data;
        }
        file_put_contents("./cmpp.log", print_r($data, true).PHP_EOL, FILE_APPEND);
    }
    public function debug($data, $fileName, $noExit = false){
        file_put_contents("./$fileName.debug", print_r($data, true));
        if(!$noExit) exit;
    }
    public function throwErr($info, $line){
        die("info: $info in line :$line");
    }
   
}

@unlink("./cmpp.log");
$cmpp = new Cmpp;
$cmpp->createSocket();
$cmpp->CMPP_CONNECT();

?>

下面是发送短信:  前端部分就不发了,后端我也是用exec($str, $out, $res);来执行php文件,方便.

后台:

$tomsisdn = $_POST["tomsisdn"];
$contents = $_POST["contents"];
$str = "php -f /var/www/html/CmppSubmit.php {$tomsisdn} {$contents}";
//echo $str."\n";
exec($str, $out, $res);
if($res ==0)
echo $out[1];
//print_r($out);

<?php
class CMPPSubmit{
    // 设置项      
    public $host = "";   //服务商ip
    public $port = "17890";           //短连接端口号   17890长连接端口号
    public $Source_Addr = "";           //企业id  企业代码
    public $Shared_secret = '';         //网关登录密码
    public $Dest_Id = "";      //短信接入码 短信端口号
    public $SP_ID = "";
    public $SP_CODE = "";
    public $Service_Id = "";    //业务代码   这个是业务代码
    public $deliver;
    private $socket;
    private $Sequence_Id = 1;
    private $bodyData;
    private $AuthenticatorSource;
    public $CMPP_CONNECT = 0x00000001; // 请求连接
    public $CMPP_CONNECT_RESP = 0x80000001; // 请求连接
    public $CMPP_SUBMIT = 0x00000004; // 短信发送
    public $CMPP_SUBMIT_RESP = 0x80000004; // 发送短信应答
    public $CMPP_DELIVER = 0x00000005; // 短信下发
    public $CMPP_DELIVER_RESP = 0x80000005; // 下发短信应答
    public $CMPP_ACTIVE_TEST = 0x00000008; // 激活测试
    public $CMPP_ACTIVE_TEST_RESP = 0x80000008; // 激活测试应答
    public $msgid = 1;
    public $tomsisdn = '';
    public $contents = '';
    public function __construct($argv1,$argv2){
        if($argv1){
            $this->tomsisdn = $argv1;
            $this->contents = $argv2; 
        }else{
            $this->log("has no canshu");exit;
        }
    }
    public function createSocket(){
        $this->socket =socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
        if(!$this->socket) {echo "can't creat socket";exit;}
        $result = socket_connect($this->socket,$this->host, $this->port) or die(socket_strerror());
        $this->CMPP_CONNECT();
    }
    public function CMPP_CONNECT(){
        date_default_timezone_set('PRC'); 
        $Source_Addr = $this->Source_Addr;
        $Version = 0x30;
        $Timestamp = date('mdHis');
        //echo $Timestamp;
        $AuthenticatorSource = $this->createAS($Timestamp);
        $bodyData = pack("a6a16CN", $Source_Addr, $AuthenticatorSource, $Version, $Timestamp);
        $this->AuthenticatorSource = $AuthenticatorSource;
        $this->send($bodyData, "CMPP_CONNECT");
        
    }
    public function CMPP_CONNECT_RESP(){
        echo "CMPP_CONNECT_RESP success \n";
        $body = unpack("CStatus/a16AuthenticatorISMG/CVersion", $this->bodyData);
        $this->CMPP_SUBMIT();
    }
    public function send($bodyData, $Command,$Sequence=0){
        $Command_Id=0x00000001;
        if($Command =="CMPP_CONNECT"){     //cmpp连接
            $Command_Id = 0x00000001;
        }elseif($Command =="CMPP_DELIVER_RESP"){     //下发应答
            $Command_Id = 0x80000005;
        }elseif($Command =="CMPP_ACTIVE_TEST_RESP"){    //数据链路应答
            $Command_Id = 0x80000008;
        }elseif($Command =="CMPP_SUBMIT"){        //发送短信
            $Command_Id = 0x00000004;
        }
        $Total_Length = strlen($bodyData) + 12;
        if($Sequence==0){
            if($this->Sequence_Id <10){
                $Sequence_Id = $this->Sequence_Id;
            }else{
                $Sequence_Id =1;
                $this->Sequence_Id=1;
            }
            $this->Sequence_Id = $this->Sequence_Id+1;
        }else{
            $Sequence_Id = $Sequence;
        }
        $headData = pack("NNN", $Total_Length, $Command_Id, $Sequence_Id);
        // 发送消息
        $this->log("send $Command_Id");
        socket_write($this->socket, $headData.$bodyData, $Total_Length);  
        $this->listen($Sequence_Id);
    }
    public function listen($Sequence_Id){
            // 处理头
            $headData = socket_read($this->socket, 12);
            if(empty($headData)){
                $this->log("0000");
                return;
            }
            $head = unpack("NTotal_Length/NCommand_Id/NSequence_Id", $headData);
            $this->log("get ".($head['Command_Id'] & 0x0fffffff));
            $Sequence_Id = $head['Sequence_Id'];
            // 处理body
            $this->bodyData = socket_read($this->socket,$head['Total_Length'] - 12);
            //var_dump($this->bodyData);
            switch ( $head['Command_Id'] & 0x0fffffff ) {
                case 0x00000001:
                    $this->CMPP_CONNECT_RESP();
                    break;
                // case 0x00000005:
                //     $this->CMPP_DELIVER($head['Total_Length'],$Sequence_Id);
                //     break;
                // case 0x80000005:
                //     $this->CMPP_DELIVER($head['Total_Length'],$Sequence_Id);
                //     break;
                case 0x00000008:
                    $bodyData=pack("C",1);                   //数据联络包返回
                    $this->send($bodyData, "CMPP_ACTIVE_TEST_RESP",$Sequence_Id);
                    break;
                case 0x00000004:
                    $this->CMPP_SUBMIT_RESP();
                    break;
                // case 0x80000004:
                //     $this->CMPP_SUBMIT_RESP();
                //     break;
                default:
                    $bodyData=pack("C",1);
                    $this->send($bodyData, "CMPP_ACTIVE_TEST_RESP",$Sequence_Id);
                    break;
            }
    }
public function CMPP_DELIVER($Total_Length,$Sequence_Id){    //Msg_Id直接用N解析不行
$contentlen = $Total_Length-109;
$body = unpack("N2Msg_Id/a21Dest_Id/a10Service_Id/CTP_pid/CTP_udhi/CMsg_Fmt/a32Src_terminal_Id/CSrc_terminal_type/CRegistered_Delivery/CMsg_Length/a".$contentlen."Msg_Content/a20LinkID",$this->bodyData);
var_dump($body);
if($body['Msg_Length']>0){
$data = $body['Msg_Content'];
//$Msg_Id = $body['Msg_Id'];
$Msg_Id = ($body['Msg_Id1']& 0x0fffffff);
$Msg_Idfu = $body['Msg_Id2'];
$msgidz = unpack("N",substr($this->bodyData,0,8));
$msgidzz = '0000' .$msgidz[1];
mysql_connect('localhost','','');
mysql_select_db('');
mysql_query('set names utf8');
$data = trim($data);
$sql1 = "select id from socket_yd where msgid='".$Msg_Id."'";
$chongfu = mysql_query($sql1);
$arrs =array();
while($arr= mysql_fetch_assoc($chongfu) ){
$arrs[] = $arr;
}
if( $arrs==array() || $arrs[0] == null ){
    $sql = "insert into socket_yd set msgid='".$Msg_Id."', content='".addslashes($data)."', add_time='".date('Y-m-d H:i:s')."'";
mysql_query($sql);
}
mysql_close();
//echo $Msg_Id."\n";
echo $data."\n";
echo $msgidzz."\n";
echo $Sequence_Id."\n";
$this->CMPP_DELIVER_RESP($msgidzz,$Msg_Idfu,$Sequence_Id);
}
}
// N打包只有4位 
public function CMPP_DELIVER_RESP($Msg_Id,$Msg_Idfu,$Sequence_Id){
$sendda2 = 0x00;
$bodyData = pack("NNN", $Msg_Id, $Msg_Idfu,$sendda2);
$this->send($bodyData, "CMPP_DELIVER_RESP",$Sequence_Id);
}
public function CMPP_SUBMIT(){
$Msg_Id = rand(1,100);
//$bodyData = pack("a8", $Msg_Id);
$bodyData = pack("N", $Msg_Id).pack("N", "00000000");
$bodyData .= pack("C", 1).pack("C", 1);
$bodyData .= pack("C", 0).pack("C", 0);
$bodyData .= pack("a10", $this->Service_Id);
$bodyData .= pack("C", 0).pack("a32", "").pack("C", 0).pack("C", 0).pack("C", 0).pack("C", 0).pack("a6", $this->SP_ID).pack("a2", "02").pack("a6", "").pack("a17", "").pack("a17", "").pack("a21", $this->Dest_Id).pack("C", 1);
$bodyData .= pack("a32", $this->tomsisdn);
$bodyData .= pack("C", 0);
$len = strlen($this->contents);
$bodyData .= pack("C", $len);
$bodyData .= pack("a".$len, $this->contents);
$bodyData .= pack("a20", "00000000000000000000");
//echo '内容长度:包总长度-183='.(strlen($bodyData)-183)."字节\n"; 
$this->send($bodyData, "CMPP_SUBMIT",$Msg_Id);
}
public function CMPP_SUBMIT_RESP(){
echo "CMPP_SUBMIT_RESP success"."\n";
$body = unpack("N2Msg_Id/NResult",$this->bodyData);
print_r($body);
socket_close($this->socket);
}
    /**AuthenticatorSource = MD5(Source_Addr+9 字节的0 +shared secret+timestamp) */
public function createAS($Timestamp){
$temp = $this->Source_Addr . pack("a9","") . $this->Shared_secret . $Timestamp;
return md5($temp, true);
}
public function log($data, $line = null){
if($line){
$data = $line . " : ".$data;
}
file_put_contents("./cmpp1.log", print_r($data, true).PHP_EOL, FILE_APPEND);
}
}
// @unlink("./cmpp1.log");
$cmpp = new CMPPSubmit($argv[1],$argv[2]);
$cmpp->createSocket();
// $cmpp->CMPP_CONNECT();
// $cmpp->CMPP_SUBMIT();
?>

请求头
000000c1
00000004
00000002

3100000000000000
0000000001000000
01
00
00
000100000000000000323137303632000000000000000031353230313932363137310000000000000000000000000000000000000f000000313031313631320000000000000000000000000000000000000000000000000000000000000000000000000000000000313036393238303830313539000000000000000000010000003135323031393236313731000000000000000000000c000000b6ccd0c5b7a2cbcdb2e2cad400000000

72


Array
(
    [Msg_Id1] => 3023342976
    [Msg_Id2] => 655502663
    [Dest_Id] => 106928080159
    [Service_Id] => 101161
    [TP_pid] => 0
    [TP_udhi] => 0
    [Msg_Fmt] => 0
    [Src_terminal_Id] => 15201926171
    [Registered_Delivery] => 1
    [Msg_Length] => 60
    [Msg_Content] => ´-@'DELIVRD1911081116191108130815201926171´ґ`
    [Reserved] => 
)

Array
(
    [Msg_Id1] => 3023488960
    [Msg_Id2] => 655509773
    [Dest_Id] => 106928080159
    [Service_Id] => 101161
    [TP_pid] => 0
    [TP_udhi] => 0
    [Msg_Fmt] => 0
    [Src_terminal_Id] => 15201926171
    [Registered_Delivery] => 1
    [Msg_Length] => 60
    [Msg_Content] => ´6h󿾧>gDELIVRD1911081338191108134415201926171&b 
    [Reserved] => 
)

提交的Sequence_Id:2,解析的Msg_Id:3023489024655508274

Array
(
    [Msg_Id] => ³ŧ󿾧 
    [Dest_Id] => 106928080159
    [Service_Id] => 101161
    [TP_pid] => 0
    [TP_udhi] => 0
    [Msg_Fmt] => 0
    [Src_terminal_Id] => 15201926171
    [Registered_Delivery] => 1
    [Msg_Length] => 60
    [Msg_Content] => ³ŧ'ۣDELIVRD1911071738191107173815201926171¢
                                                                
    [Reserved] => 
)

Array
(
    [Msg_Id1] => 3016442560
    [Msg_Id2] => 655497558
    [Dest_Id] => 106928080159
    [Service_Id] => 101161
    [TP_pid] => 0
    [TP_udhi] => 0
    [Msg_Fmt] => 0
    [Src_terminal_Id] => 15201926171
    [Registered_Delivery] => 1
    [Msg_Length] => 60
눖RD1911071852191107185215201926171Nѩ 
    [Reserved] => 
)

发送的msg_id:1573206138000002
69645824
118650981
Array
(
    [Msg_Id1] => 3022435840
    [Msg_Id2] => 655521893
    [Dest_Id] => 106928080159
    [Service_Id] => 101161
    [TP_pid] => 0
    [TP_udhi] => 0
    [Msg_Fmt] => 0
    [Src_terminal_Id] => 15201926171
    [Registered_Delivery] => 1
    [Msg_Length] => 60
    [Msg_Content] => ´&´'pӄELIVRD1911080943191108094315201926171Ȕ26
    [Reserved] => 
)
2147483656