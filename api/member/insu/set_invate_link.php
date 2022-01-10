<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 다이렉트보험 > 고객에 링크 전송
	* parameter
		memId:     회원아이디
		memName:   회원이름
		custName:  고객이름
		custHpNo:  고객 휴대폰번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId      = $input_data->{'memId'};
	$memName    = $input_data->{'memName'};
	$custName   = $input_data->{'custName'};
	$custHpNo   = $input_data->{'custHpNo'};

	//$memId = "a27233377";
	//$custName = "최남희";
	//$custHpNo = "010-2723-3377";

	// 고객정보 등록
    $sql = "INSERT INTO insu_request (memId, memName, custName, hpNo)
	                          VALUES ('$memId', '$memName', '$custName', '$hpNo')";
	$connect->query($sql);

	// 등록 일련번호
	$sql = "SELECT idx FROM insu_request WHERE memId = '$memId' and custName = '$custName' ORDER BY idx DESC LIMIT 1";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$idx = $row->idx;

		// 알림톡 전송


		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "링크를 전송하였습니다.";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "오류가 발생하였습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
		'idx'     => $idx,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
