<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/cms.php";
	include "../../inc/utility.php";

	/*
	* 내 정산계좌 수정
	* parameter ==> userId:         아이디
	* parameter ==> registNo:       주민번호
	* parameter ==> accountName:    예금주
	* parameter ==> accountNo:      계좌번호
	* parameter ==> accountBank:    은행명
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId      = $input_data->{'userId'};
	$registNo    = $input_data->{'registNo'};
	$accountName = $input_data->{'accountName'};
	$accountNo   = $input_data->{'accountNo'};
	$accountBank = $input_data->{'accountBank'};

	//$userId      = "a27233377";
	//$registNo    = "670225-1536126";
	//$accountName = "박태수";
	//$accountNo   = "16913364001015";
	//$accountBank = "003";

	// *********************************************************************************************************************************
	// *                                                   계좌 실명인증                                                                 *
	// *********************************************************************************************************************************
	$paymentCompany = $accountBank;
	$paymentNumber  = $accountNo;
	$payerNumber    = $registNo;

	if ($registNo != "") {
		$arrRegistNo = explode("-", $registNo);
		$payerNumber = $arrRegistNo[0];
	} else {
		$payerNumber = $registNo;
	}

	$paymentNumber  = str_replace("-", "", $paymentNumber);
	$payerNumber    = str_replace(".", "", $payerNumber);

	$cms_body = Array(
		"paymentCompany" => $paymentCompany, 
		"paymentNumber"  => $paymentNumber, 
		"payerNumber"    => $payerNumber,
	);

	// 계좌실명조회 함수 호출
	$response = cmsRealName($cms_body);
	$response = json_decode($response);

	$result_status  = $response->{'result'};
	$result_message = $response->{'message'};

	if ($result_message == null) $result_message = "오류가 발생했습니다.";

	// 계좌 유효성 체크가 유효하면
	if ($result_status == "0") {
		if ($registNo != "") $registNo = aes128encrypt($registNo);
		if ($accountNo != "") $accountNo = aes128encrypt($accountNo);

		$sql = "UPDATE member SET registNo     = '$registNo', 
								  accountName  = '$accountName', 
								  accountNo    = '$accountNo', 
								  accountBank  = '$accountBank' 
				WHERE memId = '$userId'";
		$result = $connect->query($sql);

		// 성공 결과를 반환합니다.
		$result = "0";
		$result_message = "변경하였습니다.";
	}

	$response = array(
		'result'  => $result,
		'message' => $result_message,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>