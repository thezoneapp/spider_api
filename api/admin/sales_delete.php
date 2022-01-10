<?
	include "../../../inc/common.php";
    include '../../util/response.php';

	/*
	* 매출원장 삭제
	* parameter ==> chk:       선택된 값
	*/

	$data_back = json_decode(file_get_contents('php://input'));

	$idx = $data_back->{'idx'};
	$idx = implode($idx,',');

	$sql = "DELETE FROM sales WHERE idx in($idx)";
	$result = $connect->query($sql);

	if ($result === true) {
		// 성공 결과를 반환합니다.
		$response = array(
			'result'    => "0",
			'message'   => "삭제하였습니다."
		);

	} else {
		// 실패 결과를 반환합니다.
		$response = array(
			'result'    => "1",
			'message'   => "삭제에 실패하였습니다."
		);
	}

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>