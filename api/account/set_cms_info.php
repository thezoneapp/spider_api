<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	// *********************************************************************************************************************************
	// *                                                   CMS 신청                                                             *
	// *********************************************************************************************************************************
	/*
	* parameter
	*	memId:          회원 아이디
	*	paymentKind:    납부수단
	*	paymentCompany: 은행코드
	*	paymentNumber:  계좌/카드번호
	*	payerName:      예금주/소유주
	*	payerNumber:    생년월일/사업자번호
	*	valid:          유효기간(년도/월)
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId          = $input_data->{'memId'};
	$paymentKind    = $input_data->{'paymentKind'};
	$paymentCompany = $input_data->{'paymentCompany'};
	$paymentNumber  = $input_data->{'paymentNumber'};
	$payerName      = $input_data->{'payerName'};
	$payerNumber    = $input_data->{'payerNumber'};
	$valid          = $input_data->{'valid'};
	$cardPasswd     = $input_data->{'cardPasswd'};

	$paymentCompany = $paymentCompany->{'code'};
	$payerNumber    = str_replace(".", "", $payerNumber);
	$paymentNumber  = str_replace("-", "", $paymentNumber);
	$arrValid       = explode("/", $valid);
	$validYear      = $arrValid[0];
	$validMonth     = $arrValid[1];

	//$memId          = "27233377";
	//$paymentKind    = "CARD";
	//$paymentCompany = "003";
	//$paymentNumber  = "6251032309586218";
	//$payerName      = "박태수";
	//$payerNumber    = "670225";
	//$validYear      = "24";
	//$validMonth     = "03";
	//$cardPasswd     = "10";

	if ($paymentNumber != "") $paymentNumber = aes128encrypt($paymentNumber);
	if ($payerNumber != "") $payerNumber = aes128encrypt($payerNumber);
	if ($validYear != "") $validYear = aes128encrypt($validYear);
	if ($validMonth != "") $validMonth = aes128encrypt($validMonth);
	if ($cardPasswd != "") $cardPasswd = aes128encrypt($cardPasswd);

	$sql = "SELECT memId FROM cms WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows == 0) {
		$sql = "INSERT INTO cms (memId, paymentKind, paymentCompany, paymentNumber, payerName, payerNumber, validYear, validMonth, cardPasswd, wdate) 
						 VALUES ('$memId', '$paymentKind', '$paymentCompany', '$paymentNumber', '$payerName', '$payerNumber', '$validYear', '$validMonth', '$cardPasswd', now())";
		$result = $connect->query($sql);

	} else {
		$sql = "UPDATE cms SET paymentKind = '$paymentKind', 
							   paymentCompany = '$paymentCompany', 
							   paymentNumber = '$paymentNumber', 
                               payerName = '$payerName', 
						       payerNumber = '$payerNumber', 
                               validYear = '$validYear',
							   validMonth = '$validMonth', 
							   cardPasswd = '$cardPasswd', 
							   wdate = now()
				WHERE memId = '$memId'";
		$result = $connect->query($sql);
	}

	// 효성 CMS에 전송할 자료 구성
	$sql = "SELECT m.memId, m.memName, m.hpNo, m.memAssort, c.paymentKind, c.paymentCompany, c.paymentNumber, c.payerName, c.payerNumber, c.validYear, c.validMonth, c.cardPasswd 
			FROM member m
				INNER JOIN cms c ON m.memId = c.memId
			WHERE m.memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$adminId   = $row->memId;
		$adminName = $row->memName;
		$memId = $row->memId;
		$memName = $row->memName;
		$memAssort = $row->memAssort;
		$paymentKind = $row->paymentKind;
		$paymentCompany = $row->paymentCompany;
		$payerName = $row->payerName;

		if ($row->hpNo != "") $hpNo = aes_decode($row->hpNo);
		if ($row->paymentNumber != "") $paymentNumber = aes_decode($row->paymentNumber);
		if ($row->payerNumber != "") $payerNumber = aes_decode($row->payerNumber);
		if ($row->validYear != "") $validYear = aes_decode($row->validYear);
		if ($row->validMonth != "") $validMonth = aes_decode($row->validMonth);
		if ($row->cardPasswd != "") $cardPasswd = aes_decode($row->cardPasswd);

		$hpNo = str_replace("-", "", $hpNo);

		// 납부비용
		if ($memAssort == "M") $setCode = "payA";
		else $setCode = "payS";

		$sql = "SELECT content FROM setting WHERE code = '$setCode'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);
			$defaultAmount = $row->content;

		} else {
			$defaultAmount = "0";
		}

		$result = "0";

	} else {
		$result = "1";
		$result_message = "존재하지 않는 회원니다.";
	}

	// 테스트 계정 정보
	//$url = "https://api.efnc.co.kr:1443/v1/members";
	//$managerId = "sdsitest";
	//$CUST_ID   = "sdsitest";
	//$SW_KEY = "4LjFflzr6z4YSknp";
	//$CUST_KEY = "BT2z4D5DUm7cE5tl";

	// API 전송
	if ($result == "0") {
		$url = "https://api.hyosungcms.co.kr/v1/members";

		// header
		$header = Array(
			"Content-Type: application/json; charset=utf-8", 
			"Authorization: VAN $SW_KEY:$CUST_KEY"
		);

		// body
		$body = Array(
			"memberId" => $memId, 
			"memberName" => $memName, 
			"smsFlag" => "N", 
			"phone" => $hpNo,
			"email" => "",
			"zipcode" => "",
			"address1" => "",
			"address2" => "",
			"joinDate" => "",
			"receiptFlag" => "Y",
			"receiptNumber" => $hpNo,
			"memberKind" => "",
			"managerId" => $managerId,
			"memo" => "",
			"paymentStartDate" => "",
			"paymentEndDate" => "",
			"paymentDay" => "",
			"defaultAmount" => $defaultAmount,
			"paymentKind" => $paymentKind,
			"paymentCompany" => $paymentCompany,
			"paymentNumber" => $paymentNumber,
			"payerName" => $payerName,
			"payerNumber" => $payerNumber,
			"validYear" => $validYear,
			"validMonth" => $validMonth,
			"password" => $cardPasswd
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($ch, CURLOPT_ENCODING , "");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); 

		$response = curl_exec($ch);

		curl_close($ch);

		$response = json_decode($response, true);
		//print_r( $response );

		if ($response[error] != null) {
			$result = "1";
			$message = $response[error][message];
			$result_message = $response[error][message];
		} else {
			$member = $response[member];
			$status = $response[member][status];
			$message = $response[member][message];

			if ($status == "신청대기" || $status == "신청완료") {
				$message = $status;

				if ($status == "신청대기") {
					$agreeStatus = "0";
					$cmsStatus = "1";
					$result = "0";
					$result_message = "CMS 신청을 완료하였습니다.\n해당 은행의 승인완료 후 신청이 완료됩니다.";

				} else if ($status == "신청완료") {
					$agreeStatus = "9";
					$cmsStatus = "9";
					$result = "0";
					$result_message = "CMS 신청을 완료하였습니다.";

				} else {
					$agreeStatus = "0";
				}

				$sql = "UPDATE member SET agreeStatus = '$agreeStatus', cmsStatus = '$cmsStatus' WHERE memId = '$memId'";
				$connect->query($sql);
			}
		}

		// CMS 로그등록
		$assort = "1";
		$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
		                     VALUES ('$memId', '$assort', '$message', '$adminId', '$adminName', now())";
		$connect->query($sql);
	}

	$response = array(
		'result'   => $result,
		'message'  => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>