<?
	// *********************************************************************************************************************************
	// *                                                다이렉트보험 > 차봇 > 본인인증                                                    *
	// *********************************************************************************************************************************
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/chabot.php";

	/*
	* parameter ==> sessionId: 세션ID
	* parameter ==> confirmNo: 인증번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$sessionId  = $input_data->{'sessionId'};
	$confirmNo  = $input_data->{'confirmNo'};

//$sessionId = "7343bfb8-64e8-411f-a21b-ceb37d05cd6d";
//$confirmNo = "774615";

	/* *************************************** Session ID 취득 API 시작 ********************************************************* */
	$body = Array(
		"coCode"      => $coCode, 
		"mode"        => "auth_ok", 
		"session_id"  => $sessionId, 
		"auth_number" => $confirmNo, 
	);

	// 인증번호확인 호출 /inc/chabot.php
	$response = confirmResult($body);
	$response = json_decode($response, true);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>