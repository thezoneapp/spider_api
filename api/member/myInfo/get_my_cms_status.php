<?
	include "../../../inc/common.php";

	/*
	* 내정보 > CMS신청상태
	* parameter
		userId: 회원아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId = $input_data->{'userId'};

	//$userId = "a22223333";

	// 회원정보 검색
	$sql = "SELECT m.cmsStatus, c.changeDate, c.closeDate, DATE_FORMAT(NOW(), '%Y-%m-%d') as today, DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') AS nextDay
			FROM member m 
				 INNER JOIN cms c ON m.memId = c.memId 
			WHERE m.memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->cmsStatus == "1") {
			$cmsStatus = "1";
			$result_message = "신청중입니다.";
		} else if ($row->cmsStatus == "9" && ($row->today < $row->nextDay)) {
			$cmsStatus = "2";
			$lastDate = $row->changeDate;
			$result_message = "익월 1일에 변경 가능합니다.";
		} else {
			$cmsStatus = "9";
			$closeDate = $row->closeDate;
			$result_message = "변경가능합니다.";
		}

		$result_status = "0";

	} else {
		$cmsStatus = "";
		$result_status = "1";
		$result_message = "등록되어 있지 않는 회원입니다.";
	}

	$response = array(
		'cmsStatus'  => $cmsStatus,
		'lastDate'   => $lastDate,
		'closeDate'  => $closeDate,
		'result'     => $result_status,
		'message'    => $result_message,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>