<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 관리자 정보
	* parameter ==> idx: 아이디에 해당하는 idx
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, code, subject, content FROM setting WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$data = array(
			'idx'     => $row->idx,
			'code'    => $row->code,
			'subject' => $row->subject,
			'content' => $row->content,
		);

		// 업데이트모드로 결과를 반환합니다.
		$result = "0";

    } else {
		$data = array(
			'idx'     => '',
			'code'    => '',
			'subject' => '',
			'content' => '',
		);

		// 추가모드로 결과를 반환합니다.
		$result = "1";
	}

	$response = array(
		'error' => $result,
		'data'  => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>