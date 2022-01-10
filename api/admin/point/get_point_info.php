<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 포인트 세부 정보
	* parameter ==> idx: 정산서 idx
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, memId, memName, assort, descript, point, accurateIdx, wdate 
	        FROM point 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$assortName = selected_object($row->assort, $arrPointAssort);

		if ($row->descript == null) $row->descript = "";

		$data = array(
			'idx'           => $row->idx,
			'memId'         => $row->memId,
			'memName'       => $row->memName,
			'assort'        => $row->assort,
			'assortName'    => $assortName,
			'assortOptions' => $arrPointAssort,
			'descript'      => $row->descript,
			'point'         => number_format($row->point),
			'wdate'         => $row->wdate,
		);

	} else {
		$data = array(
			'idx'           => "",
			'memId'         => "",
			'memName'       => "",
			'assort'        => "",
			'assortName'    => $assortName,
			'assortOptions' => $arrPointAssort,
			'descript'      => "",
			'point'         => "",
			'wate'          => "",
		);
	}

	$response = array(
		'result' => "0",
		'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
