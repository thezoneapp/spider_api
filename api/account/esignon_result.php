<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    
	/*
	* 관리자정보
	* parameter ==> user_id: 회원ID
	* parameter ==> user_pw: 회원PW
	*/
	$response = json_decode(file_get_contents('php://input'));
	$body = $response->{'body'};
	$status = $body->{'status'};
	$memId = $body->{'clientid'};
	$contractDoc = $body->{'download_url'};

	//error_log ($status . $memId, 3, "/home/spiderfla/upload/doc/debug.log");

	// 실행할 쿼리를 작성합니다.
	if ($status == "Complete") {
		$status = "9";
		$sql = "UPDATE member SET contractStatus = '$status', 
		                          contractDoc = '$contractDoc', 
					              contractDate = now()
				WHERE memId = '$memId'";
		$result = $connect->query($sql);

	} else if ($status == "Playing") {
		$status = "1";
 		$sql = "UPDATE member SET contractStatus = '$status' WHERE memId = '$memId'";
		$result = $connect->query($sql);
	}

	// 회원정보
    $sql = "SELECT idx, memName, hpNo FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

		$idx     = $row->idx;
		$memName = $row->memName;
		$hpNo    = $row->hpNo;

		// 알림톡 전송
		$hpNo = preg_replace('/\D+/', '', $hpNo);
		$receiptInfo = array(
			"memId"       => $memId,
			"memName"     => $memName,
			"receiptHpNo" => $hpNo
		);
		sendTalk("J_01_01", $receiptInfo);
	}

	// member log table에 등록한다.
	$sql = "INSERT INTO member_log (memIdx, adminId, adminName, logAssort, status, wdate)
	                        VALUES ('$idx', '$memId', '$memName', 'E', '$status', now())";
	$connect->query($sql);

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);

	exit;
?>