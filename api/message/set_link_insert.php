<?
	include "../../inc/common.php";

	/*
	* 링크전송 > 등록
	* parameter:
		domain:  도메인
		memId:   회원ID
		fee:     휴대폰신청-마진율
		coupon:  휴대폰신청-쿠폰금액
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$mode       = $input_data->{'mode'};
	$domain     = $input_data->{'domain'};
	$memId      = $input_data->{'memId'};
	$fee        = $input_data->{'fee'};
	$coupon     = $input_data->{'coupon'};
	$dueDate    = $input_data->{'dueDate'};

	if ($mode == "insert") {
		// 링크정보 등록
		$sql = "INSERT INTO link_send (domain, memId, fee, coupon, dueDate, wdate)
							   VALUES ('$domain', '$memId', '$fee', '$coupon', '$dueDate', now())";
		$connect->query($sql);

		// 등록된 신청서의 일련번호를 구한다.
		$sql = "SELECT idx FROM link_send WHERE memId = '$memId' ORDER BY idx DESC LIMIT 1";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);
			$linkIdx = $row->idx;

			$result_status = "0";
			$result_message = "'링크전송'이 등록되었습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "존재하지 않는 링크입니다.";
		}

	} else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "존재하지 않는 링크입니다.";
	}

	$response = array(
		'result'     => $result_status,
		'message'    => $result_message,
		'linkIdx'    => $linkIdx,
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>