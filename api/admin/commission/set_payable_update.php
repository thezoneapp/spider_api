<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 수수료대금 입금내용 등록
	* parameter ==> memId:         회원ID
	* parameter ==> memName:       회원명
	* parameter ==> bankName:      입금은행
	* parameter ==> accountNo:     계좌번호
	* parameter ==> accountName:   예금주
	* parameter ==> accountAmount: 입금액
	* parameter ==> accountDate:   입금일자
	*/

	$input_data    = json_decode(file_get_contents('php://input'));
	$memId         = $input_data->{'memId'};
	$memName       = $input_data->{'memName'};
	$bankName      = $input_data->{'bankName'};
	$accountNo     = $input_data->{'accountNo'};
	$accountName   = $input_data->{'accountName'};
	$accountAmount = $input_data->{'accountAmount'};
	$accountDate   = $input_data->{'accountDate'};

	if ($accountAmount == "") $accountAmount = "0";
	if ($accountNo != "") $accountNo = aes128encrypt($accountNo);

	$assort = "P";
	$wdate = date("Y-m-d");
	$sql = "INSERT INTO commi_account (memId, memName, assort, bankName, accountNo, accountName, accountAmount, accountDate, wdate)
	                           VALUES ('$memId', '$memName', '$assort', '$bankName', '$accountNo', '$accountName', '$accountAmount', '$accountDate', '$wdate')";
	$connect->query($sql);

	// 성공 결과를 반환합니다.
	$result = "0";
	$result_message = "등록하였습니다.";

	$response = array(
		'result'    => $result,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>