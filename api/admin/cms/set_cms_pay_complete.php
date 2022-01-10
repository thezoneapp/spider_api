<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* CMS관리 > 출금관리 > 무통장입금완료
	* parameter ==> adminId: 관리자ID
	* parameter ==> idx:     일련번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$adminId   = $input_data->{'adminId'};
	$adminName = $input_data->{'adminName'};
	$idx       = $input_data->{'idx'};

	$requestStatus = "0";
	$payStatus = "9";
	$payMessage = "무통장입금";

	$sql = "UPDATE cms_pay SET requestStatus = '$requestStatus', 
		                       payStatus = '$payStatus', 
						       payMessage = '$payMessage'
				WHERE idx = '$idx'";
	$connect->query($sql);

	// 출금신청정보
	$sql = "SELECT sponsId, sponsName, memId, memName, memAssort, payMonth, payAmount, commiAmount 
			FROM cms_pay 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$sponsId = $row->sponsId;
	$sponsName = $row->sponsName;
	$memId = $row->memId;
	$memName = $row->memName;
	$memAssort = $row->memAssort;
	$payMonth = $row->payMonth;
	$payAmount = $row->payAmount;
	$commiAmount = $row->commiAmount;

	// 구독 연체횟수를 알아본다.
	$sql = "SELECT ifnull(count(idx),0) AS count 
			FROM cms_pay 
			WHERE memId = '$memId' AND (requestStatus = '1' or payStatus = '5' )";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$count = $row->count;

	if ($count > 2) $count = 2;

	if ($count == 0) $memStatus = "9";
	else $memStatus = "2";

	// 회원상태 = 보류, 구독료납부 = 연체로 변경한다.
	$sql = "UPDATE member SET memStatus = '$memStatus', clearStatus = '$count' WHERE memId = '$memId'";
	$connect->query($sql);

	// 스폰서 CMS납부 상태 체크
	$sql = "SELECT clearStatus FROM member WHERE memId = '$sponsId'";
	$result2 = $connect->query($sql);
	$row2 = mysqli_fetch_object($result2);

	if ($row2->clearStatus == "0") $clearStatus = "N"; // 정상
	else $clearStatus = "Y"; // 보류

	// 스폰서에게 구독수수료 등록
	if ($memAssort == "M") {
		$commiAssort = "MA";
		$salesAssort = "PA";

	} else {
		$commiAssort = "MS";
		$salesAssort = "PS";
	}

	$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, assort, price, clearStatus, wdate) 
							VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$commiAssort', '$commiAmount', '$clearStatus', now())";
	$connect->query($sql);

	// 매출(CMS납부) 등록
	$sql = "INSERT INTO sales (sponsId, sponsName, memId, memName, assort, price, transactionId, wdate) 
						VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$salesAssort', '$payAmount', '$transactionId', now())";
	$connect->query($sql);

	// 출금신청 로그등록
	$paymentKind = "CMS";
	$status = "0";
	$sql = "INSERT INTO cms_pay_log (memId, memName, payMonth, paymentKind, payAmount, message, adminId, adminName, status, wdate)
						     VALUES ('$memId', '$memName', '$payMonth', '$paymentKind', '$payAmount', '$payMessage', '$adminId', '$adminName', '$status', now())";
	$connect->query($sql);

	// 변경 결과를 반환합니다.
	$result_status = "0";
	$result_message = "'무통장입금처리'를 완료하였습니다.";

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>