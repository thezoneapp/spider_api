<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	//include "../../../inc/kakaoTalk.php";

	/*
	* 컨시어지 > 계약등록 >제휴상품대상조회
	* parameter 
		hpNo: 고객 휴대폰번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$hpNo       = $input_data->{'hpNo'};

	//$hpNo      = "010-6649-2082";
	//$birthday  = "990320";

	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);

	$sql = "SELECT idx, memId, memName, birthday, if (date_format(date_add(wdate, INTERVAL 30 DAY), '%Y-%m-%d') >= curdate(), '0', '1') AS dayCheck 
			FROM hp_request 
			WHERE hpNo = '$hpNo' 
			ORDER BY idx DESC 
			LIMIT 1";
	$result = $connect->query($sql); 

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->birthday != "") {
			$birthday = aes_decode($row->birthday);
			$birthday = substr($birthday, 0,2) . "-" . substr($birthday, 2,2) . "-" . substr($birthday, 4,2);
		}

		if ($row->dayCheck == "0") {
			$result_status = "0";
			$result_message = "제휴상품 이용이 가능합니다.";

		} else {
			$result_status = "2";
			$result_message = "휴대폰 개통후 30일 경과로 혜택없이 일반가입으로 진행됩니다.";
		}

		// 성공 결과를 반환합니다.
		$response = array(
			'result'    => $result_status,
			'message'   => $result_message,
			'memId'     => $row->memId,
			'memName'   => $row->memName,
			'birthday'  => $birthday,
		);

	} else {
		// 실패 결과를 반환합니다.
		$response = array(
			'result'    => "1",
			'message'   => "단품 또는 연납상품 신청"
		);
	}

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>