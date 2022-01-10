<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 내정보 > 현재 상태
	* parameter ==> userId: 회원아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId     = $input_data->{'userId'};
	
	//$userId = "a27233377";

	// 회원정보 > 구독료 납부상태
	$sql = "SELECT clearStatus FROM member WHERE memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$clearStatus = $row->clearStatus;

		if ($clearStatus == "0") $cmsStatus = "정상";
		else $cmsStatus = $clearStatus . "회미납";

		// 초기가맹금, 월구독료, 후원수익포인트
		$joinPoint = 0;
		$subsPoint = 0;
		$invitePoint = 0;
		$rentalPoint = 0;
		$insuRate = 0;
		$sql = "SELECT code, content FROM setting WHERE assort = 'V' ORDER by code asc";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			while($row2 = mysqli_fetch_array($result2)) {
				if ($row2[code] == "subsA") $joinPoint = $row2[content];
				else if ($row2[code] == "payA") $subsPoint = $row2[content];
				else if ($row2[code] == "commiS") $invitePoint = $row2[content];
				else if ($row2[code] == "commiR") $rentalPoint = $row2[content];
				else if ($row2[code] == "insuRate") $insuRate = $row2[content];
			}
		}

		$data = array(
			'cmsStatus'   => $cmsStatus,
			'joinPoint'   => number_format($joinPoint),
			'subsPoint'   => number_format($subsPoint),
			'invitePoint' => number_format($invitePoint),
			'rentalPoint' => number_format($rentalPoint),
			'insuRate'    => $insuRate
		);

		$result_status  = "0";
		$result_message = "정상";

	} else {
		$data = array();
		$result_status = "1";
		$result_message = "등록되지 않은 회원입니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
		'data'    => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>