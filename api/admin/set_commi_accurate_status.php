<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 정산서 상태변경
	* parameter ==> idx:    정산서 idx
	* parameter ==> status: 변경될 상태값 (0: 정산대기, 2:정산보류, 9:정산완료)
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$idx        = $input_data->{'idx'};
	$status     = $input_data->{'status'};

	// 정산서 삭제
	if ($status == "0") {
		// 매입원장 목록의 해당 정산대금을 삭제
		$sql = "DELETE FROM commi_account WHERE accurateIdx = '$idx'";
		$connect->query($sql);

		// 수수료목록의 정산상태를 "정산대기"로 변경
		$sql = "UPDATE commission SET accurateIdx = null, accurateStatus = '$status' WHERE accurateIdx = '$idx'";
		$connect->query($sql);

		// 정산서 세부내용을 삭제
		$sql = "DELETE FROM commi_accurate_detail WHERE accurateIdx = '$idx'";
		$connect->query($sql);

		// 정산서 삭제
		$sql = "DELETE FROM commi_accurate WHERE idx = '$idx'";
		$connect->query($sql);

		$result_message = "정산서를 삭제하였습니다.";

	// 정산보류
	} else if ($status == "2") {
		// 매입원장 목록의 해당 정산대금을 삭제
		$sql = "DELETE FROM commi_account WHERE accurateIdx = '$idx'";
		$connect->query($sql);

		// 수수료목록의 정산상태를 "정산보류"로 변경
		$sql = "UPDATE commission SET accurateStatus = '$status' WHERE accurateIdx = '$idx'";
		$connect->query($sql);

		// 정산서의 정산상태를 "정산보류"로 변경
		$sql = "UPDATE commi_accurate SET accurateStatus = '$status' WHERE idx = '$idx'";
		$connect->query($sql);

		$result_message = "정산이 보류되였습니다.";

	// 정산완료
	} else if ($status == "9") {
		// 수수료목록의 정산상태를 "정산완료"로 변경
		$sql = "UPDATE commission SET accurateStatus = '$status' WHERE accurateIdx = '$idx'";
		$connect->query($sql);

		// 정산서의 정산상태를 "정산완료"로 변경
		$sql = "UPDATE commi_accurate SET accurateStatus = '$status' WHERE idx = '$idx'";
		$connect->query($sql);

		// 매입원장 목록의 동일한 정산번호를 삭제
		$sql = "DELETE FROM commi_account WHERE accurateIdx = '$idx'";
		$connect->query($sql);

		// 정산정보
		$sql = "SELECT memId, memName, accurateAmount, wdate FROM commi_accurate WHERE idx = '$idx'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);
		$memId         = $row->memId;
		$memName       = $row->memName;
		$accountAmount = $row->accurateAmount;
		$accurateDate  = $row->wdate;

		// 매입원장 목록에 추가
		$assort = "I";
		$sql = "INSERT INTO commi_account (memId, memName, assort, accurateIdx, accurateDate, accountAmount, wdate)
								   VALUES ('$memId', '$memName', '$assort', '$idx', '$accurateDate', '$accountAmount', now())";
		$connect->query($sql);

		$result_message = "정산이 완료되였습니다.";
	}

	$response = array(
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>