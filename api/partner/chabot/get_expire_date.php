<?
	// *********************************************************************************************************************************
	// *                                                다이렉트보험 > 차봇 > 만기일자조회                                                 *
	// *********************************************************************************************************************************
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/chabot.php";

	/*
	* parameter
		sessionId: 세션ID
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$sessionId  = $input_data->{'sessionId'};

	//$sessionId = "2d746a85-174d-43f2-b235-e63f5837c608";

	/* *************************************** Session ID 취득 API 시작 ********************************************************* */
	$body = Array(
		"coCode"      => $coCode, 
		"mode"        => "pre_insure", 
		"session_id"  => $sessionId, 
	);

	// 만기일자조회 호출 /inc/chabot.php
	$response = expireDate($body);
	$response = json_decode($response, true);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>