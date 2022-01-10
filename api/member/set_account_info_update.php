<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 정산계좌 정보 수정
	* parameter ==> memId:          회원ID
	* parameter ==> registNo:       주민번호
	* parameter ==> accountName:    예금주
	* parameter ==> accountNo:      계좌번호
	* parameter ==> accountBank:    은행명
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId       = $input_data->{'memId'};
	$registNo    = $input_data->{'registNo'};
	$accountName = $input_data->{'accountName'};
	$accountNo   = $input_data->{'accountNo'};
	$accountBank = $input_data->{'accountBank'};

	if ($accountNo != "") $accountNo = aes128encrypt($accountNo);

	$sql = "UPDATE member SET registNo     = '$registNo', 
	                          accountName  = '$accountName', 
							  accountNo    = '$accountNo', 
							  accountBank  = '$accountBank' 
			WHERE memId = '$memId'";
	$result = $connect->query($sql);

	// 성공 결과를 반환합니다.
	$result = "0";
	$result_message = "변경하였습니다.";

	$response = array(
		'result'    => $result,
		'message'   => $result_message,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>