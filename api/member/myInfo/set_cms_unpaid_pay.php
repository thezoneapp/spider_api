<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/cms.php";
	include "../../../inc/kakaoTalk.php";

	/*
	* 회원 > 내정보 > 구독료 미납 > 납부 신청
	* parameter ==> userId: 회원아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId     = $input_data->{'userId'};
	
	//$userId = "a53179780";

	// 요일 배열
	$arrWeek = array("sun","mon","tue","wed","thu","fri","sat");
	$timestamp = strtotime("Now");
	$today = date("Ymd", $timestamp);
	//$time = date("H", $timestamp);

	//if ($time >= "17") {
	//	$timestamp = strtotime("+1 days");
	//	$today = date("Ymd", $timestamp);
	//}

	$week = $arrWeek[date('w', strtotime($today))];

	// 자동이체 출금신청일
	if ($week == "thu") { // 목요일
		$timestamp = strtotime("+4 days");
		$requestDate = date("Ymd", $timestamp);
	
	} else if ($week == "fri") { // 금요일
		$timestamp = strtotime("+3 days");
		$requestDate = date("Ymd", $timestamp);
	
	} else { // 토~수요일
		$timestamp = strtotime("+2 days");
		$requestDate = date("Ymd", $timestamp);
	}

	// 미납정보 목록
	$n = 0;
    $sql = "SELECT cp.idx, cp.payMonth, cp.payAmount, cp.commiAmount, c.paymentKind, m.groupCode, m.sponsId, m.memId, m.memName, m.memAssort, m.cmsId 
		    FROM cms_pay cp 
			     inner join member m on cp.memId = m.memId 
				 inner join cms c on cp.memId = c.memId 
		    WHERE cp.memId = '$userId' and (cp.requestStatus = '1' or cp.payStatus = '5') 
			ORDER BY cp.payMonth ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$payIdx       = $row[idx];
			$payMonth     = $row[payMonth];
			$payAmount    = $row[payAmount];
			$commiAmount  = $row[commiAmount];
			$paymentKind  = $row[paymentKind];
			$groupCode    = $row[groupCode];
			$sponsId      = $row[sponsId];
			$memId        = $row[memId];
			$memName      = $row[memName];
			$memAssort    = $row[memAssort];
			$cmsId        = $row[cmsId];

			// 출금신청 거래ID 및 출금일자
			++$n;
			$transactionId = date("ymd-His") . "-" . $n . "-" . $memId;

			if ($paymentKind == "CARD") $paymentDate = date("Ymd");
			else $paymentDate = $requestDate;

			// body
			$cms_body = Array(
				"memberId"      => $cmsId, 
				"transactionId" => $transactionId, 
				"paymentDate"   => $paymentDate, 
				"callAmount"    => $payAmount
			);

			// CMS 출금신청 함수
			$response = cmsPayRegist($paymentKind, $cms_body);
			$response = json_decode($response);

			$result_status  = $response->{'result'};
			$result_message = $response->{'message'};
			$requestStatus  = $response->{'requestStatus'};
			$payStatus      = $response->{'payStatus'};

			// 스폰서정보
			$sql = "SELECT memName FROM member WHERE memId = '$sponsId'";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);
			$sponsName = $row2->memName;

			// 출금신청내역 등록
			$sql = "INSERT INTO cms_pay (sponsId, sponsName, memId, memName, memAssort, transactionId, paymentKind, payMonth, payAmount, commiAmount, payMessage, requestStatus, payStatus, adminId, adminName, wdate) 
								 VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$memAssort', '$transactionId', '$paymentKind', '$payMonth', '$payAmount', '$commiAmount', '$result_message', '$requestStatus', '$payStatus', '$memId', '$memName', now())";
			$connect->query($sql);

			// 기존 신청 내역 삭제
			$sql = "DELETE FROM cms_pay WHERE idx = '$payIdx'";
			$connect->query($sql);

			// 구독 연체횟수를 알아본다.
			$sql = "SELECT ifnull(count(idx),0) AS count 
					FROM cms_pay 
					WHERE memId = '$memId' AND (requestStatus = '1' or payStatus = '5' )";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);
			$count = $row2->count;

			//if ($count > 2) $count = 2;

			// 회원상태 = 보류, 구독료납부 = 연체로 변경한다.
			$sql = "UPDATE member SET clearStatus = '$count' WHERE memId = '$memId'";
			$connect->query($sql);

			if ($result_status == "1") {
				// 알림톡 전송
				$hpNo = preg_replace('/\D+/', '', $hpNo);
				$receiptInfo = array(
					"memName"     => $memName,
					"cmsMessage"  => $message,
					"cmsAmount"   => number_format($payAmount),
					"receiptHpNo" => $hpNo,
				);
				sendTalk($groupCode, "C_002_1", $receiptInfo);
			}

			// 출금신청 로그등록
			$sql = "INSERT INTO cms_pay_log (memId, memName, payMonth, paymentKind, transactionId, payAmount, message, adminId, adminName, status, wdate)
									 VALUES ('$memId', '$memName', '$payMonth', '$paymentKind', '$transactionId', '$payAmount', '$result_message', '$memId', '$memName', '$result_status', now())";
			$connect->query($sql);
		}

		// 성공 결과를 반환합니다.
		if ($result_status == "0") $result_message = "납부신청이 완료되었습니다.";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "미납내역이 없습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>