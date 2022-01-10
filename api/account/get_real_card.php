<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/cms.php";
	include "../../inc/utility.php";

	// *********************************************************************************************************************************
	// *                                                   신용카드 유효성 체크                                                           *
	// *********************************************************************************************************************************
	/*
	* parameter
		paymentNumber: 카드번호
		payerNumber:   생년월일
		validYear:     유효기간 년도
		validMonth:    유효기간 월
	*/
	$back_data = json_decode(file_get_contents('php://input'));
	$paymentNumber = $back_data->{'paymentNumber'};
	$payerNumber   = $back_data->{'payerNumber'};
	$validYear     = $back_data->{'validYear'};
	$validMonth    = $back_data->{'validMonth'};

	$paymentNumber  = str_replace("-", "", $paymentNumber);
	$payerNumber    = str_replace(".", "", $payerNumber);
	$payerNumber    = str_replace("-", "", $payerNumber);

	//$paymentNumber  = "6251032309586218";
	//$payerNumber    = "670225";
	//$validYear      = "2024";
	//$validMonth     = "03";

	$cms_body = Array(
		"paymentNumber" => $paymentNumber, 
		"payerNumber"   => $payerNumber,
		"validYear"     => $validYear,
		"validMonth"    => $validMonth,
	);

	// 신용카드 조회 함수 호출
	$response = cmsRealCard($cms_body);
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
//print_r($response);
//exit;
    echo json_encode( $response );
?>