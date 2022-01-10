<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	// *********************************************************************************************************************************
	// *                                                   CMS 출금신청 취소                                                             *
	// *********************************************************************************************************************************
	/*
	* parameter
	*	admind:        관리자ID
	*	transactionId: 거래ID
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$adminId       = $input_data->{'adminId'};
	$transactionId = $input_data->{'transactionId'};

	//$adminId       = "admin";
	//$transactionId = "2010201501371";

	// 관리자 정보
	$sql = "SELECT name FROM admin WHERE id = '$adminId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$adminName = $row->name;

	// 출금신청내역
	$sql = "SELECT memId, memName, paymentKind, payMonth, payAmount FROM cms_pay WHERE transactionId = '$transactionId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$memId = $row->memId;
		$memName = $row->memName;
		$paymentKind = $row->paymentKind;
		$payMonth = $row->payMonth;
		$payAmount = $row->payAmount;
		$result_ok = "0";

	} else {
		$result_ok = "1";
		$result_message = "해당 정보가 존재하지 않습니다.";
	}

	// 테스트계정 정보
	//$managerId = "sdsitest";
	//$CUST_ID   = "sdsitest";
	//$SW_KEY = "4LjFflzr6z4YSknp";
	//$CUST_KEY = "BT2z4D5DUm7cE5tl";

	// API 전송
	if ($result_ok == "0") {
		// header
		$header = Array(
			"Content-Type: application/json; charset=utf-8", 
			"Authorization: VAN $SW_KEY:$CUST_KEY"
		);

		// body
		$body = Array();

		if ($paymentKind == "CMS") {
			$urlKind = "cms";
			//$url = "https://api.efnc.co.kr:1443/v1/payments/" . $urlKind . "/" . $transactionId . "/cancel";  // 테스트
			$url = "https://api.hyosungcms.co.kr/v1/payments/cms/" . $transactionId;
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
			curl_setopt($ch, CURLOPT_ENCODING , "");
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

			$response = curl_exec($ch);

			curl_close($ch);

		} else if ($paymentKind == "CARD") {
			$urlKind = "card";
			//$url = "https://api.efnc.co.kr:1443/v1/payments/" . $urlKind . "/" . $transactionId . "/cancel";  // 테스트
			$url = "https://api.hyosungcms.co.kr/v1/payments/card/" . $transactionId . "/cancel";

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
			curl_setopt($ch, CURLOPT_ENCODING , "");
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); 

			$response = curl_exec($ch);

			curl_close($ch);
		}

		$response = json_decode($response, true);

		if ($response[error] != null) {
			$result_ok = "1";
			$status_status = "1";
			$message = $response[error][message];
			$result_message = $response[error][message];

		} else {
			if ($paymentKind == "CMS") {
				$message = "취소되었습니다.";

			} else {
				$member = $response[payment];
				$status = $response[payment][status];
				$message = $response[payment][status];
				$result_message = $response[payment][status];
			}

			// 출금신청자료 삭제
			$sql = "DELETE FROM cms_pay WHERE transactionId = '$transactionId'";
			$connect->query($sql);

			// 수수료(구독) 삭제
			$sql = "DELETE FROM commission WHERE transactionId = '$transactionId'";
			$connect->query($sql);

			// 매출(구독) 삭제
			$sql = "DELETE FROM sales WHERE transactionId = '$transactionId'";
			$connect->query($sql);

			$status_status = "0";
			$result_ok = "0";
			$result_message = "취소되었습니다.";
		}

		// 출금신청 로그등록
		$sql = "INSERT INTO cms_pay_log (memId, memName, payMonth, payAmount, message, adminId, adminName, status, wdate)
								 VALUES ('$memId', '$memName', '$payMonth', '$payAmount', '$message', '$adminId', '$adminName', '$status_status', now())";
		$connect->query($sql);
	}

	$response = array(
		'result'   => $result_ok,
		'message'  => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>