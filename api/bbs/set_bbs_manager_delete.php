<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../utils/utility.php";

	/*
	* 게시판 정보 삭제
	* parameter ==> idx: 일련번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT bbsCode FROM bbs_manager WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$bbsCode = $row->bbsCode;

		$sql = "DELETE FROM bbs WHERE bbsCode = '$bbsCode'";
		$connect->query($sql);

		$sql = "DELETE FROM bbs_manager WHERE idx = '$idx'";
		$connect->query($sql);

		$result_ok = "0";
		$result_message = "'삭제'되었습니다.";

	} else {
		$result_ok = "1";
		$result_message = "존재하지 않는 자료입니다.";
	}

	$response = array(
		'result'  => $result_ok,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>