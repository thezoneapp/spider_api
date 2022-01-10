<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 회원 > 소비자관리 > 소비자 정보
	* parameter
		custId:    소비자ID
		nickName:  닉네임
		custMemo:  소비자정보
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$custId     = $input_data->{'custId'};
	$nickName   = $input_data->{'nickName'};
	$custMemo   = $input_data->{'custMemo'};

	$sql = "UPDATE customer SET nickName = '$nickName', 
	                            custMemo = '$custMemo' 
				WHERE custId = '$custId'";
	$connect->query($sql);

	$response = array(
		'result'  => "0",
		'message' => "'변경'되었습니다."
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>