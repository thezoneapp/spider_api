<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 휴대폰신청 > 가입신청서URL > 목록 > 저장
	* parameter
		data: data 배열
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$datas = $input_data->{'data'};

	for ($i = 0; count($datas) > $i; $i++) {
		$data = $datas[$i];

		$idx      = $data->idx;
		$writeUrl = $data->writeUrl;

		$sql = "UPDATE hp_write_url SET writeUrl = '$writeUrl' WHERE idx = '$idx'";
		$connect->query($sql);
	}

	// 성공 결과를 반환합니다.
	$result_status = "0";
	$result_message = "저장하였습니다.";

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>