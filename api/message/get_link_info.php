<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 링크전송 > 등록
	* parameter:
		idx:  링크 일련번호
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$idx  = $input_data->{'idx'};

	// **************************************** 휴대폰 기종 정보 *******************************************************
    $sql = "SELECT domain, memId, fee, coupon, dueDate FROM link_send WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$domain  = $row->domain;
		$memId   = $row->memId;
		$fee     = $row->fee;
		$coupon  = $row->coupon;
		$dueDate = $row->dueDate;

		$data = array(
			'domain'  => $domain,
			'memId'   => $memId,
			'fee'     => $fee,
			'coupon'  => $coupon,
			'dueDate' => $dueDate,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "정상.";

	} else {
		$data = array();

		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "존재하지 않는 링크입니다.";
	}

	// 최종 결과
	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
		'data'    => $data,
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>