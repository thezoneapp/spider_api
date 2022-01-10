<?
	// *********************************************************************************************************************************
	// *                                                다이렉트보험 > 차봇 > 인증번호전송                                                 *
	// *********************************************************************************************************************************
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/chabot.php";

	/*
	* parameter ==> sessionId:      세션ID
	* parameter ==> custName:       고객명
	* parameter ==> hpNo:           고객 연락처
	* parameter ==> telecom:        통신사(SKT - 01, KT - 02, LGU+ - 03, SKT알뜰 - 04, KT알뜰 - 05, LGU+알뜰 - 06)
	* parameter ==> registNo:       주민번호("-"로 구분)
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$sessionId  = $input_data->{'sessionId'};
	$custName   = $input_data->{'custName'};
	$hpNo       = $input_data->{'hpNo'};
	$telecom    = $input_data->{'telecom'};
	$registNo   = $input_data->{'registNo'};

//$sessionId = "90da5505-5f46-45a0-be3a-a1ec3b81b667";
//$custName = "이해영";
//$hpNo = "010-5564-5136";
//$telecom = "02";
//$registNo = "960103-2796517";

	$hpNo = str_replace("-", "", $hpNo);
	$arrRegistNo = explode("-", $registNo);

	/* *************************************** 인증번호 전송 API 시작 ********************************************************* */
	$body = Array(
		"coCode"         => $coCode, 
		"mode"           => "send_auth", 
		"customer_name"  => $custName, 
		"phone"          => $hpNo, 
		"phone_company"  => $telecom, 
		"ssn_prefix"     => $arrRegistNo[0], 
		"ssn_suffix"     => $arrRegistNo[1], 
		"session_id"     => $sessionId 
	);

	// 인증서전송 함수 호출 /inc/chabot.php
	$response = sendCertifyNo($body);
	$response = json_decode($response);

	//print_r($response);
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>