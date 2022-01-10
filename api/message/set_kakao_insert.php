<?
	include "../../inc/common.php";
	include "../../inc/utility.php";
	include "../../inc/kakaoTalk.php";

	/*
	* 링크전송 > 등록
	* parameter:
		domain:  도메인
		memId:   회원ID
		memName: 회원명
		hpNo:    휴대폰번호
		fee:     휴대폰신청-마진율
		coupon:  휴대폰신청-쿠폰금액
		dueDate: 유효기간
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$mode       = $input_data->{'mode'};
	$domain     = $input_data->{'domain'};
	$memId      = $input_data->{'memId'};
	$memName    = $input_data->{'memName'};
	$hpNo       = $input_data->{'hpNo'};
	$fee        = $input_data->{'fee'};
	$coupon     = $input_data->{'coupon'};
	$dueDate    = $input_data->{'dueDate'};

	if ($mode == "insert") {
		// 그룹코드
		$groupCode = getDomainGroupCode($_SERVER['HTTP_REFERER']);

		// 링크정보 등록
		$sql = "INSERT INTO link_send (domain, memId, memName, fee, coupon, dueDate, wdate)
							   VALUES ('$domain', '$memId', '$memName', '$fee', '$coupon', '$dueDate', now())";
		$connect->query($sql);

		// 등록된 신청서의 일련번호를 구한다.
		$sql = "SELECT idx FROM link_send WHERE memName = '$memName' ORDER BY idx DESC LIMIT 1";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);
			$linkIdx = $row->idx;

			// 알림톡 전송
			$messageCode = $groupCode . "_hp_coupon_10_01";
			$hpNo = preg_replace('/\D+/', '', $hpNo);
			$receiptInfo = array(
				"memName"     => $memName,
				"idx"         => $linkIdx,
				"receiptHpNo" => $hpNo,
			);
			sendTalk($messageCode, $receiptInfo);

			$result_status = "0";
			$result_message = "'알림톡'이 전송되었습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "알림톡 전송오류가 발생하였습니다.";
		}

	} else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "존재하지 않는 링크입니다.";
	}

	$response = array(
		'result'     => $result_status,
		'message'    => $result_message,
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>