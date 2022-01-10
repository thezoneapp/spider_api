<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 토큰값 취득
	* parameter ==> comId: 업체ID
	* parameter ==> comPw: 비밀번호
	*/
	$remoteIp   = $_SERVER['REMOTE_ADDR'];
	$input_data = json_decode(file_get_contents('php://input')); 
	$comId = $input_data->comId;
	$comPw = $input_data->comPw;

	//$comId = "chabot";
	//$comPw = "kjsIzj0KpFgRUDWaRTRKww==";

	// api 로그 저장
	$log_date = date("ymdHis");
	$log_file = "/home/spiderfla/upload/log/insu/" . $log_date . ".log";
	error_log ($comId . " : " . $remoteIp, 3, $log_file);

	$data = array();
	$sql = "SELECT idx FROM approval_ip WHERE comId = '$comId' and ip = '$remoteIp' and status = '1'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$sql = "SELECT idx FROM partner WHERE comId = '$comId' and comPw = '$comPw' and status = '1'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			// 토큰값 생성
			$token = $comId . ":" . date("ymdHis");
			$token = aes128encrypt($token);

			$data = array(
				'token' => $token
			);

			$sql = "INSERT INTO token (comId, token, expiredDate) 
			                   VALUES ('$comId', '$token', date_add(now(), interval +1 DAY))";
			$connect->query($sql);

			$result_status  = "200";
			$result_message = "성공";

		} else {
			$result_status  = "502";
			$result_message = "승인되지 않은 업체입니다.";
		}

	} else {
		$result_status  = "501";
		$result_message = "승인되지 않은 IP 접속입니다.";
	}

	$response = array(
		'resultCode' => $result_status,
		'message'    => $result_message,
		'data'       => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>