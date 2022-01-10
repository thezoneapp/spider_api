<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 게시글 > 댓글 > 추가/수정
	* parameter ==> mode:      insert(추가), update(수정)
	* parameter ==> parentIdx: 부모글 idx
	* parameter ==> idx:       게시글 idx
	* parameter ==> memId:     작성자ID
	* parameter ==> memName:   작성자명
	* parameter ==> content:   내용
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$mode      = $input_data->{'mode'};
	$parentIdx = $input_data->{'parentIdx'};
	$idx       = $input_data->{'idx'};
	$memId     = $input_data->{'memId'};
	$memName   = $input_data->{'memName'};
	$content   = $input_data->{'content'};

	$subject = str_replace("'", "＇", $subject);
	$content = str_replace("'", "＇", $content);

	// 부모글 정보
	$sql = "SELECT memId, bbsCode, subject FROM bbs WHERE idx = '$parentIdx'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$writeId = $row->memId;
		$bbsCode = $row->bbsCode;
		$subject = "[Re] " . $row->subject;

		if ($mode == "insert") {
			// 댓글 등록
			$sql = "INSERT INTO bbs (parentIdx, bbsCode, memId, memName, subject, content, wdate)
							 VALUES ('$parentIdx', '$bbsCode', '$memId', '$memName', '$subject', '$content', now())";
			$connect->query($sql);

			// 댓글 카운트 증가
			$sql = "UPDATE bbs SET replyCount = replyCount + 1 WHERE idx = '$parentIdx'";
			$connect->query($sql);

			// 1:1문의글이면 SMS

			$result_status = "0";
			$result_message = "등록하였습니다.";

		} else {
			$sql = "UPDATE bbs SET content = '$content' WHERE idx = '$idx'";
			$connect->query($sql);

			// 성공 결과를 반환합니다.
			$result_status = "0";
			$result_message = "변경하였습니다.";
		}
	
		if ($bbsCode == "Q_01") {
			$sql = "DELETE FROM bbs_view WHERE memId = '$writeId' and bbsIdx = '$parentIdx'";
			$connect->query($sql);
		}

	} else {
		$result_status = "1";
		$result_message = "부모글이 존재하지 않습니다.";
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>