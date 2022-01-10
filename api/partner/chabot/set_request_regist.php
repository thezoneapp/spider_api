<?
	// *********************************************************************************************************************************
	// *                                                다이렉트보험 > 차봇 > 신청서 저장                                                  *
	// *********************************************************************************************************************************
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/chabot.php";
	include "../../../inc/customer.php";

	/*
	* parameter 
		memId:          회원ID
		custName:       고객명
		hpNo:           고객 연락처
		carType:        N: 신규계약, O: 갱신계약
		carNoType:      차량/차대번호 구분
		carNo:          차량/차대번호
		expiredDate:    만기일자
		custRegion:     거주지역
		marketingAgree: 마케팅 동의여부
		marketingFlag:  애드인슈-마케팅 동의여부
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId          = $input_data->{'memId'};
	$custName       = $input_data->{'custName'};
	$hpNo           = $input_data->{'hpNo'};
	$insuAssort     = $input_data->{'insuAssort'};
	$carNoType      = $input_data->{'carNoType'};
	$carNo          = $input_data->{'carNo'};
	$expiredDate    = $input_data->{'expiredDate'};
	$custRegion     = $input_data->{'custRegion'};
	$marketingAgree = $input_data->{'marketingAgree'};
	$marketingFlag  = $input_data->{'marketingFlag'};

//$memId = "a27233377";
//$custName = "최남희";
//$hpNo = "010-2723-3377";
//$carNoType = "1";
//$carNo = "23우9144";
//$insuAssort = "N";
//$expiredDate = "2021-10-25";
//$custRegion = "경기";
//$marketingAgree = "Y";
//$marketingFlag = "Y";

	$custHpNo = str_replace("-", "", $hpNo);
	$carNo = str_replace(" ", "", $carNo);

	// 고객 정보 등록
	$custId = customer_regist($custName, $hpNo);

	// 회원정보
	$sql = "SELECT memName, memAssort, hpNo, insuId FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$memName    = $row->memName;
		$memAssort  = $row->memAssort;
		$dealerCode = $row->insuId;

		$today = date("Y-m-d", time());
		$beforeDay = date("Y-m-d", strtotime($expiredDate." -45 day"));

		if ($insuAssort == "N") $carType = "new_car";
		else $carType = "re_new_car";

		if ($today >= $beforeDay) {
			/* *************************************** 차봇 API 호출 시작 ********************************************************* */
			$body = Array(
				"coCode"         => $coCode, 
				"mode"           => "tm_regist", 
				"dealerCode"     => $dealerCode, 
				"customerName"   => $custName, 
				"customerMobile" => $custHpNo, 
				"carNoType"      => $carNoType, 
				"carNo"          => $carNo, 
				"carType"        => $carType, 
				"expiredDate"    => $expiredDate,
				"customerRegion" => $custRegion,
				"marketingFlag"  => $marketingFlag
			);

			// 신청서 등록 함수 호출 /inc/chabot.php
			$response = requestRegist($body);
			$response = json_decode($response);

			$seqNo = $response->{'seqNo'};
			$result_status  = $response->{'result'};
			$result_message = $response->{'message'};
			$isRceiptTarget = "N";

		} else {
			$seqNo = "0";
			$result_status  = "0";
			$result_message = "신청이 완료되었습니다.";
			$isRceiptTarget = "Y";
		}

		if ($result_status == "0") {
			if ($hpNo != "") $hpNo = aes128encrypt($hpNo);

			$sql = "INSERT INTO insu_request (memId, memName, memAssort, seqNo, insuAssort, custId, custName, hpNo, carNoType, carNo, expiredDate, custRegion, marketingAgree, marketingFlag, requestStatus, wdate) 
									  VALUES ('$memId', '$memName', '$memAssort', '$seqNo', '$insuAssort', '$custId', '$custName', '$hpNo', '$carNoType', '$carNo', '$expiredDate', '$custRegion', '$marketingAgree', '$marketingFlag', '0', now())";
			$connect->query($sql);
		}

	} else {
		$result_status = "1";
		$result_message = "회원정보가 존재하지 않습니다.";
	}
	/* *************************************** 차봇 API 호출 끝 ********************************************************* */

	$response = array(
		'result'   => $result_status,
		'message'  => $result_message
    );

	//print_r($response);
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>