<?
	// *********************************************************************************************************************************
	// *                                                다이렉트보험 > 차봇 > 세션ID 취득                                                  *
	// *********************************************************************************************************************************
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/chabot.php";

	/* *************************************** Session ID 취득 API 시작 ********************************************************* */
	$body = Array(
		"coCode" => $coCode, 
		"mode"   => "service_init", 
	);

	// Session Id 취득 함수 호출 /inc/chabot.php
	$response = serviceInit($body);
	$response = json_decode($response, true);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>