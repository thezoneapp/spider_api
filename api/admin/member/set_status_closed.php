<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* LOS 재구성 추가/수정
	* parameter ==> memId: 회원ID
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$adminId   = $data_back->{'adminId'};
	$adminName = $data_back->{'adminName'};
	$closedId  = $data_back->{'closedId'};
	$memId     = $data_back->{'memId'};

	//$closedId = "a89840857";
	//$memId    = "a89840857";

	// 회원 정보
	$sql = "SELECT sponsId, recommandId FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$sponsId = $row->sponsId;
	$recommandId = $row->recommandId;

	// 스폰서ID = 추천인ID : 본인만 해지완료처리하고 한단계 아래에 해당하는 회원만 본사로 귀속.
	if ($sponsId == $recommandId && $memId == $closedId) {
		// 스폰서를 본사로 귀속
		$sponsId = "dream";
		$sql = "UPDATE member SET sponsId = '$sponsId' WHERE sponsId = '$closedId' and recommandId = '$closedId'";
		$connect->query($sql);

		// 회원상태 > 해지완료로 변경
		$sql = "UPDATE member SET memStatus = '8' WHERE memId = '$closedId'";
		$connect->query($sql);

		$sql = "SELECT idx FROM member WHERE memId = '$closedId'";
		$result2 = $connect->query($sql);
		$row2 = mysqli_fetch_object($result2);
		$memIdx = $row2->idx;

		// member log table에 등록한다.
		$sql = "INSERT INTO member_log (memIdx, adminId, adminName, logAssort, status, wdate)
								VALUES ('$memIdx', '$adminId', '$adminName', 'A', '8', now())";
		$connect->query($sql);

		$result_status = "0";
		$result_message = "탈퇴가 완료되었습니다.";

	// 스폰서ID <> 추천인ID : 아래로 하향하며 첫번째 회원을 해지하려는 회원의 스폰서ID로 변경한다.
	} else {
		$sql = "SELECT idx, sponsId, memId FROM member WHERE sponsId = '$sponsId' and recommandId = '$sponsId' and memStatus = '9' ORDER BY idx ASC LIMIT 1";
		$result = $connect->query($sql);

		if ($result->num_rows > 1) {
			$row = mysqli_fetch_object($result);

			$idx     = $row->idx;
			$memId   = $row->memId;

			// 첫번째 회원을 스폰서에게 올려준다.
			$sql = "UPDATE member SET sponsId = '$sponsId' WHERE idx = '$idx'";
			$connect->query($sql);

			$result_status = "1";
			$result_message = "하위 Depth가 존재합니다.";

		} else {
			// 회원상태 > 해지완료로 변경
			$sql = "UPDATE member SET memStatus = '8', 
			                          sponsId = null, 
									  memPw = null,
                                      registNo = null,
									  hpNo = null,
									  email = null,
                                      photo = null,
                                      taxRegistNo = null,
                                      accountName = null,
									  accountNo = null,
									  accountBank = null 
						WHERE memId = '$closedId'";
			$connect->query($sql);

			$sql = "SELECT idx FROM member WHERE memId = '$closedId'";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);
			$memIdx = $row2->idx;

			// member log table에 등록한다.
			$sql = "INSERT INTO member_log (memIdx, adminId, adminName, logAssort, status, wdate)
									VALUES ('$memIdx', '$adminId', '$adminName', 'A', '8', now())";
			$connect->query($sql);

			$result_status = "0";
			$result_message = "탈퇴가 완료되었습니다.";
		}
	}

	$response = array(
		'result'  => $result_status,
		'memId'   => $memId,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>