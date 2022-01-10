<?php
//*************************************************************************************************************
//**********                               알림톡                                     **************************
//*************************************************************************************************************
$talkKey = "da21876ce059358738c709764922b861551b45d4";
$userCode = "spiderfla";
$deptcode = "WD-0EH-M4";
$sendTel  = "01051907770";

//**********  내용 템플릿 치환   **************************
function contentReplace($receiptInfo, $content) {
    $content = str_replace("{MEM_ID}",         $receiptInfo[memId],         $content);
    $content = str_replace("{MEM_PW}",         $receiptInfo[memPw],         $content);
    $content = str_replace("{MEM_NAME}",       $receiptInfo[memName],       $content);
    $content = str_replace("{MEM_HpNo}",       $receiptInfo[memHpNo],       $content);
    $content = str_replace("{MEM_HPNO}",       $receiptInfo[memHpNo],       $content);  // 회원휴대폰번호
    $content = str_replace("{JOIN_AMOUNT}",    $receiptInfo[joinAmount],    $content);
    $content = str_replace("{CMS_MESSAGE}",    $receiptInfo[cmsMessage],    $content);
    $content = str_replace("{CMS_AMOUNT}",     $receiptInfo[cmsAmount],     $content);
    $content = str_replace("{CUST_NAME}",      $receiptInfo[custName],      $content); // 고객명
    $content = str_replace("{CUST_HpNo}",      $receiptInfo[custHpNo],      $content); // 고객휴대폰번호
    $content = str_replace("{CUST_HPNO}",      $receiptInfo[custHpNo],      $content); // 고객휴대폰번호
    $content = str_replace("{CERTIFY_NO}",     $receiptInfo[certifyNo],     $content); // 인증번호
    $content = str_replace("{MODEL_NAME}",     $receiptInfo[modelName],     $content); // 휴대폰 모델
    $content = str_replace("{MODEL_COLOR}",    $receiptInfo[colorName],     $content); // 휴대폰 색상
    $content = str_replace("{USE_TELECOM}",    $receiptInfo[useTelecom],    $content); // 기존통신사
    $content = str_replace("{CHANGE_TELECOM}", $receiptInfo[changeTelecom], $content); // 이동통신사
    $content = str_replace("{CHARGE_NAME}",    $receiptInfo[chargeName],    $content); // 요금제
    $content = str_replace("{REQUEST_MEMO}",   $receiptInfo[requestMemo],   $content); // 신청자메모
    $content = str_replace("{IDX}",            $receiptInfo[idx],           $content); // 해당일련번호
    $content = str_replace("{COMPANY_CODE}",   $receiptInfo[companyCode],   $content); // 택배업체명
    $content = str_replace("{COMPANY_NAME}",   $receiptInfo[companyName],   $content); // 택배업체코드
    $content = str_replace("{INVOICE_NO}",     $receiptInfo[invoiceNo],     $content); // 송장번호
    $content = str_replace("{BIT_LY_KEY}",     $receiptInfo[bitLyKey],      $content); // https://bit.ly key값

    return $content;
}

//**********  버튼 템플릿 치환   **************************
function buttonReplace($receiptInfo, $buttonInfo) {
	$buttonName = $buttonInfo['buttonName'];
	$mobileUrl  = $buttonInfo['mobileUrl'];
	$pcUrl      = $buttonInfo['pcUrl'];

	// 모바일
    $mobileUrl = str_replace("{MEM_ID}",       $receiptInfo[memId],         $mobileUrl); // 회원 아이디
    $mobileUrl = str_replace("{MEM_NAME}",     $receiptInfo[memName],       $mobileUrl); // 회원명
    $mobileUrl = str_replace("{RECOMMEND_ID}", $receiptInfo[recommendId],   $mobileUrl); // 추천인 아이디
    $mobileUrl = str_replace("{COMPANY_CODE}", $receiptInfo[companyCode],   $mobileUrl); // 택배업체코드
    $mobileUrl = str_replace("{INVOICE_NO}",   $receiptInfo[invoiceNo],     $mobileUrl); // 송장번호
    $mobileUrl = str_replace("{IDX}",          $receiptInfo[idx],           $mobileUrl); // 일련번호

	// PC
    $pcUrl = str_replace("{MEM_ID}",       $receiptInfo[memId],         $pcUrl); // 회원 아이디
    $pcUrl = str_replace("{MEM_NAME}",     $receiptInfo[memName],       $pcUrl); // 회원명
    $pcUrl = str_replace("{RECOMMEND_ID}", $receiptInfo[recommendId],   $pcUrl); // 추천인 아이디
	$pcUrl = str_replace("{COMPANY_CODE}", $receiptInfo[companyCode],   $pcUrl); // 택배업체코드
    $pcUrl = str_replace("{INVOICE_NO}",   $receiptInfo[invoiceNo],     $pcUrl); // 송장번호
    $pcUrl = str_replace("{IDX}",          $receiptInfo[idx],           $pcUrl); // 일련번호

	$talkButton = array();

    $dataInfo = array(
        "button_type" => "WL",
        "button_name" => $buttonName,
        "button_url"  => $mobileUrl,
        "button_url2" => $pcUrl,
    );
	array_push($talkButton, $dataInfo);

    return $talkButton;
}

//**********  알림톡 생성   **************************
function sendTalk($templateCode, $receiptInfo) {
    global $connect;

    // 알림톡 템플릿 가져오기
    $sql = "SELECT content, buttonYn, buttonName, mobileUrl, pcUrl FROM sms_message WHERE code = '$templateCode'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$content    = $row->content;
	$buttonYn   = $row->buttonYn;
	$buttonName = $row->buttonName;
	$mobileUrl  = $row->mobileUrl;
	$pcUrl      = $row->pcUrl;

    $talkMessage = contentReplace($receiptInfo, $content);

	if ($buttonYn != "Y") $talkButton = array();
	else {
		$buttonInfo = array(
			"buttonName" => $buttonName,
			"mobileUrl"  => $mobileUrl,
			"pcUrl"      => $pcUrl,
		);

		$talkButton = buttonReplace($receiptInfo, $buttonInfo);
	}

    // 알림톡 전송
	postTALK($templateCode, $talkMessage, $talkButton, $receiptInfo[receiptHpNo]);
}

/***************************************************************************************************
 *                               알림톡 전송 실행                                                    *
 ***************************************************************************************************/
function postTalk($templateCode, $talkMessage, $talkButton, $receTel) {
    global $talkKey, $userCode, $deptcode, $sendTel;

    $receTel = str_replace("-", "", $receTel);
    $receTel = "82" . floatval($receTel);

	$talkMessages = array();

    $message_info = array(
        "message_id"    => "",
        "to"            => $receTel,
        "text"          => $talkMessage,
        "from"          => $sendTel,
        "template_code" => $templateCode,
        "re_send"       => "Y",
        "buttons"       => $talkButton
    );
	array_push($talkMessages, $message_info);

    $postData = array(
        "usercode"     => $userCode,
        "deptcode"     => $deptcode,
        "yellowid_key" => $talkKey,
        "messages"     => $talkMessages
    );

    $jsonData = json_encode($postData);
    $ch = curl_init('https://rest.surem.com/alimtalk/v2/json');

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
        'Content-Length: '.strlen($jsonData)
	));
    curl_setopt($ch, CURLOPT_URL, 'https://rest.surem.com/alimtalk/v2/json');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POST, 1); 
 
    $response = curl_exec($ch);

    if($response === false){
        die(curl_error($ch));
    }
}
?>