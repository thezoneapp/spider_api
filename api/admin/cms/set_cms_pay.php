<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/kakaoTalk.php";

	// *********************************************************************************************************************************
	// *                                                   CMS 출금신청                                                                 *
	// *********************************************************************************************************************************
	/*
	* parameter
	*	admind:  관리자ID
	*	memId:   회원ID
	*	payDate: 출금일자
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId     = $input_data->{'memId'};
	$adminId   = $input_data->{'adminId'};
	$payMonth  = $input_data->{'payMonth'};
	$payDate   = $input_data->{'payDate'};

	//error_log ($memId . ", " . $payDate . "::::", 3, "/home/spiderfla/upload/doc/debug.log");

	//$memId     = "a71848084";
	//$adminId   = "admin";
	//$payDate   = "2021-06-30";
	//$payMonth  = "2021-06";

	if ($memId == "" || $payDate == "") exit;
	if ($payMonth == null) $payMonth = substr($payDate, 0,7);

	$paymentDate = str_replace("-","",$payDate); // 출금신청일

	// 관리자 정보
	$sql = "SELECT name FROM admin WHERE id = '$adminId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$adminName = $row->name;

	// 해당월에 해당하는 출금신청 오류자료 삭제
	$sql = "DELETE FROM cms_pay WHERE memId = '$memId' and payMonth = '$payMonth' and (requestStatus != '0' or payStatus = '5')";
	$connect->query($sql);

	// 출금신청내역 등록
	$yearMonth = date("Ym");
	//$sql = "SELECT memId FROM cms_pay WHERE memId = '$memId' and payMonth = '$payMonth' and requestStatus = '0' and payStatus != '5'";
	$sql = "SELECT memId FROM cms_pay WHERE memId = '$memId' and payMonth = '$payMonth'";
	$result = $connect->query($sql);

    if ($result->num_rows == 0) {
		// 출금할 회원정보
		$sql = "SELECT m.groupCode, m.sponsId, m.memName, m.hpNo, m.memAssort, c.paymentKind, c.cmsAmount, c.commiAmount 
                FROM member m
                   INNER JOIN cms c ON m.memId = c.memId
                WHERE m.memId = '$memId' AND memStatus in('0','2','9')";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);

			if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

			$groupCode    = $row->groupCode;
			$sponsId      = $row->sponsId;
			$memName      = $row->memName;
			$hpNo         = $row->hpNo;
			$memAssort    = $row->memAssort;
			$paymentKind  = $row->paymentKind;
			$payAmount    = $row->cmsAmount;
			$commiAmount  = $row->commiAmount;

			// 출금신청 거래ID 생성
			$transactionId = date("ymd") . "-" . $memId;
			$result_status = "0";

		} else {
			$result_status = "1";
			$result_message = "CMS에 등록되어있지 않은 회원입니다.";
		}

	} else {
		$result_status = "1";
		$result_message = "이미 당월에 출금신청한 회원니다.";
		$message = $result_message;
	}

	// 회원정보의 CMS ID
	if ($result_status == "0") {
		$sql = "SELECT cmsId FROM member WHERE memId = '$memId'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);

			$cmsId = $row->cmsId;
			$result_status = "0";

		} else {
			$result_status = "1";
			$result_message = "CMS ID가 등록되어 있지 않습니다.";
			$message = $result_message;
		}
	}

	// 테스트계정 정보
	//$result_status = "0";
	//$paymentKind = "CARD";
	//$transactionId = date("ymdHis");
	//$payAmount = "5000";
	//$managerId = "sdsitest";
	//$CUST_ID   = "sdsitest";
	//$SW_KEY = "4LjFflzr6z4YSknp";
	//$CUST_KEY = "BT2z4D5DUm7cE5tl";

	// API 전송
	if ($result_status == "0") {
		if ($paymentKind == "CMS") $urlKind = "cms";
		else if ($paymentKind == "CARD") $urlKind = "card";

		//$url = "https://api.efnc.co.kr:1443/v1/payments/$urlKind"; // 테스트
		$url = "https://api.hyosungcms.co.kr/v1/payments/$urlKind";

		// header
		$header = Array(
			"Content-Type: application/json; charset=utf-8", 
			"Authorization: VAN $SW_KEY:$CUST_KEY"
		);

		// body
		$body = Array(
			"memberId"      => $cmsId, 
			"transactionId" => $transactionId, 
			"paymentDate"   => $paymentDate, 
			"callAmount"    => $payAmount
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($ch, CURLOPT_ENCODING , "");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); 

		$response = curl_exec($ch);

		curl_close($ch);

		$response = json_decode($response, true);
		//print_r($response);

		if ($response[error] != null) {
			$requestStatus = "1";
			$result_status = "1";
			$message = $response[error][message];
			$result_message = $response[error][message];

		} else {
			$result_status = "0";
			$payment = $response[payment];
			$status = $response[payment][status];
			$message = $response[payment][status];
			$result_message = $response[payment][status];

			if ($message == "승인성공" || $message == "출금대기") {
				$requestStatus = "0";
				$result_status = "0";
				$result_message = "출금신청되었습니다.";

			} else {
				$requestStatus = "1";
				$result_status = "1";
				$message = $response[payment][result][message];
				$result_message = "오류가발생되었습니다.";
			}
		}

		// 스폰서정보
		$sql = "SELECT memName FROM member WHERE memId = '$sponsId'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);
		$sponsName = $row->memName;

		// 출금신청내역 등록
		$sql = "INSERT INTO cms_pay (sponsId, sponsName, memId, memName, memAssort, transactionId, paymentKind, payMonth, payAmount, commiAmount, payMessage, requestStatus, adminId, adminName, wdate) 
							 VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$memAssort', '$transactionId', '$paymentKind', '$payMonth', '$payAmount', '$commiAmount', '$message', '$requestStatus', '$adminId', '$adminName', now())";
		$connect->query($sql);

		if ($result_status == "1") {
			// 구독 연체횟수를 알아본다.
			$sql = "SELECT ifnull(count(idx),0) AS delayCount 
					FROM cms_pay 
					WHERE memId = '$memId' AND (requestStatus = '1' or payStatus = '5' )";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);
			$delayCount = $row->delayCount;

			//if ($delayCount > 2) $delayCount = 9;

			// 회원상태 = 보류, 구독료납부 = 연체로 변경한다.
			$sql = "UPDATE member SET clearStatus = '$count' WHERE memId = '$memId'";
			$connect->query($sql);

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

	} else {
		$result_status = "1";
		$message = $result_message;
	}

	// 출금신청 로그등록
	$sql = "INSERT INTO cms_pay_log (memId, memName, payMonth, paymentKind, transactionId, payAmount, message, adminId, adminName, status, wdate)
						     VALUES ('$memId', '$memName', '$payMonth', '$paymentKind', '$transactionId', '$payAmount', '$message', '$adminId', '$adminName', '$result_status', now())";
	$connect->query($sql);

	$response = array(
		'result'   => $result_status,
		'message'  => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>