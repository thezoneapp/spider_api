<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 이벤트 > 휴대폰신청 > 신청목록 > 일괄저장
	* parameter
		data: 데이타 배열
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$arrData = $data_back->{'data'};

	for ($i = 0; count($arrData) > $i; $i++) {
		$data = $arrData[$i];

		$idx           = $data->idx;
		$adminMemo     = $data->adminMemo;
		$requestStatus = $data->requestStatus;

		$requestStatus = $requestStatus->{'code'};

		$sql = "UPDATE hp_event_request SET adminMemo = '$adminMemo', 
								            requestStatus = '$requestStatus'  
						WHERE idx = '$idx'";
		$connect->query($sql);
	}

	// 성공 결과를 반환합니다.
	$result_status = "0";
	$result_message = "저장하였습니다.";

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>