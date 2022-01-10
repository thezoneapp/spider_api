<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 댓글 삭제
	* parameter ==> idx: 일련번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$parentIdx = $input_data->{'parentIdx'};
	$idx       = $input_data->{'idx'};

	$sql = "DELETE FROM bbs WHERE idx = '$idx'";
	$connect->query($sql);

	$sql = "UPDATE bbs SET replyCount = replyCount - 1 WHERE idx = '$parentIdx'";
	$connect->query($sql);

	$result_status = "0";
	$result_message = "'삭제'되었습니다.";

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>