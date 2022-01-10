<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/kakaoTalk.php";

	// *********************************************************************************************************************************
	// *                                                   CMS 신청                                                             *
	// *********************************************************************************************************************************
	/*
	* parameter
	*	memId:          회원 아이디
	*	paymentKind:    납부수단
	*	paymentCompany: 은행코드
	*	paymentNumber:  계좌/카드번호
	*	payerName:      예금주/소유주
	*	payerNumber:    생년월일/사업자번호
	*	valid:          유효기간(월/년도)
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId          = $input_data->{'memId'};
	$paymentKind    = $input_data->{'paymentKind'};
	$paymentCompany = $input_data->{'paymentCompany'};
	$paymentNumber  = $input_data->{'paymentNumber'};
	$payerName      = $input_data->{'payerName'};
	$payerNumber    = $input_data->{'payerNumber'};
	$valid          = $input_data->{'valid'};
	$cardPasswd     = $input_data->{'cardPasswd'};

	$paymentCompany = $paymentCompany->{'code'};
	$payerNumber    = str_replace(".", "", $payerNumber);
	$paymentNumber  = str_replace("-", "", $paymentNumber);
	$arrValid       = explode("/", $valid);
	$validMonth     = $arrValid[0];
	$validYear      = $arrValid[1];

	//$memId          = "27233377";
	//$paymentKind    = "CMS";
	//$paymentCompany = "003";
	//$paymentNumber  = "16913364001015";
	//$payerName      = "박태수";
	//$payerNumber    = "670225";
	//$validYear      = "24";
	//$validMonth     = "03";
	//$cardPasswd     = "10";

	if ($paymentNumber != "") $paymentNumber = aes128encrypt($paymentNumber);
	if ($payerNumber != "") $payerNumber = aes128encrypt($payerNumber);
	if ($validYear != "") $validYear = aes128encrypt($validYear);
	if ($validMonth != "") $validMonth = aes128encrypt($validMonth);
	if ($cardPasswd != "") $cardPasswd = aes128encrypt($cardPasswd);

	$sql = "SELECT memId FROM cms WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows == 0) {
		$sql = "INSERT INTO cms (memId, paymentKind, paymentCompany, paymentNumber, payerName, payerNumber, validYear, validMonth, cardPasswd, wdate) 
						 VALUES ('$memId', '$paymentKind', '$paymentCompany', '$paymentNumber', '$payerName', '$payerNumber', '$validYear', '$validMonth', '$cardPasswd', now())";
		$result = $connect->query($sql);

	} else {
		$sql = "UPDATE cms SET paymentKind = '$paymentKind', 
							   paymentCompany = '$paymentCompany', 
							   paymentNumber = '$paymentNumber', 
                               payerName = '$payerName', 
						       payerNumber = '$payerNumber', 
                               validYear = '$validYear',
							   validMonth = '$validMonth', 
							   cardPasswd = '$cardPasswd', 
							   wdate = now()
				WHERE memId = '$memId'";
		$result = $connect->query($sql);
	}

	// 효성 CMS에 전송할 자료 구성
	$sql = "SELECT m.idx as memIdx, m.groupCode, m.memId, m.memName, m.hpNo, m.memAssort, c.paymentKind, c.paymentCompany, c.paymentNumber, c.payerName, c.payerNumber, c.validYear, c.validMonth, c.cardPasswd 
			FROM member m
				INNER JOIN cms c ON m.memId = c.memId
			WHERE m.memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$adminId   = $row->memId;
		$adminName = $row->memName;

		$memIdx         = $row->memIdx;
		$groupCode      = $row->groupCode;
		$memId          = $row->memId;
		$memName        = $row->memName;
		$memAssort      = $row->memAssort;
		$paymentKind    = $row->paymentKind;
		$paymentCompany = $row->paymentCompany;
		$payerName      = $row->payerName;

		if ($row->hpNo != "") $hpNo = aes_decode($row->hpNo);
		if ($row->paymentNumber != "") $paymentNumber = aes_decode($row->paymentNumber);
		if ($row->payerNumber != "") $payerNumber = aes_decode($row->payerNumber);
		if ($row->validYear != "") $validYear = aes_decode($row->validYear);
		if ($row->validMonth != "") $validMonth = aes_decode($row->validMonth);
		if ($row->cardPasswd != "") $cardPasswd = aes_decode($row->cardPasswd);

		$hpNo = str_replace("-", "", $hpNo);

		// CMS납부금액 및 유치 수수료
		if ($memAssort == "M") {
			$payCode    = "payA";
			$commiCode = "commiMA";

		} else {
			$payCode    = "payS";
			$commiCode = "commiMS";
		}

		$cmsAmount    = "0";
		$commiAmount = "0";
		$sql = "SELECT code, content FROM setting WHERE code IN ('$payCode','$commiCode')";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			while($row = mysqli_fetch_array($result)) {
				if ($row[code] == "payA") $cmsAmount = $row[content]; // MD-CMS납부비용
				else if ($row[code] == "payS") $cmsAmount = $row[content]; // 구독-CMS납부비용
				else if ($row[code] == "commiMA") $commiAmount = $row[content]; // MD-유치수수료
				else if ($row[code] == "commiMS") $commiAmount = $row[content]; // 구독-유치수수료
			}
		}

		$result_status = "0";

	} else {
		$result_status = "1";
		$result_message = "존재하지 않는 회원니다.";
	}

	// 테스트 계정 정보
	//$result_status = "0";
	//$managerId = "sdsitest";
	//$CUST_ID   = "sdsitest";
	//$SW_KEY = "4LjFflzr6z4YSknp";
	//$CUST_KEY = "BT2z4D5DUm7cE5tl";

	// API 전송
	if ($result_status == "0") {
		//$url = "https://api.efnc.co.kr:1443/v1/members";
		$url = "https://api.hyosungcms.co.kr/v1/members";

		// header
		$header = Array(
			"Content-Type: application/json; charset=utf-8", 
			"Authorization: VAN $SW_KEY:$CUST_KEY"
		);

		// body
		$body = Array(
			"memberId" => $memId, 
			"memberName" => $memName, 
			"smsFlag" => "N", 
			"phone" => $hpNo,
			"email" => "",
			"zipcode" => "",
			"address1" => "",
			"address2" => "",
			"joinDate" => "",
			"receiptFlag" => "Y",
			"receiptNumber" => $hpNo,
			"memberKind" => "",
			"managerId" => $managerId,
			"memo" => "",
			"paymentStartDate" => "",
			"paymentEndDate" => "",
			"paymentDay" => "",
			"defaultAmount" => $cmsAmount,
			"paymentKind" => $paymentKind,
			"paymentCompany" => $paymentCompany,
			"paymentNumber" => $paymentNumber,
			"payerName" => $payerName,
			"payerNumber" => $payerNumber,
			"validYear" => $validYear,
			"validMonth" => $validMonth,
			"password" => $cardPasswd
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

		if ($response[error] != null) {
			$result_status = "1";
			$message = $response[error][message];
			$result_message = $response[error][message];

		} else {
			$member = $response[member];
			$status = $response[member][status];
			$message = $response[member][message];

			if ($status == "신청대기" || $status == "신청완료") {
				$message = $status;

				// CMS 납부금액 저장 및 결제 정보 제거
				$sql = "UPDATE cms SET cmsAmount = '$cmsAmount', commiAmount = '$commiAmount', paymentNumber = null, payerNumber = null, validYear = null, validMonth = null, cardPasswd = null WHERE memId = '$memId'";
				$connect->query($sql);

				if ($status == "신청대기") {
					$agreeStatus = "0";
					$cmsStatus = "1";
					$result_status = "0";
					$result_message = "CMS 신청을 완료하였습니다.\n해당 은행의 승인완료 후 신청이 완료됩니다.";

				} else if ($status == "신청완료") {
					$agreeStatus = "9";
					$cmsStatus = "9";
					$result_status = "0";
					$result_message = "CMS 신청을 완료하였습니다.";
				}

				// 판매점 회원상태 = 승인완료
				if ($memAssort == "S") $memStatus_sql = ", memStatus = '9', sponsId = recommandId ";
				else $memStatus_sql = "";

				$sql = "UPDATE member SET agreeStatus = '$agreeStatus', cmsStatus = '$cmsStatus' $memStatus_sql WHERE memId = '$memId'";
				$connect->query($sql);

				// 알림톡 전송
				$hpNo = preg_replace('/\D+/', '', $hpNo);
				$receiptInfo = array(
					"memId"       => $memId,
					"memName"     => $memName,
					"receiptHpNo" => $hpNo
				);
				sendTalk($groupCode, "J_02_01", $receiptInfo);


				// 회원구분: 판매점, 납부수단: 카드
				if ($memAssort == "S" && $paymentKind == "CARD") {
					// member log table에 등록한다.
					$sql = "INSERT INTO member_log (memIdx, adminId, adminName, logAssort, status, wdate)
											VALUES ('$memIdx', 'auto', '자동', 'A', '9', now())";
					$connect->query($sql);

					// 가입완료 알림톡 전송
					$hpNo = preg_replace('/\D+/', '', $hpNo);
					$receiptInfo = array(
						"memId"       => $memId,
						"memName"     => $memName,
						"receiptHpNo" => $hpNo
					);
					sendTalk($groupCode, "J_09_02", $receiptInfo);
				}

			} else {
				$result_status = "1";
				$result_message = $message;
			}
		}

		// CMS 로그등록
		$assort = "1";
		$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
		                     VALUES ('$memId', '$assort', '$message', '$adminId', '$adminName', now())";
		$connect->query($sql);

		/* ***********************************************************************************************
		 *************************              동의서 업로드           ************************************
		 ************************************************************************************************* */
		if ($agreeStatus == "0") {
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

			if ($response[agreementFile][result][code] == "Y") {
				// 회원 테이블 ==> 동의상태를 '동의완료'로 변경
				$sql = "UPDATE member SET agreeStatus = '9' WHERE memId = '$memId'";
				$result = $connect->query($sql);
				$message = "정상등록";

				// member log table에 등록한다.
				$sql = "INSERT INTO member_log (memIdx, adminId, adminName, logAssort, status, wdate)
										VALUES ('$memIdx', 'auto', '자동', 'A', '9', now())";
				$connect->query($sql);

				// 알림톡 전송
				$hpNo = preg_replace('/\D+/', '', $hpNo);
				$receiptInfo = array(
					"memId"       => $memId,
					"memName"     => $memName,
					"receiptHpNo" => $hpNo
				);
				sendTalk($groupCode, "J_09_02", $receiptInfo);

			} else {
				$message = "오류 발생";
			}

			// CMS 로그등록
			$assort = "2";
			$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
								 VALUES ('$memId', '$assort', '$message', '$adminId', '$adminName', now())";
			$connect->query($sql);
		}
	}

	$response = array(
		'result'   => $result_status,
		'message'  => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>