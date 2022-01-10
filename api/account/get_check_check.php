<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/cms.php";
	include "../../inc/utility.php";

	// *********************************************************************************************************************************
	// *                                                   계좌 실명인증                                                                 *
	// *********************************************************************************************************************************
	/*
	* parameter
	*	paymentCompany: 은행코드
	*	paymentNumber:  계좌번호
	*	payerNumber:    생년월일/사업자번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$paymentCompany = $input_data->{'paymentCompany'};
	$paymentNumber  = $input_data->{'paymentNumber'};
	$payerNumber    = $input_data->{'payerNumber'};

	$paymentNumber  = str_replace("-", "", $paymentNumber);
	$payerNumber    = str_replace(".", "", $payerNumber);
	$payerNumber    = str_replace("-", "", $payerNumber);

	//$paymentCompany = "003";
	//$paymentNumber  = "16913364001015";
	//$payerNumber    = "670225";

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

	/* ************************************************************************************
	* 최종결과 리턴
	************************************************************************************* */
	$response = array(
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );
?>