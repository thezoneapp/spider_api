<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/kakaoTalk.php";

	/*
	* 인증번호 전송
	* parameter
		assort:   인증구분(I: ID찾기, P: 비밀번호찾기, A: 기타)
		userId:   회원ID
		userName: 회원명
		hpNo:     휴대폰번호
	*/
	$back_data = json_decode(file_get_contents('php://input'));
	$assort    = trim($back_data->{'assort'});
	$userId    = trim($back_data->{'userId'});
	$userName  = trim($back_data->{'userName'});
	$hpNo      = trim($back_data->{'hpNo'});

	//$assort = "I";
	//$memId = "a27233377";
	//$userName = "박태수";
	//$hpNo = "010-2723-3377";

	if ($assort == "I" || $assort == "P") {
		if ($hpNo != "") $hpNo = aes128encrypt($hpNo);

		// 실행할 쿼리를 작성합니다.
		if ($assort == "I") $sql = "SELECT memId, memName, hpNo, groupCode FROM member WHERE memName = '$userName' and hpNo = '$hpNo'";
		else if ($assort == "P") $sql = "SELECT memId, memName, hpNo, groupCode FROM member WHERE memId = '$userId' and hpNo = '$hpNo'";

		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);

			if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

			$groupCode = $row->groupCode;

			$certifyNo = mt_rand(100000, 999999);

			// 알림톡 전송
			$hpNo = preg_replace('/\D+/', '', $row->hpNo);
			$receiptInfo = array(
				"memId"       => $row->memId,
				"memName"     => $row->memName,
				"certifyNo"   => $certifyNo,
				"receiptHpNo" => $hpNo,
			);
			sendTalk($groupCode, "M_05_01", $receiptInfo);

			// 결과를 반환합니다.
			$result_status = "0";
			$result_message = "휴대폰번호로 '인증번호'가 전송되었습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "해당하는 회원이 존재하지 않습니다.";
		}

	} else {
		$certifyNo = mt_rand(100000, 999999);

		// 알림톡 전송
		$hpNo = preg_replace('/\D+/', '', $hpNo);
		$receiptInfo = array(
			"memName"     => $memName,
			"certifyNo"   => $certifyNo,
			"receiptHpNo" => $hpNo,
		);
		sendTalk("spider", "M_05_01", $receiptInfo);

		// 결과를 반환합니다.
		$result_status = "0";
		$result_message = "휴대폰번호로 '인증번호'가 전송되었습니다.";
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
		'certifyNo' => $certifyNo,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>