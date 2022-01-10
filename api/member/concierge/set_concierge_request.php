<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 컨시어지 > 계약등록
	* parameter 
		mode:           insert
		memId:          모집자ID
		memName:        모집자명
		registNo:       주민번호
		hpNo:           휴대폰번호
		postNum:        우편번호
		addr1:          기본 주소
		addr2:          나머지 주소
		email:          이메일
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$mode       = $input_data->{'mode'};
	$memId      = $input_data->{'memId'};
	$memName    = $input_data->{'memName'};
	$registNo   = $input_data->{'registNo'};
	$hpNo       = $input_data->{'hpNo'};
	$postNum    = $input_data->{'postNum'};
	$addr1      = $input_data->{'addr1'};
	$addr2      = $input_data->{'addr2'};
	$email      = $input_data->{'email'};

	//$mode       = "insert";
	//$memId      = "a51607340";
	//$memName    = "안예린";
	//$registNo   = "670225-1536126";
	//$hpNo       = "010-2723-3377";
	//$postNum    = "06281";
	//$addr1      = "서울 강남구 남부순환로 2907";
	//$addr2      = "222222";
	//$email      = "heemang4989@nate.com";

	if ($registNo != "") $registNo = aes128encrypt($registNo);
	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
	if ($email != "") $email = aes128encrypt($email);

	if ($mode == "insert") {
		// 계약정보 등록
		$sql = "INSERT INTO concierge_request (memId, memName, registNo, hpNo, postNum, addr1, addr2, email, requestStatus, wdate) 
									   VALUES ('$memId', '$memName', '$registNo', '$hpNo', '$postNum', '$addr1', '$addr2', '$email', '0', now())";
		$result = $connect->query($sql);
	}

	if ($result == true) {
		// 성공 결과를 반환합니다.
		$response = array(
			'result'    => "0",
			'message'   => "'신청완료' 되었습니다."
		);

	} else {
		// 실패 결과를 반환합니다.
		$response = array(
			'result'    => "1",
			'message'   => "'신청오류'가 발생하였습니다."
		);
	}

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>