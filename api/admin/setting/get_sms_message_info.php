<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* SMS 메세지 정보
	* parameter ==> idx: 아이디에 해당하는 idx
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, assort, code, subject, content, buttonYn, buttonName, mobileUrl, pcUrl FROM sms_message WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$assortName = selected_object($row->assort, $arrSmsAssort);

		$data = array(
			'idx'           => $row->idx,
			'assort'        => $row->assort,
			'assortName'    => $assortName,
			'code'          => $row->code,
			'subject'       => $row->subject,
			'content'       => $row->content,
			'buttonYn'      => $row->buttonYn,
			'buttonName'    => $row->buttonName,
			'mobileUrl'     => $row->mobileUrl,
			'pcUrl'         => $row->pcUrl,
		);

		// 업데이트모드로 결과를 반환합니다.
		$result_status = "0";

    } else {
		$data = array(
			'idx'           => '',
			'assort'        => '',
			'assortName'    => '',
			'code'          => '',
			'subject'       => '',
			'content'       => '',
			'buttonYn'      => '',
			'buttonName'    => '',
			'mobileUrl'     => '',
			'pcUrl'         => '',
		);

		// 추가모드로 결과를 반환합니다.
		$result_status = "1";
	}

	$response = array(
		'result'        => $result_status,
		'assortOptions' => $arrSmsAssort,
		'buttonOptions' => $arrYesNo2,
		'data'          => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>