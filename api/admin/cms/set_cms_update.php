<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* CMS정보 수정
	* parameter ==> memId:         회원ID
	* parameter ==> paymentNumber: 계좌번호
	* parameter ==> payerName:     예금주
	* parameter ==> payerNumber:   주민번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId         = $input_data->{'memId'};
	$paymentNumber = $input_data->{'paymentNumber'};
	$payerName     = $input_data->{'payerName'};
	$payerNumber   = $input_data->{'payerNumber'};
	$cmsAmount     = $input_data->{'cmsAmount'};
	$commiAmount   = $input_data->{'commiAmount'};

	if ($paymentNumber != "") $paymentNumber = aes128encrypt($paymentNumber);
	if ($payerNumber != "") $payerNumber = aes128encrypt($payerNumber);

	$sql = "UPDATE cms SET paymentNumber = '$paymentNumber', 
		                   payerName = '$payerName', 
						   payerNumber = '$payerNumber',
		                   cmsAmount = '$cmsAmount', 
						   commiAmount = '$commiAmount' 
				WHERE memId = '$memId'";
	$connect->query($sql);

	// 변경 결과를 반환합니다.
	$result_status = "0";
	$result_message = "변경하였습니다.";

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>