<?php
//*********************************************************************
//**********          다이렉트보험 (차봇)      **************************
//*********************************************************************
$coCode = "c045cDhrc1cvcmNTU2FzZlViYm5PSGpjR2hxL2dLQWtHaHhVcmlPL3FNYz0%3D";

//*********************************************************************
//**********              회원 등록           **************************
//*********************************************************************
function memberRegist($body) {
	//$url = "https://dev-api.chabot.kr:9499/api/member/";    // 개발서버
	$url = "https://api.chabot.kr/api/member/"; // 운영서버

	// header
	$header = Array(
		"Content-Type: application/json; charset=utf-8", 
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($ch, CURLOPT_ENCODING , "");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); 

	$response = curl_exec($ch);

	curl_close($ch);

	$response = json_decode($response, true);
	//print_r( $response[data][dealerCode] );

	$insuId = $response[data][dealerCode];

	if ($response[responseCode] == "200") {
		$result_status = "0";
		$result_message = "'회원등록'이 완료되었습니다.";

	} else {
		$insuId         = $insuId;
		$result_status  = "1";
		$result_message = $response[message];
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
		'insuId'    => $insuId
    );

	return json_encode( $response );
}

//*********************************************************************
//**********             회원 탈퇴            **************************
//*********************************************************************
function memberOut($body) {
	//$url = "https://dev-api.chabot.kr:9499/api/member/";    // 개발서버
	$url = "https://api.chabot.kr/api/member/"; // 운영서버

	// header
	$header = Array(
		"Content-Type: application/json; charset=utf-8", 
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($ch, CURLOPT_ENCODING , "");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); 

	$response = curl_exec($ch);

	curl_close($ch);

	$response = json_decode($response, true);

	if ($response[responseCode] == "200") {
		$result_status = "0";
		$result_message = "'회원탈퇴'가 완료되었습니다.";

	} else {
		$result_status = "1";
		$result_message = $response[message];
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message
    );

	return json_encode( $response );
}

//*********************************************************************
//**********            신청서 등록           **************************
//*********************************************************************
function requestRegist($body) {
	//$url = "https://dev-api.chabot.kr:9499/api/tm_regist/";    // 개발서버
	$url = "https://api.chabot.kr/api/tm_regist/"; // 운영서버

	// header
	$header = Array(
		"Content-Type: application/json; charset=utf-8", 
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($ch, CURLOPT_ENCODING , "");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); 

	$response = curl_exec($ch);

	curl_close($ch);

	$response = json_decode($response, true);
	//print_r( $response );

	if ($response[status] == "200") {
		$seqNo = $response[sid];
		$result_status = "0";
		$result_message = "'상담신청'이 완료되었습니다.";

	} else {
		$seqNo = "";
		$result_status = "1";
		$result_message = $response[message];
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
		'seqNo'     => $seqNo,
    );

	return json_encode( $response );
}

//*********************************************************************
//**********            서비스 시작         **************************
//*********************************************************************
function serviceInit($body) {
	$url = "https://dev-api.chabot.kr:9499/partner_api/auth/";    // 개발서버
	//url = "https://api.chabot.kr/partner_api/auth/"; // 운영서버

	// header
	$header = Array(
		"Content-Type: application/json; charset=utf-8", 
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($ch, CURLOPT_ENCODING , "");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); 

	$response = curl_exec($ch);

	curl_close($ch);

	$response = json_decode($response, true);

	if ($response[status] == "200") {
		$sessionId = $response[data][session_id];
		$result_status = "0";
		$result_message = "정상";

	} else {
		$sessionId = "";
		$result_status = "1";
		$result_message = "에러";
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
		'sessionId' => $sessionId,
    );

	return json_encode( $response );
}

//*********************************************************************
//**********            인증번호 전송         **************************
//*********************************************************************
function sendCertifyNo($body) {
	$url = "https://dev-api.chabot.kr:9499/partner_api/auth/";    // 개발서버
	//$url = "https://api.chabot.kr/partner_api/auth/"; // 운영서버

	// header
	$header = Array(
		"Content-Type: application/json; charset=utf-8", 
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($ch, CURLOPT_ENCODING , "");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); 

	$response = curl_exec($ch);

	curl_close($ch);

	$response = json_decode($response, true);

	if ($response[status] == "200") {
		$result_status = "0";
		$result_message = "'인증번호'가 전송되었습니다.";

	} else {
		$result_status = "1";
		$result_message = $response[message];
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
    );

	return json_encode( $response );
}

//*********************************************************************
//**********            인증번호확인         **************************
//*********************************************************************
function confirmResult($body) {
	$url = "https://dev-api.chabot.kr:9499/partner_api/auth/";    // 개발서버
	//$url = "https://api.chabot.kr/partner_api/auth/"; // 운영서버

	// header
	$header = Array(
		"Content-Type: application/json; charset=utf-8", 
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($ch, CURLOPT_ENCODING , "");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); 

	$response = curl_exec($ch);

	curl_close($ch);

	$response = json_decode($response, true);

	if ($response[status] == "200") {
		$result_status = "0";
		$result_message = "'본인인증'이 완료되었습니다.";

	} else {
		$result_status = "1";
		$result_message = "'본인인증오류'가 발생하였습니다.";
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
    );

	return json_encode( $response );
}

//*********************************************************************
//**********            만기일자조회          **************************
//*********************************************************************
function expireDate($body) {
	$url = "https://dev-api.chabot.kr:9499/partner_api/pre_insure/";    // 개발서버
	//$url = "https://api.chabot.kr/partner_api/pre_insure/"; // 운영서버

	// header
	$header = Array(
		"Content-Type: application/json; charset=utf-8", 
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($ch, CURLOPT_ENCODING , "");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); 

	$response = curl_exec($ch);

	curl_close($ch);
print_r($response);
	$response = json_decode($response, true);

exit;
	if ($response[status] == "200") {
		$result_status = "0";
		$result_message = "정상";
		$data = $response[data];

	} else {
		$result_status = "1";
		$result_message = "오류가 발생하였습니다.";
		$data = array();
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
		'data'      => $data,
    );

	return json_encode( $response );
}
?>