<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 수수료대금 추가/수정
	* parameter ==> mode:          insert(추가), update(수정)
	* parameter ==> idx:           수정할 레코드 id
	* parameter ==> bankName:      입금은행
	* parameter ==> accountNo:     입금계좌
	* parameter ==> accountName:   예금주명
	* parameter ==> accountAmount: 입금금액
	* parameter ==> accountDate:   입금일자
	*/
	$input_data    = json_decode(file_get_contents('php://input'));
	$mode          = $input_data->{'mode'};
	$idx           = $input_data->{'idx'};
	$bankName      = $input_data->{'bankName'};
	$accountNo     = $input_data->{'accountNo'};
	$accountName   = $input_data->{'accountName'};
	$accountAmount = $input_data->{'accountAmount'};
	$accountDate   = $input_data->{'accountDate'};

	if ($accountAmount == "") $accountAmount = "0";
	if ($accountNo != "") $accountNo = aes128encrypt($accountNo);

	$sql = "UPDATE commi_account SET bankName = '$bankName', 
	                                 accountNo = '$accountNo', 
								     accountName = '$accountName', 
								     accountAmount = '$accountAmount', 
								     accountDate = '$accountDate'
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

	// 변경 결과를 반환합니다.
	$result = "0";
	$result_message = "변경하였습니다.";

	$response = array(
		'result'    => $result,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>