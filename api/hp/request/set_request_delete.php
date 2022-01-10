<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 신청 정보 삭제
	* parameter ==> idx: 신청 일련번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$arrIdx = $input_data->{'idx'};

	$idx = "";

	for ($i = 0; count($arrIdx) > $i; $i++) {
		if ($idx != "") $idx .= ",";

		$idx .= "'" . $arrIdx[$i] . "'";
	}

	// 신청서정보
	$sql = "DELETE FROM hp_request WHERE idx IN ($idx)";
	$result = $connect->query($sql);

	// 할인정보
	$sql = "DELETE FROM hp_request_discount WHERE requestIdx IN ($idx)";
	$connect->query($sql);

	// 캐시백정보
	$sql = "DELETE FROM hp_cash_back WHERE requestIdx IN ($idx)";
	$connect->query($sql);

	// 배송지정보
	$sql = "DELETE FROM hp_delivery WHERE requestIdx IN ($idx)";
	$connect->query($sql);

	if ($result == true) {
		// 성공 결과를 반환합니다.
		$response = array(
			'result'    => "0",
			'message'   => "삭제하였습니다."
		);

	} else {
		// 실패 결과를 반환합니다.
		$response = array(
			'result'    => "1",
			'message'   => "삭제에 실패하였습니다."
		);
	}

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>