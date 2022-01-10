<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 정산서 세부 정보
	* parameter ==> idx: 정산서 idx
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, memId, memName, minDate, maxDate, commission,  
	               otherDescript, otherAmount, totalAmount, accurateStatus, wdate 
	        FROM commi_accurate 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$dueDate = $row->minDate . "~" . $row->maxDate;
		$taxName = selected_object($row->taxAssort, $arrTaxAssort);
		$statusName = selected_object($row->accurateStatus, $arrAccurateStatus);

		if ($row->otherDescript == null) $row->otherDescript = "";

		$data = array(
			'idx'            => $row->idx,
			'memId'          => $row->memId,
			'memName'        => $row->memName,
			'dueDate'        => $dueDate,
			'commission'     => number_format($row->commission),
			'otherDescript'  => $row->otherDescript,
			'otherAmount'    => number_format($row->otherAmount),
			'totalAmount'    => number_format($row->totalAmount),
			'accurateStatus' => $row->accurateStatus,
			'statusName'     => $statusName,
			'accurateDate'   => $row->wdate,
		);

		// 업데이트 모드로 결과를 반환합니다.
		$result = "0";

	} else {
		$data = array(
			'idx'            => "",
			'memId'          => "",
			'memName'        => "",
			'dueDate'        => "",
			'commission'     => "",
			'otherDescript'  => "",
			'otherAmount'    => "",
			'totalAmount'    => "",
			'accurateStatus' => "",
			'statusName'     => $statusName,
			'statusOptions'  => $arrAccurateStatus,
			'accurateDate'   => "",
		);
	}

	$response = array(
		'result' => $result,
		'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
