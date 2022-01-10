<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 그룹관리 > 그룹 > 목록 > 삭제
	* parameter ==> idx: 그룹 일련번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$arrIdx = $input_data->{'idx'};

	$idx = "";
	for ($i = 0; count($arrIdx) > $i; $i++) {
		if ($idx != "") $idx .= ",";

		$idx .= "'" . $arrIdx[$i] . "'";
	}

    $sql = "SELECT groupCode FROM group_info WHERE idx IN ($idx)";
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		$groupCode = $row[groupCode];

		// 구성 
		$sql = "DELETE FROM group_organize WHERE groupCode = '$groupCode'";
		$connect->query($sql);

		// 구성 > 서비스이용
		$sql = "DELETE FROM group_organize_service WHERE groupCode = '$groupCode'";
		$connect->query($sql);

		// 구성 > 후원보너스
		$sql = "DELETE FROM group_organize_bonus WHERE groupCode = '$groupCode'";
		$connect->query($sql);

		// 그룹정보
		$sql = "DELETE FROM group_info WHERE groupCode = '$groupCode'";
		$connect->query($sql);
	}

	// 성공 결과를 반환합니다.
	$response = array(
		'result'    => "0",
		'message'   => "삭제하였습니다."
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>