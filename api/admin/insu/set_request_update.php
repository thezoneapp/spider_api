<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 애드인슈 > 신청정보 수정
	* parameter
		idx  :          신청서 일련번호
		adminId:        관리자ID
	    adminName:      관리자명
		memId:          회원ID
		custName:       고객명
		hpNo:           휴대폰번호
		carNoType:      차량/차대번호 구분
		carNo:          등록번호
		expiredDate:    만기월
		custRegion:     거주지역
		marketingAgree: 마케팅 동의여부
		marketingFlag:  애드인슈-마케팅 동의여부
		insurFee:       보험료
		commission:     수수료
		requestStatus:  진행상태
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx            = $input_data->{'idx'};
	$adminId        = $input_data->{'adminId'};
	$adminName      = $input_data->{'adminName'};
	$memId          = $input_data->{'memId'};
	$custName       = $input_data->{'custName'};
	$hpNo           = $input_data->{'hpNo'};
	$carNoType      = $input_data->{'carNoType'};
	$carNo          = $input_data->{'carNo'};
	$expiredDate    = $input_data->{'expiredDate'};
	$custRegion     = $input_data->{'custRegion'};
	$marketingAgree = $input_data->{'marketingAgree'};
	$marketingFlag  = $input_data->{'marketingFlag'};
	$insurFee       = $input_data->{'insurFee'};
	$commission     = $input_data->{'commission'};
	$requestStatus  = $input_data->{'requestStatus'};

	$carNoType      = $carNoType->{'code'};
	$expiredDate    = $expiredDate->{'code'};
	$custRegion     = $custRegion->{'code'};
	$requestStatus  = $requestStatus->{'code'};

	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);

	$insurFee = str_replace(",", "", $insurFee);
	$commission = str_replace(",", "", $commission);

	$sql = "UPDATE insu_request SET custName = '$custName', 
								    hpNo = '$hpNo', 
								    carNoType = '$carNoType', 
	                                carNo = '$carNo', 
								    expiredDate = '$expiredDate', 
								    custRegion = '$custRegion', 
									marketingAgree = '$marketingAgree', 
								    marketingFlag = '$marketingFlag', 
									insurFee = '$insurFee', 
									commission = '$commission', 
								    requestStatus = '$requestStatus' 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

	// 로그등록
	$sql = "INSERT INTO insu_request_log (insuIdx, memId, memName, status, wdate) 
							      VALUES ('$idx', '$adminId', '$adminName', '$requestStatus', now())";
	$connect->query($sql);

	if ($result == "1") {
		$result_status = "0";
		$result_message = "저장되었습니다";

	} else {
		$result_status = "1";
		$result_message = "오류가 발생되었습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>