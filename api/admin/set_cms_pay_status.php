<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	// *********************************************************************************************************************************
	// *                                                   CMS 출금신청 조회                                                             *
	// *********************************************************************************************************************************
	/*
	* parameter
	*	transactionId: 거래ID
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$transactionId = $input_data->{'transactionId'};

	//$transactionId = "2010081237451";

	// 출금신청내역
	$sql = "SELECT memId, memName, paymentKind, payMonth, payAmount FROM cms_pay WHERE transactionId = '$transactionId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$memId = $row->memId;
		$memName = $row->memName;
		$paymentKind = $row->paymentKind;
		$payMonth = $row->payMonth;
		$payAmount = $row->payAmount;
		$result_ok = "0";

	} else {
		$result_ok = "1";
		$result_message = "해당 정보가 존재하지 않습니다.";
	}

	// API 전송
	if ($result_ok == "0") {
		if ($paymentKind == "CMS") $urlKind = "cms";
		else if ($paymentKind == "CARD") $urlKind = "card";

		$url = "https://api.hyosungcms.co.kr/v1/payments/" . $urlKind . "/" . $transactionId;

		// header
		$header = Array(
			"Content-Type: application/json; charset=utf-8", 
			"Authorization: VAN $SW_KEY:$CUST_KEY"
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($ch, CURLOPT_ENCODING , "");
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		$response = curl_exec($ch);

		curl_close($ch);

		$response = json_decode($response, true);

		if ($response[error] != null) {
			$result_ok = "1";
			$status_status = "1";
			$message = $response[error][message];
			$result_message = $response[error][message];

		} else {
			$result_ok = "0";
			$member = $response[payment];
			$status = $response[payment][status];
			$message = $response[payment][status];
			$result_message = $response[payment][status];

			// 출금신청자료 삭제
			$sql = "UPDATE cms_pay set payStatus = '' WHERE transactionId = '$transactionId'";
			$connect->query($sql);
		}
	}

	$response = array(
		'result'   => $result_ok,
		'message'  => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>