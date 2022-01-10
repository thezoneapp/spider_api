<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 환경설정 > 관리자관리 > 삭제
	* parameter
		idx: idx 배열
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$arrIdx = $input_data->{'idx'};

	for ($i = 0; count($arrIdx) > $i; $i++) {
		$idx = $arrIdx[$i];

		$sql = "SELECT id AS adminId FROM admin WHERE idx = '$idx'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);
			$adminId = $row->adminId;

			$sql = "DELETE FROM admin_menu WHERE adminId = '$adminId'";
			$connect->query($sql);
		}

		$sql = "DELETE FROM admin WHERE idx = '$idx'";
		$connect->query($sql);
	}

	// 성공 결과를 반환합니다.
	$result_status = "0";
	$result_message = "삭제하였습니다.";

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>