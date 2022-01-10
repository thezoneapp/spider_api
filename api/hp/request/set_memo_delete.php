<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 휴대폰신청 > 신청목록 > 관리자메모 > 삭제
	* parameter
		idx: 메모 일련번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

	// 메모 삭제
	$sql = "DELETE FROM hp_request_memo WHERE idx = '$idx'";
	$result = $connect->query($sql);

	if ($result == "1") {
		$result_status = "0";
		$result_message = "'삭제'되었습니다.";

	} else {
		$result_status = "1";
		$result_message = "'오류'가 밸생되었습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>