<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 내정보 > 주민번호 변경
	* parameter:
	  userId:      회원ID
	  taxRegistNo: 주민번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId      = $input_data->{'userId'};
	$taxRegistNo = $input_data->{'taxRegistNo'};

	if ($taxRegistNo != "") $taxRegistNo = aes128encrypt($taxRegistNo);

	$sql = "UPDATE member SET taxRegistNo = '$taxRegistNo' WHERE memId = '$userId'";
	$result = $connect->query($sql);

	// 성공 결과를 반환합니다.
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