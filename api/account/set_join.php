<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/cms.php";
	include "../../inc/memberStatusUpdate.php";
	include "../../inc/utility.php";
	include "../../inc/kakaoTalk.php";

	/*
	* 회원 가입
	* parameter ==> recommandId:    추천인 아이디
	* parameter ==> memAssort:      회원구분(M: MD, S:판매점)
	* parameter ==> memName:        이름
	* parameter ==> hpNo:           휴대폰번호
	* parameter ==> paymentKind:    납부수단(CARD: 신용카드, CMS: 자동이체)
	* parameter ==> paymentCompany: 은행코드
	* parameter ==> paymentNumber:  계좌/카드번호
	* parameter ==> payerName:      예금주/소유주
	* parameter ==> payerNumber:    생년월일/사업자번호
	* parameter ==> valid:          유효기간(MM/YY)
	* parameter ==> cardPasswd:     카드비밀번호(앞2자리)
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$recommandId    = trim($input_data->{'recommandId'});
	$memAssort      = $input_data->{'memAssort'};
	$memName        = trim($input_data->{'memName'});
	$hpNo           = $input_data->{'hpNo'};
	$paymentKind    = $input_data->{'paymentKind'};
	$paymentCompany = $input_data->{'paymentCompany'};
	$paymentNumber  = $input_data->{'paymentNumber'};
	$payerName      = $input_data->{'payerName'};
	$payerNumber    = $input_data->{'payerNumber'};
	$valid          = $input_data->{'valid'};
	$cardPasswd     = $input_data->{'cardPasswd'};

	//$recommandId    = "a33368055";
	//$memAssort      = "M";
	//$memName        = "양민석";
	//$hpNo           = "010-2336-3753";
	//$paymentKind    = "CARD";
	//$paymentCompany = "003";
	//$paymentNumber  = "5570420315221076";
	//$payerName      = "양민석";
	//$payerNumber    = "970601";
	//$valid          = "02/25";
	//$cardPasswd     = "01";

	// 신용카드 유효기간
	if ($valid != "") {
		$arrValid   = explode("/", $valid);
		$validMonth = $arrValid[0];
		$validYear  = $arrValid[1];

	} else {
		$validMonth = "";
		$validYear  = "";
	}

	// 휴대폰 번호를 이용한 ID 및 비밀번호 생성
	$arrHpNo = explode("-", $hpNo);
	$memId   = "a" . trim($arrHpNo[1]) . trim($arrHpNo[2]);
	$memPw   = $memId . "!";

	/* ****************************************************************************************
	* 1. 동일한 회원 아이디가 있나 체크
	***************************************************************************************** */
	$sql = "SELECT memId
			FROM ( select id as memId from admin
				   union 
				   select memId from member 
				 ) m 
			WHERE memId = '$memId'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$result_status  = "0";
		$result_message = "정상";

	} else {
		$result_status  = "1";
		$result_message = "이미 존재하는 '휴대폰번호'입니다.";
	}

	// 1-1. 추천인 정보 조회
	if ($result_status == "0") {
		$sql = "SELECT idx FROM member WHERE memAssort = 'M' and memStatus = '9' and memId = '$recommandId'";
		$result = $connect->query($sql);

		if ($result->num_rows === 1) {
			$result_status  = "0";
			$result_message = "정상";

		} else {
			// 실패 결과를 반환합니다.
			$result_status  = "1";
			$result_message = "존재하지 않거나 승인되지 않은 '추천인 아이디'입니다.";
		}
	}

	// 아이디와 추천인정보가 정상이면...
	if ($result_status == "0") {
		cmsDelete($memId);

		/* ************************************************************************************
		* 2. CMS 등록
		************************************************************************************* */
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

		// 휴대폰번호에서 "-"를 제거
		$cmsHpNo = str_replace("-", "", $hpNo);

		$cms_body = Array(
			"memberId"         => $memId, 
			"memberName"       => $memName, 
			"smsFlag"          => "N", 
			"phone"            => $cmsHpNo,
			"email"            => "",
			"zipcode"          => "",
			"address1"         => "",
			"address2"         => "",
			"joinDate"         => "",
			"receiptFlag"      => "Y",
			"receiptNumber"    => $cmsHpNo,
			"memberKind"       => "",
			"managerId"        => $managerId,
			"memo"             => "",
			"paymentStartDate" => "",
			"paymentEndDate"   => "",
			"paymentDay"       => "",
			"defaultAmount"    => $cmsAmount,
			"paymentKind"      => $paymentKind,
			"paymentCompany"   => $paymentCompany,
			"paymentNumber"    => $paymentNumber,
			"payerName"        => $payerName,
			"payerNumber"      => $payerNumber,
			"validYear"        => $validYear,
			"validMonth"       => $validMonth,
			"password"         => $cardPasswd
		);

		// CMS등록 함수 호출
		$response = cmsRegist($cms_body);
		$response = json_decode($response);

		$result_status  = $response->{'result'};
		$result_message = $response->{'message'};

		// CMS등록 == 정상
		if ($result_status == "0") {
			/* ************************************************************************************
			* 3. 회원정보 등록
			************************************************************************************* */
			if ($memPw != "") $memPw = aes128encrypt($memPw);
			if ($hpNo != "") $hpNo = aes128encrypt($hpNo);

			$contractStatus = "9"; // 계약상태
			$memStatus      = "0"; // 회원상태

			// 가입비 납부상태
			if ($memAssort == "M") $joinPayStatus = "1";
			else $joinPayStatus = "0";

			// CMS 상태
			if ($paymentKind == "CARD") {
				$agreeStatus = "9";
				$cmsStatus   = "9";

			} else {
				$agreeStatus = "0";
				$cmsStatus   = "0";
			}

			// 회원정보 등록
			$sql = "INSERT INTO member (recommendId, memId, memName, memPw, memAssort, hpNo, contractStatus, agreeStatus, cmsStatus, joinPayStatus, memStatus, firstLogin, cmsId, gajaId, wdate)
							    VALUES ('$recommendId', '$memId', '$memName', '$memPw', '$memAssort', '$hpNo', '$contractStatus', '$agreeStatus', '$cmsStatus', '$joinPayStatus', '$memStatus', '0', '$memId', '$memId', now())";
			$connect->query($sql);

			/* ************************************************************************************
			* 4. 회원 LOSMAP 구성
			************************************************************************************* */
			$sql = "SELECT idx FROM member WHERE memId = '$memId'";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);

			memberApproval($row->idx, "9");

			/* ************************************************************************************
			* 5. CMS정보 등록
			************************************************************************************* */
			$sql = "INSERT INTO cms (memId, paymentKind, cmsAmount, commiAmount, wdate) 
							 VALUES ('$memId', '$paymentKind', '$cmsAmount', '$commiAmount', now())";
			$result = $connect->query($sql);

			// CMS로그 등록
			$assort  = "1";
			$message = "정상";
			$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
								 VALUES ('$memId', '$assort', '$message', '$memId', '$memName', now())";
			$connect->query($sql);

			/* ************************************************************************************
			* 6. 자동이체 동의서 등록
			************************************************************************************* */
			// 자동이체
			if ($paymentKind == "CMS") {
				cmsAgreeRegist($memId);
			}
		}
	}

	/* ************************************************************************************
	* 7. 최종결과 리턴
	************************************************************************************* */
	$response = array(
		'memId'   => $memId,
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>