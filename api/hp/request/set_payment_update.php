<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰신청 > 입금정보 > 삭제예정
	* parameter ==> idx  :         신청서 일련번호
	* parameter ==> commission:    수수료
	* parameter ==> bankName:      입금은행
	* parameter ==> accountNo:     계좌번호
	* parameter ==> accountName:   예금주명
	* parameter ==> accountDate:   입금일자
	* parameter ==> accountStatus: 입금상태
	* parameter ==> adminMemo:     관리자메모
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$idx           = $input_data->{'idx'};
	$memId         = $input_data->{'memId'};
	$commission    = $input_data->{'commission'};
	$bankName      = $input_data->{'bankName'};
	$accountNo     = $input_data->{'accountNo'};
	$accountName   = $input_data->{'accountName'};
	$accountDate   = $input_data->{'accountDate'};
	$accountStatus = $input_data->{'accountStatus'};
	$comment       = $input_data->{'comment'};
	$statusMemo    = $input_data->{'statusMemo'};
	$adminMemo     = $input_data->{'adminMemo'};

	$accountStatus = $accountStatus->{'code'};

	if ($accountDate != "") $accountDate = str_replace(".", "-", $accountDate);
	else $accountDate =  date("Y-m-d");

	if ($commission == "" || $commission == null) $commission = "0";
	if ($accountNo != "") $accountNo = aes128encrypt($accountNo);

	$sql = "UPDATE hp_request SET commission = '$commission', 
	                              bankName = '$bankName', 
								  accountNo = '$accountNo', 
								  accountName = '$accountName', 
								  accountDate = '$accountDate', 
								  accountStatus = '$accountStatus', 
								  adminMemo = '$adminMemo'
			WHERE idx = '$idx'";
	$connect->query($sql);

	// 수수료(휴대폰신청) 처리
	if ($accountStatus == "9") { // 입금완료
		// 회원정보
		$sql = "SELECT memName FROM member WHERE memId = '$memId'";
    	$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);
		$memName = $row->memName;

		// 기존 등록여부 체크
		$sql = "SELECT idx FROM commission WHERE hpRequestIdx = '$idx'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$sql = "UPDATE commission SET price = '$commission', 
										  wdate = '$accountDate' 
			        WHERE hpRequestIdx = '$idx'";
			$connect->query($sql);

		} else {
			// 수수료(휴대폰신청) 등록
			$assort = "H1";
			$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, assort, price, hpRequestIdx, wdate) 
									VALUES ('$memId', '$memName', '$memId', '$memName', '$assort', '$commission', '$idx', '$accountDate')";
			$connect->query($sql);
		}

	} else {
		$sql = "DELETE FROM commission WHERE hpRequestIdx = '$idx'";
		$connect->query($sql);
	}

	$result_status = "0";
	$result_message = "저장되었습니다.";

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>