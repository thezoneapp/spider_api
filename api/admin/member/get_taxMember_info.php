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

    $sql = "SELECT tm.idx, tm.memId, m.memName, tm.corpNum, tm.corpName, tm.ceoName, tm.juminNum, tm.bizType, tm.bizClass, tm.postNum, tm.addr1, tm.addr2, 
	               tm.staffName, tm.hpNo, tm.email, tm.baroId, tm.baroPw, tm.certifyStatus, tm.expireDate, date_format(tm.wdate, '%Y/%m/%d') as wdate 
		    FROM tax_member tm 
				inner join member m on tm.memId = m.memId 
			WHERE tm.idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->juminNum !== "") $row->juminNum = aes_decode($row->juminNum);
		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);
		if ($row->email !== "") $row->email = aes_decode($row->email);

		$certifyName = selected_object($row->certifyStatus, $arrYesNo);

		$data = array(
			'idx'           => $row->idx,
			'memId'         => $row->memId,
			'memName'       => $row->memName,
			'corpName'      => $row->corpName,
			'corpNum'       => $row->corpNum,
			'ceoName'       => $row->ceoName,
			'juminNum'      => $row->juminNum,
			'bizType'       => $row->bizType,
			'bizClass'      => $row->bizClass,
			'postNum'       => $row->postNum,
			'addr1'         => $row->addr1,
			'addr2'         => $row->addr2,
			'staffName'     => $row->staffName,
			'hpNo'          => $row->hpNo,
			'email'         => $row->email,
			'baroId'        => $row->baroId,
			'baroPw'        => $row->baroPw,
			'certifyStatus' => $certifyName,
			'expireDate'    => $row->expireDate,
			'wdate'         => $row->wdate,
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
