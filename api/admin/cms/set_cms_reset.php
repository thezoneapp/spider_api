<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	// *********************************************************************************************************************************
	// *                                                   CMS 재등록                                                                   *
	// *********************************************************************************************************************************
	/*
	* parameter
	*	adminId: 관리자ID
	*	memId:   회원ID
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$adminId = $input_data->{'adminId'};
	$memId   = $input_data->{'memId'};

	//$adminId = "admin";
	//$memId   = "a89580657";

	// 관리자 정보
	$sql = "SELECT name FROM admin WHERE id = '$adminId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$adminName = $row->name;

	$sql = "SELECT m.cmsId, m.memName, m.memAssort, m.hpNo, c.paymentKind, c.paymentCompany, c.paymentNumber, c.payerName, c.payerNumber, c.validYear, c.validMonth, c.cardPasswd 
            FROM cms c 
                 INNER JOIN member m ON c.memId = m.memId 
			WHERE m.memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$cmsId          = $row->cmsId;
		$memName        = $row->memName;
		$memAssort      = $row->memAssort;
		$hpNo           = trim($row->hpNo);
		$paymentKind    = $row->paymentKind;
		$paymentCompany = $row->paymentCompany;
		$paymentNumber  = $row->paymentNumber;
		$payerName      = $row->payerName;
		$payerNumber    = $row->payerNumber;
		$validYear      = $row->validYear;
		$validMonth     = $row->validMonth;
		$cardPasswd     = $row->cardPasswd;

		if ($hpNo != "") $hpNo = aes_decode($hpNo);
		if ($paymentNumber != "") $paymentNumber = aes_decode($paymentNumber);
		if ($payerNumber != "") $payerNumber = aes_decode($payerNumber);
		if ($validYear != "") $validYear = aes_decode($validYear);
		if ($validMonth != "") $validMonth = aes_decode($validMonth);
		if ($cardPasswd != "") $cardPasswd = aes_decode($cardPasswd);

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

		$result_status = "0";

	} else {
		$result_status = "1";
		$result_message = "존재하지 않는 회원니다.";
	}

	// API 전송
	if ($result_status == "0") {
		/****************** 기존자료 해지 */
		$url = "https://api.hyosungcms.co.kr/v1/members/$cmsId";

		// header
		$header = Array(
			"Content-Type: application/json; charset=utf-8", 
			"Authorization: VAN $SW_KEY:$CUST_KEY"
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($ch, CURLOPT_ENCODING , "");
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		$response = curl_exec($ch);

		curl_close($ch);

		$response = json_decode($response, true);
		//print_r( $response );

		/****************** 재신청 */
		$url = "https://api.hyosungcms.co.kr/v1/members";

		// header
		$header = Array(
			"Content-Type: application/json; charset=utf-8", 
			"Authorization: VAN $SW_KEY:$CUST_KEY"
		);

		// body
		$body = Array(
			"memberId" => $cmsId, 
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
			$result_status = "1";
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
					$result_status = "0";
					$result_message = "CMS 신청을 완료하였습니다.\n해당 은행의 승인완료 후 신청이 완료됩니다.";

				} else if ($status == "신청완료") {
					$agreeStatus = "9";
					$cmsStatus = "9";
					$result_status = "0";
					$result_message = "CMS 신청을 완료하였습니다.";
				}

				$sql = "UPDATE member SET cmsStatus = '$cmsStatus' WHERE memId = '$memId'";
				$connect->query($sql);

				// CMS 정보 삭제
				$sql = "UPDATE cms SET paymentNumber = null, payerNumber = null, validYear = null, validMonth = null, cardPasswd = null WHERE memId = '$memId'";
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
		'result'   => $result_status,
		'message'  => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>