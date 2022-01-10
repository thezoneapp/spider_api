<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 회원 정보
	* parameter ==> memId: 회원 아이디
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};

    $sql = "SELECT recommandId, memId, memName, memAssort, hpNo 
	        FROM member 
			WHERE memId = '$userId'";

	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$memAssort = $row->memAssort === "M" ? "플랫폼MD(대리점)" : "온라인구독플랫폼(판매점)";

		if ($row->registNo !== "") $row->registNo = aes_decode($row->registNo);
		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

		$data = array(
			'recommandId'  => $row->recommandId,
			'memId'     => $row->memId,
			'memName'   => $row->memName,
			'memAssort' => $memAssort,
			'hpNo'      => $row->hpNo,
			'kindOptions' => $arrPayKind,
			'bankOptions' => $arrBankCode,
		);

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result = "1";
	}

	$response = array(
		'result'    => $result,
		'message'   => $result_message,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>