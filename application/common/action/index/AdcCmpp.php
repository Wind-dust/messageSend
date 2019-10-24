<?php
// namespace CMPP30;
namespace app\common\action\index;

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
define('CMPP_FWD', 0x00000009); // 消息前转
define('CMPP_FWD_RESP', 0x80000009); // 消息前转应答
define('CMPP_MT_ROUTE', 0x00000010); // MT路由请求
define('CMPP_MT_ROUTE_RESP', 0x80000010); // MT路由请求应答
define('CMPP_MO_ROUTE', 0x00000011); // MO路由请求
define('CMPP_MO_ROUTE_RESP', 0x80000011); // MO路由请求应答
define('CMPP_GET_ROUTE', 0x00000012); // 获取路由请求
define('CMPP_GET_ROUTE_RESP', 0x80000012); // 获取路由请求应答
define('CMPP_MT_ROUTE_UPDATE', 0x00000013); // MT路由更新
define('CMPP_MT_ROUTE_UPDATE_RESP', 0x80000013); // MT路由更新应答
define('CMPP_MO_ROUTE_UPDATE', 0x00000014); // MO路由更新
define('CMPP_MO_ROUTE_UPDATE_RESP', 0x80000014); // MO路由更新应答
define('CMPP_PUSH_MT_ROUTE_UPDATE', 0x00000015); // MT路由更新
define('CMPP_PUSH_MT_ROUTE_UPDATE_RESP', 0x80000015); // MT路由更新应答
define('CMPP_PUSH_MO_ROUTE_UPDATE', 0x00000016); // MO路由更新
define('CMPP_PUSH_MO_ROUTE_UPDATE_RESP', 0x80000016); // MO路由更新应答



class AdcCmpp extends Adc{
	
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
	
	/**
	 * 收到数据的数组格式
	 * @var array
	 */
	protected $_ArrayModeData = array();
	
	public function __construct($Config=NULL){
		parent::__construct($Config);
		
		$this->_socket = NULL;
		$this->_sequence_number = 1;
		$this->_message_sequence = rand(1,255);
	}
	
	/*
	 This method initiates an SMPP session.
	It is to be called BEFORE using the Send() method.
	Parameters:
	$host		: SMPP ip to connect to.
	$port		: port # to connect to.
	$username	: SMPP system ID
	$password	: SMPP passord.
	$system_type	: SMPP System type
	Returns:
	true if successful, otherwise false
	Example:
	$smpp->Start("smpp.chimit.nl", 2345, "chimit", "my_password", "client01");
	*/
	public function Start($host, $port, $username, $password)
	{
		$this->_socket = fsockopen($host, $port, $errno, $errstr, 20);
		// todo: sanity check on input parameters
		if (!$this->_socket) {
			$this->_log("Error opening CMPP session.", ADC_LOG_ERR);
			$this->_log("Error was: $errstr.", ADC_LOG_ERR);
			return false;
		}
		socket_set_timeout($this->_socket, 1200);
		$status = $this->_CMPP_CONNECT($username, $password);
		if (false===$status) {
			$this->_log("Error Connect to CMPP server. Invalid credentials?", ADC_LOG_ERR);
		}
		
		return $status;
	}
	
	/*
	 This method ends a SMPP session.
	Parameters:
	none
	Returns:
	true if successful, otherwise false
	Example: $smpp->End();
	*/
	public function End()
	{
		if (!$this->_socket) {
			// not connected
			return;
		}
		$status = $this->_CMPP_TERMINATE();
		if (false==$status) {
			$this->_log("CMPP Server returned error $status", ADC_LOG_ERR);
			return false;
		}
		fclose($this->_socket);
		$this->_socket = NULL;
		return $status;
	}
	
 
	protected function _ExpectPDU($our_sequence_number, $requestd=NULL)
	{
		do {
			$this->_log("Trying to read PDU.");
			if (feof($this->_socket)) {
				$this->_log("Socket was closed.!!", ADC_LOG_ERR);
				return false;
			}
			$elength = fread($this->_socket, 4);
			if (empty($elength)) {
				$this->_log("Connection lost.", ADC_LOG_ERR);
				return false;
			}
			extract(unpack("Nlength", $elength));
			$this->_log("Reading PDU     : $length bytes.", ADC_LOG_DEBUG);
			$stream = fread($this->_socket, $length - 4);
			$this->_log("Stream len      : " . strlen($stream), ADC_LOG_DEBUG);
			extract(unpack("Ncommand_id/Nsequence_number", $stream));
			$command_id &= 0x0fffffff;
			$this->_log("Command id      : $command_id", ADC_LOG_DEBUG);
			$this->_log("sequence_number : $sequence_number", ADC_LOG_DEBUG);
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
					
				default:
					$this->_log("Got unknown CMPP pdu.");
					break;
			}
			
			$this->_log("Received PDU: " . self::pduord(pack('N', $elength).$stream) , ADC_LOG_DEBUG);
			
		} while ($sequence_number != $our_sequence_number);
		
		$this->_responseData = $data;
		$command_status = false===$data?false:true;
		
		return $command_status;
	}
	
	protected function _SendPDU($command_id, $pdu, $requestd)
	{
		$length = strlen($pdu) + 12;
		$header = pack("NNN", $length, $command_id, $this->_sequence_number);
		$this->_log("Sending PDU, len == $length", ADC_LOG_DEBUG);
		$this->_log("Sending PDU, header-len == " . strlen($header), ADC_LOG_DEBUG);
		$this->_log("Sending PDU, command_id == " . $command_id, ADC_LOG_DEBUG);
		$this->_log("Sending PDU, sequence_number == " . $this->_sequence_number, ADC_LOG_DEBUG);
		
		$this->_sendData = $requestd;
		
		fwrite($this->_socket, $header . $pdu, $length);
		$status = $this->_ExpectPDU($this->_sequence_number, $requestd);
		$this->_sequence_number = $this->_sequence_number + 1;
		return $status;
	}
	
 
	protected function _CMPP_TERMINATE()
	{
		$pdu = "";
		$this->_log("CMPP_TERMINATE PDU: " . self::pduord($pdu), ADC_LOG_DEBUG);
		return $this->_SendPDU(CMPP_TERMINATE, $pdu);
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
		//$data['Version'] = 0x20;
		
		// 用于鉴别源地址。其值通过单向MD5 hash计算得出，表示如下：
		// AuthenticatorSource= MD5（Source_Addr+9  字节的0 +shared secret+timestamp）
		// Shared secret 由中国移动与源地址实体事先商定，timestamp 格式为：MMDDHHMMSS，即月日时分秒，10位。
		//$AuthenticatorSource_ori = $data['Source_Addr'] . str_pad('', 9, '0') . $data['Shared_Secret'] . $data['Timestamp'];
		$AuthenticatorSource_ori = $data['Source_Addr'] . pack('a9', '') . $data['Shared_Secret'] . $data['Timestamp'];
		
		$data['AuthenticatorSource'] = md5($AuthenticatorSource_ori, true);
		$AuthenticatorSource_len = 16;
		
		$format = "a{$Source_Addr_len}a{$AuthenticatorSource_len}CN";
		$pdu = pack($format, $data['Source_Addr'], $data['AuthenticatorSource'], $data['Version'], $data['Timestamp']);
	
		$debug_data = $data;
		$debug_data['AuthenticatorSource'] = self::pduord($debug_data['AuthenticatorSource']);
		
		$this->_log("CMPP_CONNECT PDU: " . self::pduord($pdu), ADC_LOG_DEBUG);
		$this->_log("CMPP_CONNECT SPEC: " . $format, ADC_LOG_DEBUG);
		$this->_log("CMPP_CONNECT DATA: " . print_r($debug_data, true), ADC_LOG_DEBUG);
		unset($debug_data);
 
		//$this->_log('AuthenticatorSource Len: ' . strlen($data['AuthenticatorSource']), ADC_LOG_DEBUG);
		//$this->_log('AuthenticatorSource Ori: ' . $AuthenticatorSource_ori, ADC_LOG_DEBUG);
		//$this->_log('AuthenticatorSource MD5: ' . md5($AuthenticatorSource_ori), ADC_LOG_DEBUG);
		
		return $this->_SendPDU(CMPP_CONNECT, $pdu, $data);
	}
	
	
	protected function _CMPP_CONNECT_RESP($pdu, $requestd){
		$format = "CStatus/a16AuthenticatorISMG/CVersion";
		$data = unpack($format, $pdu);
		
		$this->_log("CMPP_CONNECT_RESP PDU: " . self::pduord($pdu), ADC_LOG_DEBUG);
		$this->_log("CMPP_CONNECT_RESP SPEC: " . $format, ADC_LOG_DEBUG);
		$this->_log("CMPP_CONNECT_RESP DATA: " . print_r($data, true), ADC_LOG_DEBUG);
		
		$this->_log("AuthenticatorISMG: " . self::pduord($data['AuthenticatorISMG']), ADC_LOG_DEBUG);
		
		/** ISMG认证码，用于鉴别ISMG。其值通过单向MD5 hash计算得出，表示如下：AuthenticatorISMG =MD5（Status+AuthenticatorSource+shared secret），Shared secret 由中国移动与源地址实体事先商定，AuthenticatorSource为源地址实体发送给ISMG 的对应消息CMPP_Connect中的值。认证出错时，此项为空。
		 */
		$status = intval($data['Status']);
		if(strcasecmp('0', $status)){
			return $this->_throw_exception(
					self::cmpp_connect_resp_status_error($status)
					, ADC_ERR_RESPONSE_STATUS
					, $data
					, __FILE__, __LINE__
			);
		}elseif(0==strlen($data['AuthenticatorISMG'])){
			return $this->_throw_exception(
					'ISMG认证码为空'
					, ADC_ERR_RESPONSE_STATUS
					, $data
					, __FILE__, __LINE__
			);
		}
		
		$checkAuthenticatorISMG = md5($data['Status'].$requestd['AuthenticatorSource'].$requestd['Shared_Secret'], true);
		if(strcmp($checkAuthenticatorISMG, $data['AuthenticatorISMG'])){
			return $this->_throw_exception(
					'ISMG认证码校验失败'
					, ADC_ERR_RESPONSE_STATUS
					, array('PDU DATA'=>$data, 'checkAuthenticatorISMG' => $checkAuthenticatorISMG)
					, __FILE__, __LINE__
			);
		}
		
		return $data;
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
		$format = "a8CCCCa10Ca21CCCa6a2a6a17a17a21Ca{$Dest_terminal_Id_len}Ca{$Msg_Content_len}a8";
		$pdu = pack($format
				, $data['Msg_Id']
				, $data['Pk_total'], $data['Pk_number'], $data['Registered_Delivery'], $data['Msg_level']
				, $data['Service_Id']
				, $data['Fee_UserType']
				, $data['Fee_terminal_Id']
				, $data['TP_pId'], $data['TP_udhi'], $data['Msg_Fmt']
				, $data['Msg_src']
				, $data['FeeType']
				, $data['FeeCode']
				, $data['ValId_Time'], $data['At_Time']
				, $data['Src_Id']
				, $data['DestUsr_tl']
				, $data['Dest_terminal_Id']
				, $data['Msg_Length']
				, $data['Msg_Content']
				, $data['Reserve']
		);
 
		$this->_log("CMPP_SUBMIT PDU: " . self::pduord($pdu), ADC_LOG_DEBUG);
		$this->_log("CMPP_SUBMIT SPEC: " . $format, ADC_LOG_DEBUG);
		$this->_log("CMPP_SUBMIT DATA: " . print_r($data, true), ADC_LOG_DEBUG);
		
		return $this->_SendPDU(CMPP_SUBMIT, $pdu, $data);
	}
	
	protected function _CMPP_SUBMIT_RESP($pdu, $requestd){
		$format = "N2Msg_Id/CResult";
		//$format = "C8Msg_Id/CResult";
		$data = unpack($format, $pdu);
		
		$this->_log("CMPP_SUBMIT_RESP PDU: " . self::pduord($pdu), ADC_LOG_DEBUG);
		$this->_log("CMPP_SUBMIT_RESP SPEC: " . $format, ADC_LOG_DEBUG);
		$this->_log("CMPP_SUBMIT_RESP DATA: " . print_r($data, true), ADC_LOG_DEBUG);
	
		$result = intval($data['Result']);
		if(strcasecmp('0', $result)){
			return $this->_throw_exception(
					self::cmpp_connect_resp_status_error($result)
					, ADC_ERR_RESPONSE_STATUS
					, $data
					, __FILE__, __LINE__
			);
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
	
	protected function _CMPP_QUERY($data){
		$format = "a8Ca10a8";
		$pdu = pack($format
				, $data['Time']
				, $data['Query_Type']
				, $data['Query_Code']
				, $data['Reserve']
		);
	
		$this->_log("CMPP_QUERY PDU: " . self::pduord($pdu), ADC_LOG_DEBUG);
		$this->_log("CMPP_QUERY SPEC: " . $format, ADC_LOG_DEBUG);
		$this->_log("CMPP_QUERY DATA: " . print_r($data, true), ADC_LOG_DEBUG);
	
		$status = $this->_SendPDU(CMPP_QUERY, $pdu, $data);
		return $status;
	}
	
	protected function _CMPP_QUERY_RESP($pdu, $requestd){
		$format = "a8Time/CQuery_Type/a10Query_Code/NMT_TLMsg/NMT_Tlusr/NMT_Scs/NMT_WT/NMT_FL/NMO_Scs/NMO_WT/NMO_FL";
		$data = unpack($format, $pdu);
	
		$this->_log("CMPP_QUERY_RESP PDU: " . self::pduord($pdu), ADC_LOG_DEBUG);
		$this->_log("CMPP_QUERY_RESP SPEC: " . $format, ADC_LOG_DEBUG);
		$this->_log("CMPP_QUERY_RESP DATA: " . print_r($data, true), ADC_LOG_DEBUG);
		
		if(empty($data)){
			return $this->_throw_exception(
					  "Empty CMPP_QUERY_RESP"
					, ADC_ERR_RESPONSE_EMPTY
					, $data
					, __FILE__, __LINE__
			);
		}
	
		return $data;
	}
	
	/*
	 This method sends out one SMS message.
	Parameters:
	$to	: destination address.
	$text	: text of message to send.
	$unicode: Optional. Indicates if sending text in unicode format.
	$text_encoding: Optional. encoding of text
	Returns:
	true if messages sent successfull, otherwise false.
	Example:
	$cmpp->Send("31649072766", "This is an SMPP Test message.");
	$cmpp->Send("31648072766", "صباحالخير", true);
	*/
	
	/**
	 * 发送短消息
	 * 
	 * Example:
	 *	$cmpp->Send("31649072766", "This is an SMPP Test message.");
	 *	$cmpp->Send("31648072766", "صباحالخير", true, 'HTML-ENTITIES')
		
	 * @param mixed $to 手机号码，传单个电话号码或者数组
	 * @param stirng $text 短消息内容，如果超长会自动切分短消息变成多条发送
	 * @param bool $unicode 是否UCS2编码方式发送，默认是，注意中文必须以UCS2编码发送
	 * @param string $text_encoding 短消息内容的编码，默认UTF-8，可选值参考函数：mb_convert_encoding()
	 * @return boolean 发送成功返回true，失败返回false
	 */
	public function Send($to, $text, $unicode = true, $text_encoding='UTF-8'){
		if(is_array($to)){
			$Dest_terminal_Id = '';
			$DestUsr_tl = count($to);
			foreach($to as $dest){
				$Dest_terminal_Id .= pack('a21', $dest);
			}
		}else{
			$Dest_terminal_Id = $to;
			$DestUsr_tl = 1;
		}
		
		$Msg_Content = '';
		
		$data = array();
		$data['Msg_Id'] = 0;
		$data['Pk_total'] = 1;
		$data['Pk_number'] = 1;
		
		// 是否要求返回状态确认报告
		// 0：不需要    
		// 1：需要   
		// 2：产生SMC话单（该类型短信仅供网关计费使用，不发送给目的终端) 
		$data['Registered_Delivery'] = $DestUsr_tl>1?0:0;
		
		// 信息级别
		// 0：普通优先级（缺省值）
        // 1：高优先级
        // >1：保留
		$data['Msg_level'] = 0;
		
		// 业务类型
		$data['Service_Id'] = $this->Config->AdcServiceId;
		
		//计费用户类型字段
		//0：对目的终端MSISDN计费；
		//1：对源终端MSISDN计费；
		//2：对SP计费;
		//3：表示本字段无效，对谁计费参见Fee_terminal_Id字段
		$data['Fee_UserType'] = 0;
		
		// 被计费用户的号码（如本字节填空，则表示本字段无效，对谁计费参见Fee_UserType 字段，本字段与Fee_UserType字段互斥）
		$data['Fee_terminal_Id'] = '';
		
		// GSM协议类型
		$data['TP_pId'] = 0;
		$data['TP_udhi'] = 0;
		
		// 信息格式
        // 0：ASCII串
        // 3：短信写卡操作
        // 4：二进制信息
        // 8：UCS2编码
        // 15：含GB汉字  。。。。。。
        $data['Msg_Fmt'] = 0;
        
        // 信息内容来源(SP_Id)
        $data['Msg_src'] = $this->Config->AdcAspId;
        
        // 资费类别
		//01：对“计费用户号码”免费
		//02：对“计费用户号码”按条计信息费
		//03：对“计费用户号码”按包月收取信息费
		//04：对“计费用户号码”的信息费封顶
		//05：对“计费用户号码”的收费是由SP实现
		$data['FeeType'] = '03';
		
		// 资费代码（以分为单位）
		$data['FeeCode'] = '';
		
		// 存活有效期，格式遵循SMPP3.3 协议
		$data['ValId_Time'] = '';
		
		// 定时发送时间，格式遵循SMPP3.3 协议
		$data['At_Time'] = '';
		
		// 源号码
		//SP 的服务代码或前缀为服务代码的长号码, 
		//网关将该号码完整的填到SMPP协议Submit_SM 消息相应的source_addr字段，
		//该号码最终在用户手机上显示为短消息的主叫号码
		$data['Src_Id'] = $this->Config->AdcAspCode;
		
		// 接收信息的用户数量(小于100 个用户)
		$data['DestUsr_tl'] = $DestUsr_tl;
		
		if($data['DestUsr_tl']>100){
			return $this->_throw_exception(
					"Over max DestUsr_tl<100"
					, SCRIPT_ERR_CONFIG
					, $data
					, __FILE__, __LINE__
			);
		}
		
		// 接收短信的MSISDN 号码
		$data['Dest_terminal_Id'] = $Dest_terminal_Id;
		
		// 信息长度(Msg_Fmt 值为0 时：<160 个字节；其它<=140 个字节)
		$data['Msg_Length'] = strlen($Msg_Content);
		
		/*
		$max_msg_len = $data['Msg_Fmt']==0?160:140;
		if($data['Msg_Length']>$max_msg_len){
			return $this->_throw_exception(
					"Over max Msg_Length<{$max_msg_len}"
					, SCRIPT_ERR_CONFIG
					, $data
					, __FILE__, __LINE__
			);
		}
		unset($max_msg_len);*/
		
		// 信息内容
		$data['Msg_Content'] = $Msg_Content;
		
		$data['Reserve'] = '';
		
		/**
		 * 消息转码分隔
		 */
		if ($unicode) {
			if(strcasecmp("UCS-2BE", $text_encoding)){
				$unicode_text = mb_convert_encoding($text, "UCS-2BE", $text_encoding); /* UCS-2BE */
			}else{
				$unicode_text = $text;
			}
			$multi_texts = $this->_split_message_unicode($unicode_text);
			unset($unicode_text);
			
			$data['Msg_Fmt'] = 8;
		}
		else {
			$multi_texts = $this->_split_message($text);
		}
		
		if (count($multi_texts) > 1) {
			$data['TP_udhi'] = 1;
			$data['Pk_total'] = count($multi_texts);
		}
		
		$result = true;
		
		reset($multi_texts);
		while (list($pos, $part) = each($multi_texts)) {
			$Msg_Content = $part;
			$data['Pk_number'] = $pos+1;
			$data['Msg_Content'] = $Msg_Content;
			$data['Msg_Length'] = strlen($Msg_Content);
			
			$status = $this->_CMPP_SUBMIT($data);
			if (false===$status) {
				$this->_log("CMPP server returned error $status.", ADC_LOG_ERR);
				$result = false;
			}
		}
		
		return $result;
	}
	
	/**
	 * PDU数据包转化ASCII数字
	 * @param string $pdu
	 * @return string
	 */
	public static function pduord($pdu){
		$ord_pdu = '';
		for ($i = 0; $i < strlen($pdu); $i++) {
			$ord_pdu .= ord($pdu[$i]) . ' ';
		}
		
		if($ord_pdu){
			$ord_pdu = substr($ord_pdu, 0, -1);
		}
		
		return $ord_pdu;
	}
	
	/**
	 * PDU数据包转为字符串（不可见字符转为ASCII数字）
	 * @param string $pdu
	 * @return string
	 */
	public static function pdustr($pdu){
		$str_pdu = '';
		for ($i = 0; $i < strlen($pdu); $i++) {
			$n = ord($pdu[$i]);
			if($n<=32 || $n>=127){
				$str_pdu .= '('. $n . ')';
			}else{
				$str_pdu .= $pdu[$i];
			}
			$str_pdu .= ' ';
		}
	
		if($str_pdu){
			$str_pdu = substr($str_pdu, 0, -1);
		}
	
		return $str_pdu;
	}
	
	protected function _split_message($text)
	{
		$this->_log("In split_message.", ADC_LOG_DEBUG);
		$max_len = 153;
		$res = array();
		if (strlen($text) <= 160) {
			$this->_log("One message: " . strlen($text), ADC_LOG_DEBUG);
			$res[] = $text;
			return $res;
		}
		$pos = 0;
		$msg_sequence = $this->_message_sequence++;
		$num_messages = ceil(strlen($text) / $max_len);
		$part_no = 1;
		while ($pos < strlen($text)) {
			$ttext = substr($text, $pos, $max_len);
			$pos += strlen($ttext);
			$udh = pack("cccccc", 5, 0, 3, $msg_sequence, $num_messages, $part_no);
			$part_no++;
			$res[] = $udh . $ttext;
			$this->_log("Split: UDH = " . self::pduord($udh), ADC_LOG_DEBUG);
			$this->_log("Split: $ttext.", ADC_LOG_DEBUG);
		}
		return $res;
	}
	
	protected function _split_message_unicode($text)
	{
		$this->_log("In split_message.", ADC_LOG_DEBUG);
		$max_len = 134;
		$res = array();
		if (mb_strlen($text) <= 140) {
			$this->debug("One message: " . mb_strlen($text), ADC_LOG_DEBUG);
			$res[] = $text;
			return $res;
		}
		$pos = 0;
		$msg_sequence = $this->_message_sequence++;
		$num_messages = ceil(mb_strlen($text) / $max_len);
		$part_no = 1;
		while ($pos < mb_strlen($text)) {
			$ttext = mb_substr($text, $pos, $max_len);
			$pos += mb_strlen($ttext);
			$udh = pack("cccccc", 5, 0, 3, $msg_sequence, $num_messages, $part_no);
			$part_no++;
			$res[] = $udh . $ttext;
			$this->_log("Split: UDH = " . self::pduord($udh), ADC_LOG_DEBUG);
			$this->_log("Split: $ttext.", ADC_LOG_DEBUG);
		}
		return $res;
	}
}


// $cmpp = new CMPPSubmit($argv[1],$argv[2]);
// $cmpp->createSocket();
// $cmpp->cmppConnect();
// $cmpp->cmppSubmit();

$cmpp = new AdcCmpp;
$smpp->Start("121.199.15.87", 7890, "7890", "992174", "shyx11");
$cmpp->Send("31649072766", "This is an SMPP Test message.");
$cmpp->Send("31648072766", "صباحالخير", true, 'HTML-ENTITIES');