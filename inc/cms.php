<?php
//*********************************************************************
//**********          효성 CMS               **************************
//*********************************************************************
$managerId = "nbbang18";
$CUST_ID   = "nbbang18";
$SW_KEY    = "sldcgtgYKYQ03R2W";
$CUST_KEY  = "7tPKQGphGwFLD5TJ";

// 테스트용
//"https://add-test.hyosungcms.co.kr/v1/custs/$CUST_ID/agreements",
//$url = "https://api.efnc.co.kr:1443/v1/"; // 포트번호가 없으면 실서버;
//$managerId = "sdsitest";
//$CUST_ID   = "sdsitest";
//$SW_KEY = "4LjFflzr6z4YSknp";
//$CUST_KEY = "BT2z4D5DUm7cE5tl";

//*********************************************************************
//**********              CMS 등록           **************************
//*********************************************************************
function cmsRegist($body) {
	global $connect, $managerId, $CUST_ID, $SW_KEY, $CUST_KEY;

	//$url = "https://api.efnc.co.kr:1443/v1/members";    // 개발서버
	$url = "https://api.hyosungcms.co.kr/v1/members"; // 운영서버

	// header
	$header = Array(
		"Content-Type: application/json; charset=utf-8", 
		"Authorization: VAN $SW_KEY:$CUST_KEY"
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
	//print_r( $response );

	$paymentKind = $body[paymentKind];

	if ($paymentKind == "CMS") {
		if ($response[error] != null) {
			$result_status = "1";
			$message = $response[error][message];
			$result_message = $response[error][message];

		} else {
			$member  = $response[member];
			$status  = $response[member][status];
			$message = $response[member][message];

			if ($status == "신청대기") {
				$result_status  = "0";
				$result_message = "CMS 신청을 완료하였습니다.\n해당 은행의 승인완료 후 신청이 완료됩니다.";

			} else {
				$result_status = "1";
				$result_message = $message;
			}
		}

	} else {
		if ($response[member][status] == "신청완료") {
			$result_status  = "0";
			$result_message = "신청이 완료되었습니다.";

		} else {
			$result_status = "1";
			$result_message = $response[member][result][message];
		}
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message
    );

	return json_encode( $response );
}

//*********************************************************************
//**********        자동이체 동의서 등록        **************************
//*********************************************************************
function cmsAgreeRegist($memId) {
	global $connect, $managerId, $CUST_ID, $SW_KEY, $CUST_KEY;

	$agreeDoc = "cms_doc.jpg";
	$path = "/home/spiderfla/upload/";
	$agreeDoc = $path . $agreeDoc;

	// header
	$header = Array(
		"Content-Type: multipart/form-data;", 
		"Authorization: VAN $SW_KEY:$CUST_KEY"
	);

	$data = array(
		'memberId' => $memId, 
		'file'     => new CURLFILE($agreeDoc)
	);

	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => "https://add.hyosungcms.co.kr/v1/custs/" . $CUST_ID . "/agreements",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => $data,
		CURLOPT_HTTPHEADER => $header,
	));

	$response = curl_exec($curl);

	curl_close($curl);

	$response = json_decode($response, true);
	//print_r($response);

	if ($response[agreementFile][result][code] == "Y") $agreeStatus = "9";
	else $agreeStatus = "1";

	// 회원 테이블 ==> 동의상태를 '동의완료'로 변경
	$sql = "UPDATE member SET agreeStatus = '$agreeStatus' WHERE memId = '$memId'";
	$connect->query($sql);
}

//*********************************************************************
//**********          CMS 등록정보 조회        **************************
//*********************************************************************
function cmsView($cmsId) {
	global $connect, $managerId, $CUST_ID, $SW_KEY, $CUST_KEY;

	$url = "https://api.hyosungcms.co.kr/v1/members/" . $cmsId;

	// header
	$header = Array(
		"Content-Type: application/json; charset=utf-8", 
		"Authorization: VAN $SW_KEY:$CUST_KEY"
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($ch, CURLOPT_ENCODING , "");
	curl_setopt($ch, CURLOPT_POST, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	$response = curl_exec($ch);

	curl_close($ch);

	$response = json_decode($response, true);

	if ($response[error] != null) {
		$result_status = "1";
		$result_message = $response[error][message];

	} else {
		$result_status = "0";
		$member         = $response[member];
		$result_message = $response[member][result][message];
		$status         = $response[member][status];
		$paymentKind    = $response[member][paymentKind];
		$paymentCompany = $response[member][paymentCompany];
		$paymentNumber  = $response[member][paymentNumber];

		//print_r( $member );
		//exit;
	}

	$response = array(
		'result'         => $result_status,
		'message'        => $result_message,
		'status'         => $status,
		'paymentKind'    => $paymentKind,
		'paymentCompany' => $paymentCompany,
		'paymentNumber'  => $paymentNumber,
    );

	return json_encode( $response );
}

//*********************************************************************
//**********           CMS 정보 삭제          **************************
//*********************************************************************
function cmsDelete($cmsId) {
	global $connect, $managerId, $CUST_ID, $SW_KEY, $CUST_KEY;

	$url = "https://api.hyosungcms.co.kr/v1/members/$cmsId";

	// header
	$header = Array(
		"Content-Type: application/json; charset=utf-8", 
		"Authorization: VAN $SW_KEY:$CUST_KEY"
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($ch, CURLOPT_ENCODING , "");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	$response = curl_exec($ch);

	curl_close($ch);

	$response = json_decode($response, true);

	if ($response[error] != null) {
		$result_status = "1";
		$result_message = $response[error][message];

	} else {
		// 회원 테이블 ==> CMS상태 '해지완료' 변경
		$sql = "UPDATE member SET cmsStatus = '8' WHERE memId = '$memId'";
		$connect->query($sql);

		$result_status = "0";
		$result_message = "해지되었습니다.";
	}

	$response = array(
		'result'   => $result_status,
		'message'  => $result_message,
    );

	return json_encode( $response );
}

//*********************************************************************
//**********              계좌실명조회         **************************
//*********************************************************************
function cmsRealName($body) {
	global $connect, $managerId, $CUST_ID, $SW_KEY, $CUST_KEY;

	$url = "https://api.hyosungcms.co.kr/v1/custs/" . $CUST_ID . "/check/account/verify-payer-number"; // 운영서버

	// header
	$header = Array(
		"Content-Type: application/json; charset=utf-8", 
		"Authorization: VAN $SW_KEY:$CUST_KEY"
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
//print_r($response);
	$response = json_decode($response, true);
	$result = $response[check][result];

	if ($response[error] != null) {
		$result_status = "1";
		$result_message = "계좌정보가 올바르지 않습니다.";
	
	} else {
		if ($result[flag] != "Y") {
			$result_status = "1";
			$result_message = $result[message];

		} else {
			$result_status = "0";
			$result_message = "계좌정보가 확인되었습니다.";
		}
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message
    );

	return json_encode( $response );
}

//*********************************************************************
//**********           CMS 출금 신청          **************************
//*********************************************************************
function cmsPayRegist($paymentKind, $body) {
	global $connect, $managerId, $CUST_ID, $SW_KEY, $CUST_KEY;

	if ($paymentKind == "CMS") $urlKind = "cms";
	else if ($paymentKind == "CARD") $urlKind = "card";

	//$url = "https://api.efnc.co.kr:1443/v1/payments/$urlKind"; // 테스트
	$url = "https://api.hyosungcms.co.kr/v1/payments/$urlKind";

	// header
	$header = Array(
		"Content-Type: application/json; charset=utf-8", 
		"Authorization: VAN $SW_KEY:$CUST_KEY"
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
		$payStatus = "0";
		$result_status = "1";
		$result_message = $response[error][message];

	} else {
		$payment = $response[payment];
		$status = $response[payment][status];
		$result_message = $response[payment][status];

		if ($result_message == "승인성공" || $result_message == "출금대기") {
			if ($result_message == "승인성공") $payStatus = "9";
			else $payStatus = "0";

			$requestStatus = "0";
			$result_status = "0";
			$result_message = "출금신청되었습니다.";

		} else {
			$requestStatus = "1";
			$payStatus = "0";
			$result_status = "1";
			$result_message = $response[payment][result][message];
		}
	}

	$response = array(
		'result'        => $result_status,
		'message'       => $result_message,
		'requestStatus' => $requestStatus,
		'payStatus'     => $payStatus,
    );

	return json_encode( $response );
}
?>