<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 수수료대금 세부 정보
	* parameter ==> idx: 정산서 idx
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, memId, memName, assort, accurateIdx, accurateDate, accountAmount, accountDate, bankName, accountNo, accountName, wdate 
	        FROM commi_account 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$assortName = selected_object($row->assort, $arrAccountAssort);

		if ($row->bankName == null) $row->bankName = "";
		if ($row->accountNo == null) $row->accountNo = "";
		if ($row->accountName == null) $row->accountName = "";
		if ($row->accountDate == null) $row->accountDate = "";

		if ($row->accountNo !== "") $row->accountNo = aes_decode($row->accountNo);

		$data = array(
			'idx'           => $row->idx,
			'memId'         => $row->memId,
			'memName'       => $row->memName,
			'assort'        => $row->assort,
			'assortName'    => $assortName,
			'accurateIdx'   => $row->accurateIdx,
			'accurateDate'  => $row->accurateDate,
			'accountAmount' => $row->accountAmount,
			'accountDate'   => $row->accountDate,
			'bankName'      => $row->bankName,
			'accountNo'     => $row->accountNo,
			'accountName'   => $row->accountName,
			'wdate'         => $row->wdate,
		);

		// 업데이트 모드로 결과를 반환합니다.
		$result = "0";

	} else {
		$data = array(
			'idx'           => "",
			'memId'         => "",
			'memName'       => "",
			'assort'        => "",
			'assortName'    => "",
			'accurateIdx'   => "",
			'accurateDate'  => "",
			'accountAmount' => "",
			'accountDate'   => "",
			'bankName'      => "",
			'accountNo'     => "",
			'accountName'   => "",
			'wdate'         => "",
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
