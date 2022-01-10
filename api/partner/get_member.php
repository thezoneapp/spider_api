<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 협력업체에 회원정보 전송
	* parameter ==> token: 토큰값
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$token = $input_data->{'token'};
	
	//$token = "777210817111943";
	$data = array();
    $sql = "SELECT memId, memName, hpNo FROM member WHERE token = '$token'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

		$data = array(
			'memId'   => $row->memId,
			'memName' => $row->memName,
			'hpNo'    => $row->hpNo,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "성공";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "회원이 존재하지 않습니다.";
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>