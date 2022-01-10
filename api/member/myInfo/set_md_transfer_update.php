<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/memberStatusUpdate.php";
	include "../../../inc/kakaoTalk.php";

	/*
	* 내정보 > MD전환신청
	* parameter
		userId: 회원아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId     = $input_data->{'userId'};
	
	//$userId = "a27233377";

	// 월구독료
	$cmsAmount = 0;
	$commiAmount = 0;
	$sql = "SELECT code, content FROM setting WHERE assort = 'V' and code IN ('payA', 'commiMA') ORDER by code asc";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[code] == "payA") $cmsAmount = $row[content];
			else if ($row[code] == "commiMA") $commiAmount = $row[content];
		}
	}

	// 회원구분을 MD로 변경한다.
	$sql = "UPDATE member SET memAssort = 'M', memStatus = '0' WHERE memId = '$userId'";
	$connect->query($sql);

	// CMS 납부금액을 변경한다.
	$sql = "UPDATE cms SET cmsAmount = '$cmsAmount', commiAmount = '$commiAmount' WHERE memId = '$userId'";
	$connect->query($sql);

	// 회원정보
	$sql = "SELECT idx, memId, memName FROM member WHERE memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$idx     = $row->idx;
		$memId   = $row->memId;
		$memName = $row->memName;

		// 레그를 구성한다.
		$status = "9";
		$result_approval = memberApproval($idx, $status);

		$arrResult = explode("|", $result_approval);
		$result_status  = $arrResult[0];
		$result_message = $arrResult[1];

		// member log table에 등록한다.
		$sql = "INSERT INTO member_log (memIdx, adminId, adminName, logAssort, status, wdate)
								VALUES ('$idx', '$memId', '$memName', 'T', '$status', now())";
		$connect->query($sql);

	} else {
		$result_status = "1";
		$result_message = "등록되지 않은 회원입니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>