<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 제휴제인서 정보
	* parameter
		idx: 제안서 일련번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT companyName, postCode, addr1, addr2, telNo, hpNo, email, chargeName, introduction, content, companyDoc FROM proposal WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->telNo != "") $row->telNo = aes_decode($row->telNo);
		if ($row->hpNo != "") $row->hpNo = aes_decode($row->hpNo);
		if ($row->email != "") $row->email = aes_decode($row->email);

		$data = array(
			'companyName'  => $row->companyName,
			'postCode'     => $row->postCode,
			'addr1'        => $row->addr1,
			'addr2'        => $row->addr2,
			'telNo'        => $row->telNo,
			'hpNo'         => $row->hpNo,
			'email'        => $row->email,
			'chargeName'   => $row->chargeName,
			'introduction' => $row->introduction,
			'content'      => $row->content,
			'companyDoc'   => $row->companyDoc
		);

		$result_status = "0";
		$result_message = "정상";

    } else {
		$data = array();
		$result_status = "1";
		$result_message = "존재하지 않는 '제안서'입니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
		'data'    => $data,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>