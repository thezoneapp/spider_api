<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 회원 탈퇴신청
	* parameter ==> memId:     회원ID
	* parameter ==> outReason: 탈퇴사유
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId      = trim($input_data->{'memId'});
	$outReason  = $input_data->{'outReason'};

	$status = "7";

	// 1. 회원 아이디가 있나 체크
	$sql = "SELECT idx, memName FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$row = mysqli_fetch_object($result);

		$idx     = $row->idx;
		$memName = $row->memName;

		$sql = "UPDATE member SET memStatus = '$status',
		                          outReason = '$outReason'
				WHERE memId = '$memId'";
		$connect->query($sql);

		// member log table에 등록한다.
		$sql = "INSERT INTO member_log (memIdx, adminId, adminName, logAssort, status, wdate)
		                        VALUES ('$idx', '$memId', '$memName', 'A', '$status', now())";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "'틸퇴신청'이 접수되었습니다.";

	} else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "존재하지 않는 '회원'입니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>