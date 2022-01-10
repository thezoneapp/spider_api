<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 게시글 > 평점
	* parameter ==> memId:   회원ID
	* parameter ==> bbsCode: 게시판코드
	* parameter ==> idx:     글 idx
	* parameter ==> score:   평점
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId   = $input_data->{'memId'};
	$bbsCode = $input_data->{'bbsCode'};
	$idx     = $input_data->{'idx'};
	$score   = $input_data->{'score'};

	//$memId   = "a27233377";
	//$bbsCode = "N_01";
	//$idx     = "95";
	//$score   = "3";

	// 게시판 정보
	$sql = "INSERT INTO bbs_score (memId, bbsCode, bbsIdx, score, wdate) 
	                       VALUES ('$memId', '$bbsCode', '$idx', '$score', now())";
	$result = $connect->query($sql);

    if ($result) {
		$sql = "SELECT SUM(score) AS score, COUNT(idx) AS count 
				FROM bbs_score
				WHERE bbsCode= '$bbsCode' and bbsIdx = '$idx'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);
		$score = $row->score;
		$count = $row->count;

		$avg = round($score / $count);

		$sql = "UPDATE bbs SET score = '$avg' WHERE bbsCode= '$bbsCode' and idx = '$idx'";
		$connect->query($sql);

		$result_status = "0";
		$result_message = "평점이 등록되었습니다.";

	} else {
		$result_status = "1";
		$result_message = "오류가 발생되었습니다.";
	}

	$response = array(
		'result'     => $result_status,
		'message'    => $result_message,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>