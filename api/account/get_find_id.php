<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/kakaoTalk.php";

	/*
	* 아이디 찾기
	* api/account/get_find_id.php
	* parameter ==> userName: 회원명
	* parameter ==> hpNo:     휴대폰번호
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$userName = trim($data_back->{'userName'});
	$hpNo     = trim($data_back->{'hpNo'});

	//$useName = "박태수";
	//$hpNo = "010-2723-3377";

	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);

	// 실행할 쿼리를 작성합니다.
    $sql = "SELECT memId, memName, hpNo, groupCode 
            FROM member
			WHERE memName = '$userName' and hpNo = '$hpNo'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

		$groupCode = $row->groupCode;

		// 알림톡 전송
		$hpNo = preg_replace('/\D+/', '', $row->hpNo);
		$receiptInfo = array(
			"memId"       => $row->memId,
			"memName"     => $row->memName,
			"receiptHpNo" => $hpNo
		);
		sendTalk($groupCode, "M_01_01", $receiptInfo);

		// 결과를 반환합니다.
		$result_status = "0";
		$result_message = "휴대폰번호로 '아이디'가 전송되었습니다.";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "정보가 일치하지 않습니다.\n다시 입력해 주세요.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>