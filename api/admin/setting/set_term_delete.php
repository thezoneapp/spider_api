<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 추가/수정
	* parameter ==> chk:       선택된 값
	*/

	$data_back = json_decode(file_get_contents('php://input'));

	$idx = $data_back->{'idx'};
	$idx = implode($idx,',');

	$sql = "DELETE FROM admin WHERE idx in($idx)";
	$result = $connect->query($sql);

	if ($result === true) {
		// 성공 결과를 반환합니다.
		$result = "0";
		$result_message = "삭제하였습니다.";

	} else {
		// 실패 결과를 반환합니다.
		$result = "1";
		$result_message = "삭제에 실패하였습니다.";
	}

	$response = array(
		'error'     => $result,
		'message'   => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>