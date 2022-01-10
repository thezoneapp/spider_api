<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 컨시어지 > 아이디발급신청> 목록 > 상세정보
	* parameter
		idx: 신청서에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};
	
	//$idx = 83;

	// 신청자료 검색
    $sql = "SELECT idx, memId, memName, conciergeId, registNo, hpNo, postNum, addr1, addr2, email, requestStatus, wdate 
	        FROM concierge_request 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->registNo !== "") $row->registNo = aes_decode($row->registNo);
		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);
		if ($row->email !== "") $row->email = aes_decode($row->email);

		if ($row->registNo == "") $registNo2 = "";
		else $registNo2 = substr($row->registNo, 0, 6) . "-*******";

		$requestStatus = selected_object($row->requestStatus, $arrIdRequestStatus);

		$data = array(
			'idx'            => $row->idx,
			'memId'          => $row->memId,
			'memName'        => $row->memName,
			'conciergeId'    => $row->conciergeId,
			'registNo'       => $row->registNo,
			'registNo2'      => $registNo2,
			'hpNo'           => $row->hpNo,	
			'postNum'		 => $row->postNum,		
			'addr1'			 => $row->addr1,	
			'addr2'			 => $row->addr2,	
			'email'	         => $row->email,	
			'requestStatus'  => $requestStatus,
			'wdate'          => $row->wdate,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result_status = "1";
	}

	$response = array(
		'result'          => $result_status,
		'message'         => $result_message,
		'statusOptions'   => $arrIdRequestStatus,
		'data'            => $data,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>