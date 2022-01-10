<?
	// *********************************************************************************************************************************
	// *                                                     애드인슈 > 신청서 저장                                                       *
	// *********************************************************************************************************************************
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "./token.php";

	/*
	* parameter ==> memId:          회원ID
	* parameter ==> custName:       고객명
	* parameter ==> hpNo:           고객 연락처
	* parameter ==> carNoType:      차량/차대번호 구분
	* parameter ==> carNo:          차량/차대번호
	* parameter ==> expiredDate:    만기일자
	* parameter ==> custRegion:     거주지역
	* parameter ==> marketingAgree: 마케팅 동의여부
	* parameter ==> marketingFlag:  애드인슈-마케팅 동의여부
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId          = $input_data->{'memId'};
	$custName       = $input_data->{'custName'};
	$hpNo           = $input_data->{'hpNo'};
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
//$expiredDate = "2월";
//$custRegion = "경기";
//$marketingAgree = "Y";
//$marketingFlag = "Y";

	$custHpNo = str_replace("-", "", $hpNo);
	$carNo = str_replace(" ", "", $carNo);

	// 회원정보
	$sql = "SELECT memName, hpNo, insuId FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$memName = $row->memName;
	$dealerCode = $row->insuId;
	
	//$dealerCode = "QE300024";
	
	//if ($row->hpNo !== "") $hpNo = aes_decode($row->hpNo);

	/* *************************************** 애드인슈 API 호출 시작 ********************************************************* */
	// 토큰 취득
	$token = json_decode(get_token());
	$responseCode = $token->responseCode;
	$result_message = $token->message;

	if ($responseCode == "200") {
		$tokenKey = $token->data->tokenKey;

		// 신청서 등록 API 호출
		//$url = "https://dev-usedcar-api.adinsu.co.kr/v2/externalApi/customer/requestDBIns"; // 개발서버
		$url = "https://usedcar-api.adinsu.co.kr/v2/externalApi/customer/requestDBIns"; // 실서버

		$headers = Array(
			'OPERA-TOKEN: ' . $tokenKey,
			'Content-Type: application/json'
		);
		$fields = Array(
			"coCode"         => $coCode, 
			"siteCode"       => $siteCode, 
			"mainCode"       => $mainCode, 
			"subCode"        => $subCode, 
			"categoryCode"   => $categoryCode, 
			"intype"         => $intype, 
			"dealerCode"     => $dealerCode, 
			"customerName"   => $custName, 
			"customerMobile" => $custHpNo, 
			"carNoType"      => $carNoType, 
			"carNo"          => $carNo, 
			"expiredDate"    => $expiredDate,
			"customerRegion" => $custRegion,
			"marketingFlag"  => $marketingFlag
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($ch, CURLOPT_ENCODING , "");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields)); 
			
		$response = curl_exec($ch);
		$err = curl_error($ch);

		curl_close($ch);

		if ($err) {
			echo "curl Error #:" . $err;
		}

		$response = json_decode($response);
		$responseCode = $response->responseCode;

		if ($responseCode == "200") {
			if ($hpNo != "") $hpNo = aes128encrypt($hpNo);

			$seqNo = $response->data->seqno;

			$sql = "INSERT INTO insu_request (memId, memName, seqNo, custName, hpNo, carNoType, carNo, expiredDate, custRegion, marketingAgree, marketingFlag, requestStatus, wdate) 
									  VALUES ('$memId', '$memName', '$seqNo', '$custName', '$hpNo', '$carNoType', '$carNo', '$expiredDate', '$custRegion', '$marketingAgree', '$marketingFlag', '0', now())";
			$connect->query($sql);

			// 신청서 번호
			$sql = "SELECT idx FROM insu_request WHERE memId = '$memId' ORDER BY wdate DESC LIMIT 1";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);
				$insuIdx = $row2->idx;

			} else {
				$insuIdx = 0;
			}

			// 로그등록
			$sql = "INSERT INTO insu_request_log (insuIdx, memId, memName, status, wdate) 
									      VALUES ('$insuIdx', '$memId', '$memName', '0', now())";
			$connect->query($sql);

			$result_status = "0";
			$result_message = "신청이 완료되었습니다.";

		} else {
			$result_status = "1";
			$result_message = $response->message;
		}

	} else {
		$result_status = "1";
	}
	/* *************************************** 애드인슈 API 호출 끝 ********************************************************* */

	$response = array(
		'result'   => $result_status,
		'message'  => $result_message
    );

	//print_r($response);
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>