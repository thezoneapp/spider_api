<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 컨시어지 > 아이디발급신청> 목록 > 상세정보 > 수정
	* parameter 
	    mode:           모드(수정: update, 추가: insert)
		idx:            일련번호
		memId:          모집자ID
		memName:        모집자명
		registNo:       주민번호
		hpNo:           휴대폰번호
		postNum:        우편번호
		addr1:          기본 주소
		addr2:          나머지 주소
		email:          이메일
		requestStatus:  발급상태
	*/
	$input_data    = json_decode(file_get_contents('php://input'));
	$mode          = $input_data->{'mode'};
	$idx           = $input_data->{'idx'};
	$memId         = $input_data->{'memId'};
	$memName       = $input_data->{'memName'};
	$conciergeId   = $input_data->{'conciergeId'};
	$registNo      = $input_data->{'registNo'};
	$hpNo          = $input_data->{'hpNo'};
	$postNum       = $input_data->{'postNum'};
	$addr1         = $input_data->{'addr1'};
	$addr2         = $input_data->{'addr2'};
	$email         = $input_data->{'email'};

	//$idx         = "1";
	//$mode       = "update";
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

	if ($mode == "update") {
		if ($conciergeId == "") $requestStatus = "0";
		else $requestStatus = "9";

		// 신청정보 저장
		$sql = "UPDATE concierge_request SET conciergeId = '$conciergeId',
											 registNo = '$registNo', 
											 hpNo = '$hpNo', 
											 postNum = '$postNum', 
											 addr1 = '$addr1', 
											 addr2 = '$addr2', 
											 email = '$email', 
											 requestStatus = '$requestStatus' 
					WHERE idx = '$idx'";
		$connect->query($sql);

		// 회원정보 > 컨시어지 ID 저장
		$sql = "UPDATE member SET conciergeId = '$conciergeId' WHERE memId = '$memId'";
		$connect->query($sql);
	}

	// 성공 결과를 반환합니다.
	$response = array(
		'result'    => "0",
		'message'   => "저장되었습니다."
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
