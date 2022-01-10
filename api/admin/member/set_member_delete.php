<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 회원 정보 삭제
	* parameter ==> idx: 회원 일련번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

	// 회원 CMS상태 체크
    $sql = "SELECT memId, cmsStatus FROM member WHERE idx = '$idx'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$memId     = $row->memId;
	$cmsStatus = $row->cmsStatus;

	if ($cmsStatus == "0" || $cmsStatus == "8") {
		// CMS 정보 삭제
		$sql = "DELETE FROM cms WHERE memId = '$memId'";
		$connect->query($sql);

		// 회원정보 삭제
		$sql = "DELETE FROM member WHERE idx = '$idx'";
		$connect->query($sql);

		$result_ok = "0";
		$result_message = "'삭제'되었습니다.";

	} else {
		$result_ok = "1";
		$result_message = "'CMS'를 해지해주세요.";
	}

	$response = array(
		'result'  => $result_ok,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>