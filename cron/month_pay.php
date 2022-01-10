<?
    /***********************************************************************************************************************
	* CMS 출금신청상태 자동 업데이트
	* 1. 출금신청목록 > 출금상태 > 출금대기 검색
	* 2. 출금완료 > 수수료목록에 추가
	***********************************************************************************************************************/

	$db_host = "localhost";
	$db_user = "spiderfla";
	$db_pass = "dlfvkf#$12";
	$db_name = "spiderfla";

	$connect = @mysqli_connect($db_host, $db_user, $db_pass, $db_name) or die("Failed to connect to MySQL: " . mysqli_error()); 

	$managerId = "nbbang18";
	$CUST_ID   = "nbbang18";
	$SW_KEY    = "sldcgtgYKYQ03R2W";
	$CUST_KEY  = "7tPKQGphGwFLD5TJ";

	//*********************************************************************
	//**********          암호화/복호화            **************************
	//*********************************************************************
	$key = "eoqkr!#18@$";
	$key = substr(hash('sha256', $key), 0, 32);
	$key = pack('H*', hash('sha256', $key));

	// AES128/ECB/PKCS5Padding 암호화
	function aes128encrypt($data) {
		global $key;

		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

		mcrypt_generic_init($td, $key, $iv);

		$result = mcrypt_generic($td, pkcs5_pad($data, $size));

		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return base64_encode($result);
	}

	// AES128/ECB/PKCS5Padding 복호화
	function aes128decrypt($data) {
		global $key;
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

		mcrypt_generic_init($td, $key, $iv);

		$decrypted_text = mdecrypt_generic($td, base64_decode($data));

		$rt = $decrypted_text;

		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return pkcs5_unpad($rt);
	}

	// PKCS5Padding 인코딩
	function pkcs5_pad($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);

		return $text . str_repeat(chr($pad), $pad);
	}

	// PKCS5Padding 디코딩
	function pkcs5_unpad($text) { 
		$pad = ord($text{strlen($text)-1}); 

		if ($pad > strlen($text)) return false; 

		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false; 

		return substr($text, 0, -1 * $pad); 
	}

	// 디코딩 결과값 리턴
	function aes_decode($data) {
		if (empty($data)) $data = "";
		else $data = aes128decrypt($data);

		return $data;
	}

	//*************************************************************************************************************
	//**********                               알림톡                                     **************************
	//*************************************************************************************************************

	//**********  알림톡 생성   **************************
	function sendTalk($templateCode, $receiptInfo) {
		global $connect;

		// 알림톡 템플릿 가져오기
		$sql = "SELECT content FROM sms_message WHERE code = '$templateCode'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		$talkMessage = infoReplace($receiptInfo, $row->content);

		// 알림톡 전송
		postTALK($receiptInfo[receiptHpNo], $talkMessage, $templateCode);
	}

	//**********  템플릿 치환   **************************
	function infoReplace($receiptInfo, $content) {
		$content = str_replace("{MEM_ID}",      $receiptInfo[memId],      $content);
		$content = str_replace("{MEM_NAME}",    $receiptInfo[memName],    $content);
		$content = str_replace("{MEM_HpNo}",    $receiptInfo[memHpNo],    $content);
		$content = str_replace("{CMS_MESSAGE}", $receiptInfo[cmsMessage], $content);
		$content = str_replace("{CMS_AMOUNT}",  $receiptInfo[cmsAmount],  $content);

		return $content;
	}

	/***************************************************************************************************
	 *                               알림톡 전송 실행                                                    *
	 ***************************************************************************************************/
	function postTALK($receTel, $talkMessage, $templateCode) {
		$talkKey = "da21876ce059358738c709764922b861551b45d4";
		$userCode = "spiderfla";
		$deptcode = "WD-0EH-M4";

		$sendTel = "01051907770 ";
		$receTel = str_replace("-", "", $receTel);
		$receTel = "82" . floatval($receTel);

		$talkMessages = array(
			"message_id"    => "",
			"to"            => $receTel,
			"text"          => $talkMessage,
			"from"          => $sendTel,
			"template_code" => $templateCode,
			"re_send"       => "Y"
		);

		$arrTalkMessages = array($talkMessages);

		$curlPostData = array(
			"usercode"     => $userCode,
			"deptcode"     => $deptcode,
			"yellowid_key" => $talkKey,
			"messages"     => $arrTalkMessages
		);
	 
		$jsonData = json_encode($curlPostData);
		$ch = curl_init('https://api.surem.com/alimtalk/v1/json');

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: '.strlen($jsonData)
		));
		curl_setopt($ch, CURLOPT_URL, 'https://api.surem.com/alimtalk/v1/json');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POST, 1); 
	 
		// Send the request
		$talkResponse = curl_exec($ch);

		if($talkResponse === FALSE){
			die(curl_error($ch));
		}
	}

	/**************************** 최근 한달이내에 등록된 출금대기 목록 **********************************************************/
	$adminId = "auto";
	$adminName = "자동";
	$clearDate = date("Y-m-d");
	$sql = "SELECT cp.idx, cp.sponsId, cp.sponsName, cp.memId, cp.memName, m.hpNo, cp.memAssort, cp.paymentKind, cp.payMonth, cp.payAmount, cp.commiAmount, cp.transactionId
			FROM cms_pay cp
				 INNER JOIN member m ON cp.memId = m.memId
			WHERE cp.requestStatus = '0' and cp.payStatus in ('0','1') AND cp.wdate >= date_sub(now(),INTERVAL 30 DAY)";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

			$idx           = $row[idx];
			$sponsId       = $row[sponsId];
			$sponsName     = $row[sponsName];
			$memId         = $row[memId];
			$memName       = $row[memName];
			$hpNo          = $row[hpNo];
			$memAssort     = $row[memAssort];
			$payMonth      = $row[payMonth];
			$paymentKind   = $row[paymentKind];
			$payAmount     = $row[payAmount];    // CMS월납부금액
			$commiAmount   = $row[commiAmount]; // 유치수수료
			$transactionId = $row[transactionId];

			if ($paymentKind == "CMS") $urlKind = "cms";
			else if ($paymentKind == "CARD") $urlKind = "card";

			$url = "https://api.hyosungcms.co.kr/v1/payments/" . $urlKind . "/" . $transactionId;

			// header
			$header = Array(
				"Content-Type: application/json; charset=utf-8", 
				"Authorization: VAN $SW_KEY:$CUST_KEY"
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
			curl_setopt($ch, CURLOPT_ENCODING , "");
			curl_setopt($ch, CURLOPT_POST, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

			$response = curl_exec($ch);

			curl_close($ch);

			$response = json_decode($response, true);

			if ($response[error] != null) {
				$result_status = "1";
				$status_status = "1";
				$message = $response[error][message];
				$result_message = $response[error][message];

				// 출금신청상태 > 출금오류
				$sql = "UPDATE cms_pay set payStatus = '5', payMessage = '$message' WHERE idx = '$idx'";
				$connect->query($sql);

			} else {
				$result_status = "0";
				$payment = $response[payment];
				$status = $response[payment][status];
				$message = $response[payment][status];
				$errorMessage = $response[payment][result][message];
				$result_message = $response[payment][status];

				if ($status == "승인성공" || $status == "출금성공") {
					if ($memAssort == "M") {
						$commiAssort = "MA";
						$salesAssort = "PA";

					} else {
						$commiAssort = "MS";
						$salesAssort = "PS";
					}

					// 스폰서 > 이용료 납부방식
					$sql = "SELECT payType FROM member WHERE memId = '$sponsId'";
					$result2 = $connect->query($sql);
					$row2 = mysqli_fetch_object($result2);
					$payType = $row2->payType;

					// 스폰서 > 이용료 납부방식 > 구독료납부 > 후원수수료 등록
					if ($payType == "S") {
						$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, memAssort, assort, price, transactionId, wdate) 
												VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$memAssort', '$commiAssort', '$commiAmount', '$transactionId', now())";
						$connect->query($sql);
					}

					// 매출(CMS납부) 등록
					$sql = "INSERT INTO sales (sponsId, sponsName, memId, memName, assort, price, transactionId, wdate) 
										VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$salesAssort', '$payAmount', '$transactionId', now())";
					$connect->query($sql);

					// 출금신청상태 > 출금완료
					$sql = "UPDATE cms_pay set payStatus = '9' WHERE transactionId = '$transactionId'";
					$connect->query($sql);

				} else if ($message == "출금대기") {
					$result_status = "0";
					$sql = "UPDATE cms_pay set payStatus = '0', payMessage = '$message' WHERE transactionId = '$transactionId'";
					$connect->query($sql);

				} else if ($message == "출금중") {
					$result_status = "0";
					$sql = "UPDATE cms_pay set payStatus = '1', payMessage = '$message' WHERE transactionId = '$transactionId'";
					$connect->query($sql);

				} else {
					// 출금신청상태 > 출금오류
					$result_status = "1";
					$message = $errorMessage;
					$sql = "UPDATE cms_pay set payStatus = '5', payMessage = '$message' WHERE transactionId = '$transactionId'";
					$connect->query($sql);
				}
			}

			// 출금신청 로그등록
			$sql = "INSERT INTO cms_pay_log (memId, memName, payMonth, paymentKind, transactionId, payAmount, message, adminId, adminName, status, wdate)
									 VALUES ('$memId', '$memName', '$payMonth', '$paymentKind', '$transactionId', '$payAmount', '$message', '$adminId', '$adminName', '$result_status', now())";
			$connect->query($sql);

			// 구독 연체횟수를 알아본다.
			$sql = "SELECT ifnull(count(idx),0) AS delayCount 
					FROM cms_pay 
					WHERE memId = '$memId' AND (requestStatus = '1' or payStatus = '5' )";
			$result3 = $connect->query($sql);
			$row3 = mysqli_fetch_object($result3);
			$delayCount = $row3->delayCount;

			if ($delayCount == 0) {
				// 회원상태 = 정상, 구독료납부 = 정상으로 변경한다.
				$sql = "UPDATE member SET clearStatus = '0' WHERE memId = '$memId'";
				$connect->query($sql);

			} else {
				// 회원상태 = 보류, 구독료납부 = 연체로 변경한다.
				$sql = "UPDATE member SET clearStatus = '$delayCount' WHERE memId = '$memId'";
				$connect->query($sql);

				// 알림톡 전송
				$hpNo = preg_replace('/\D+/', '', $hpNo);
				$receiptInfo = array(
					"memName"     => $memName,
					"cmsMessage"  => $message,
					"cmsAmount"   => number_format($payAmount),
					"receiptHpNo" => $hpNo,
				);
				sendTalk("C_002_1", $receiptInfo);
			}
		}
	}

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);

	exit;
?>