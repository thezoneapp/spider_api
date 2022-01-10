<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 게시판 정보 삭제
	* parameter ==> idx: 일련번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$arrIdx = $input_data->{'idx'};

	$idx = "";

	for ($i = 0; count($arrIdx) > $i; $i++) {
		if ($idx != "") $idx .= ",";

		$idx .= "'" . $arrIdx[$i] . "'";
	}
//$idx = ['5'];
    $sql = "SELECT idx, bbsCode FROM bbs_manager WHERE idx IN ($idx)";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$idx     = $row[idx];
			$bbsCode = $row[bbsCode];

			$sql = "DELETE FROM bbs WHERE bbsCode = '$bbsCode'";
			$connect->query($sql);

			$sql = "DELETE FROM bbs_manager WHERE idx = '$idx'";
			$connect->query($sql);
		}

		$result_status = "0";
		$result_message = "'삭제'되었습니다.";

	} else {
		$result_status = "1";
		$result_message = "존재하지 않는 자료입니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>