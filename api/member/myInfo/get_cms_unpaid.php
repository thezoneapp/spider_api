<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 회원 > 내정보 > 구독료 미납 정보
	* parameter ==> userId: 회원아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId     = $input_data->{'userId'};
	
	//$userId = "a44999359";

	$data = array();
    $sql = "SELECT @a:=@a+1 no, idx, payMonth, payAmount, requestStatus, payStatus 
	        FROM ( select idx, payMonth, payAmount, requestStatus, payStatus 
		           from cms_pay 
		           where memId = '$userId' and (requestStatus = '1' or payStatus = '5') 
		         ) c, (select @a:= 0) as a 
			ORDER BY payMonth ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if (($row[requestStatus] == "0" && $row[payStatus] == "0") || $row[payStatus] == "1") $payStatus = "납부중";
			else if ($row[payStatus] == "5") $payStatus = "출금오류";
			else $payStatus = "미납";

			$data_info = array(
				'no'        => $row[no],
				'idx'       => $row[idx],
				'payMonth'  => $row[payMonth],
				'payAmount' => $row[payAmount],
				'payStatus' => $payStatus,
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	$response = array(
		'result'  => $result_status,
		'data'    => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>