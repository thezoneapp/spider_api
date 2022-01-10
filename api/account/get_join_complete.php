<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/kakaoTalk.php";
	include "../../inc/memberStatusUpdate.php";

	/*
	* 회원가입완료
	* parameter ==> userId: 회원ID
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$userId = $data_back->{'userId'};
	//$userId = "a27233377";

	// 회원정보
	$data = Array();
	$sql = "SELECT idx, memId, memName, hpNo, groupCode, memAssort FROM member WHERE memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$idx       = $row->idx;
		$memId     = $row->memId;
		$memName   = $row->memName;
		$hpNo      = $row->hpNo;
		$groupCode = $row->groupCode;
		$memAssort = $row->memAssort;

		if ($hpNo != "") $hpNo = aes_decode($hpNo);

		// 승인완료처리한다.
		$status = "9";
		$result_approval = memberApprovalProc($idx, $status);

		$arrResult = explode("|", $result_approval);
		$result_status  = $arrResult[0];
		$result_message = $arrResult[1];

		// 가입비 완료 안내글
		if ($memAssort == "M") $code = "joinCompleteMd";
		else $code = "joinCompleteOp";

		$sql = "SELECT content FROM setting WHERE code = '$code'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		$data = array(
			'content' => $row->content
		);

		// MD 가입비 정보
		if ($memAssort == "M") {
			$code = "subsA";
			$sql = "SELECT content FROM setting WHERE code = '$code'";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);

			if ($row->content == "" || $row->content == null) $joinAmount = 0;
			else $joinAmount = $row->content;

			// 알림톡 전송
			$hpNo = preg_replace('/\D+/', '', $hpNo);
			$receiptInfo = array(
				"memId"       => $memId,
				"memName"     => $memName,
				"joinAmount"  => number_format($joinAmount),
				"receiptHpNo" => $hpNo,
			);
			sendTalk($groupCode, "J_04_01", $receiptInfo);
		}

    } else {
		// 실패 결과를 반환합니다.
		$result = "1";
		$result_message = "존재하지 않는 회원입니다.";
	}

	$response = array(
		'result'    => $result,
		'message'   => $result_message,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>