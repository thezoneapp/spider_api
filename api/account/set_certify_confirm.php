<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    
	/*
	* 접속 IP, 접속기기 정보 변경
	* parameter
		userId:       회원ID
		device:       접속기기
		connectIP:    접속 IP
	*/
	$back_data = json_decode(file_get_contents('php://input'));
	$userId    = $back_data->{'userId'};
	$device    = $back_data->{'device'};
	$connectIP = $back_data->{'connectIP'};

	// 실행할 쿼리를 작성합니다.
    $sql = "SELECT idx FROM member WHERE memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		// 접속 기기
		if ($device == "W") $device_sql = ", useWeb = 'Y' ";
		else if ($device == "A") $device_sql = ", useAndroid = 'Y' ";
		else if ($device == "I") $device_sql = ", useIphone = 'Y' ";
		else $device_sql = "";

		$sql = "UPDATE member SET connectIp = '$connectIP', 
								  connectDevice = '$device' $device_sql 
					WHERE memId = '$userId'";
		$connect->query($sql);

		// 결과를 반환합니다.
		$result_status = "0";
		$result_message = "'접속정보'가 변경되었습니다.";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "해당하는 회원이 존재하지 않습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
