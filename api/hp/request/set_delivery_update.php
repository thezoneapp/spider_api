<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/kakaoTalk.php";

	/*
	* 관리자페이지 > 휴대폰신청 > 배송정보변경
	* parameter ==> 
		idx  :            신청서 일련번호
		adminId:          관리자ID
		deliveryCompany:  택배사코드
		deliveryNo:       송장번호
	*/

	$input_data      = json_decode(file_get_contents('php://input'));
	$idx             = $input_data->{'idx'};
	$adminId         = $input_data->{'adminId'};
	$deliveryCompany = $input_data->{'deliveryCompany'};
	$deliveryNo      = $input_data->{'deliveryNo'};

	$deliveryCompany = $deliveryCompany->{'code'};

	$groupCode = "spider";

	//$idx             = "1056";
	//$deliveryCompany = "01";
	//$deliveryNo      = "6078921144893";

	if ($deliveryCompany == "000") $deliveryStatus = "9"; // 배송완료
	else $deliveryStatus = "1"; // 발송처리

	$sql = "UPDATE hp_request SET deliveryCompany = '$deliveryCompany', 
								  deliveryNo = '$deliveryNo', 
								  deliveryStatus = '$deliveryStatus'
				WHERE idx = '$idx'";
	$connect->query($sql);

	// 신청정보
	$sql = "SELECT memHpNo, custName, hpNo AS custHpNo, dc.companyName 
			FROM hp_request hr 
				 INNER JOIN delivery_company dc ON hr.deliveryCompany = dc.companyCode 
	        WHERE hr.idx = '$idx'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	if ($row->memHpNo !== "") $row->memHpNo = aes_decode($row->memHpNo);
	if ($row->custHpNo !== "") $row->custHpNo = aes_decode($row->custHpNo);

	$memHpNo     = $row->memHpNo;
	$custName    = $row->custName;
	$custHpNo    = $row->custHpNo;
	$companyName = $row->companyName;

	// 퀵배송
	if ($deliveryCompany == "000") {
		// 알림톡 전송(고객)
		$custHpNo = preg_replace('/\D+/', '', $custHpNo);
		$receiptInfo = array(
			"receiptHpNo"    => $custHpNo,
		);
		sendTalk($groupCode, "HP_07_05_01", $receiptInfo);

		// 알림톡 전송(사업자)
		$memHpNo = preg_replace('/\D+/', '', $memHpNo);
		$receiptInfo = array(
			"custName"       => $custName,
			"receiptHpNo"    => $memHpNo,
		);
		sendTalk($groupCode, "HP_07_06_01", $receiptInfo);

	// 택배배송
	} else {
		// 알림톡 전송(고객)
		$custHpNo = preg_replace('/\D+/', '', $custHpNo);
		$receiptInfo = array(
			"companyCode"    => $deliveryCompany,
			"companyName"    => $companyName,
			"invoiceNo"      => $deliveryNo,
			"receiptHpNo"    => $custHpNo,
		);
		sendTalk($groupCode, "HP_07_01_03", $receiptInfo);

		// 알림톡 전송(사업자)
		$memHpNo = preg_replace('/\D+/', '', $memHpNo);
		$receiptInfo = array(
			"custName"       => $custName,
			"companyCode"    => $deliveryCompany,
			"companyName"    => $companyName,
			"invoiceNo"      => $deliveryNo,
			"receiptHpNo"    => $memHpNo,
		);
		sendTalk($groupCode, "HP_07_02_01", $receiptInfo);
	}

	$result_status = "0";
	$result_message = "'발송처리'로 저장되었습니다.";

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>