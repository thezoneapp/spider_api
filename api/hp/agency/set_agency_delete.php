<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자페이지 > 휴대폰신청 > 업체관리 > 목록 > 삭제
	* parameter ==> idx: 일련번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$arrIdx = $input_data->{'idx'};

	$idx = "";

	for ($i = 0; count($arrIdx) > $i; $i++) {
		if ($idx != "") $idx .= ",";

		$idx .= "'" . $arrIdx[$i] . "'";
	}

	$sql = "DELETE FROM hp_agency WHERE idx IN ($idx)";
	$result = $connect->query($sql);

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