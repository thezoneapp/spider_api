<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

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
	$payDate   = $input_data->{'payDate'};
	//error_log ($memId . ", " . $payDate . "::::", 3, "/home/spiderfla/upload/doc/debug.log");

	//$memId     = "27233377";
	//$adminId   = "admin";
	//$payDate   = "2020-10-30";

	if ($memId == "" || $payDate == "") exit;

	// 관리자 정보
	$sql = "SELECT name FROM admin WHERE id = '$adminId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$adminName = $row->name;

	$payMonth = substr($payDate, 0,7);
	$paymentDate = str_replace("-","",$payDate); // 출금신청일

	// 출금신청내역 등록
	$yearMonth = date("Ym");
	$sql = "SELECT memId FROM cms_pay WHERE memId = '$memId' and payMonth = '$payMonth'";
	$result = $connect->query($sql);

    if ($result->num_rows == 0) {
		// 출금할 회원정보
		$sql = "SELECT m.sponsId, m.memName, m.memAssort, c.paymentKind
                FROM member m
                   INNER JOIN cms c ON m.memId = c.memId
                WHERE m.memId = '$memId' AND m.cmsStatus = '9' and memStatus = '9'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);

			$sponsId = $row->sponsId;
			$memName = $row->memName;
			$memAssort = $row->memAssort;
			$paymentKind = $row->paymentKind;

			// 납부비용, 수수료
			if ($memAssort == "A") {
				$payCode = "payA";
				$commiCode = "commitMA";
				$commiAssort = "MA";
				$salesAssort = "PA";

			} else {
				$payCode = "payS";
				$commiCode = "commitMS";
				$commiAssort = "MS";
				$salesAssort = "PS";
			}

			$payAmount = "0";
			$commiPrice = "0";
			$sql = "SELECT code, content FROM setting WHERE code in ('$payCode', '$commiCode')";
			$result = $connect->query($sql);

			if ($result->num_rows > 0) {
				while($row = mysqli_fetch_array($result)) {
					if ($row[code] == "payA" || $row[code] == "payS") $payAmount = $row[content];
					else if ($row[code] == "commitMA" || $row[code] == "commitMS") $commiPrice = $row[content];
				}
			}

			// 출금신청 거래ID 생성
			$transactionId = date("ymd") . "-" . $memId;
			$result_ok = "0";

		} else {
			$result_ok = "1";
			$result_message = "CMS에 등록되어있지 않은 회원입니다.";
		}

	} else {
		$result_ok = "1";
		$result_message = "이미 당월에 출금신청한 회원니다.";
	}

	// 테스트계정 정보
	//$result_ok = "0";
	//$paymentKind = "CARD";
	//$transactionId = date("ymdHis");
	//$payAmount = "5000";
	//$managerId = "sdsitest";
	//$CUST_ID   = "sdsitest";
	//$SW_KEY = "4LjFflzr6z4YSknp";
	//$CUST_KEY = "BT2z4D5DUm7cE5tl";

	// API 전송
	if ($result_ok == "0") {
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
			"memberId" => $memId, 
			"transactionId" => $transactionId, 
			"paymentDate" => $paymentDate, 
			"callAmount" => $payAmount
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
			$result_ok = "1";
			$result_status = "1";
			$message = $response[error][message];
			$result_message = $response[error][message];

		} else {
			$result_status = "0";
			$member = $response[payment];
			$status = $response[payment][status];
			$message = $response[payment][status];
			$result_message = $response[payment][status];

			// 스폰서정보
			$sql = "SELECT memName FROM member WHERE memId = '$sponsId'";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);
			$sponsName = $row->memName;

			if ($message == "승인성공" || $message == "출금대기") {
				// 출금신청내역 등록
				$sql = "INSERT INTO cms_pay (sponsId, sponsName, memId, memName, memAssort, transactionId, paymentKind, payMonth, payAmount, adminId, adminName, wdate) 
									 VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$memAssort', '$transactionId', '$paymentKind', '$payMonth', '$payAmount', '$adminId', '$adminName', now())";
				$connect->query($sql);

				// 수수료(구독) 등록
				$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, assort, price, transactionId, wdate) 
										VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$commiAssort', '$commiPrice', '$transactionId', '$payDate')";
				$connect->query($sql);

				// 매출(구독) 등록
				$sql = "INSERT INTO sales (sponsId, sponsName, memId, memName, assort, price, transactionId, wdate) 
									VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$salesAssort', '$payAmount', '$transactionId', '$payDate')";
				$connect->query($sql);

				$result_ok = "0";
				$result_message = "출금신청되었습니다.";
			}
		}

	} else {
		$result_status = "1";
		$message = $result_message;
	}

	// 출금신청 로그등록
	$sql = "INSERT INTO cms_pay_log (memId, memName, payMonth, payAmount, message, adminId, adminName, status, wdate)
						     VALUES ('$memId', '$memName', '$payMonth', '$payAmount', '$message', '$adminId', '$adminName', '$result_status', now())";
	$connect->query($sql);

	$response = array(
		'result'   => $result_ok,
		'message'  => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>