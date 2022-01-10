<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/cms.php";

	/*
	* 회원 > 내정보 > CMS등록정보
	* parameter ==> userId:         회원아이디
	* parameter ==> paymentKind:    납부수단(CARD: 신용카드, CMS: 자동이체)
	* parameter ==> paymentCompany: 은행코드
	* parameter ==> paymentNumber:  계좌/카드번호
	* parameter ==> payerName:      예금주/소유주
	* parameter ==> payerNumber:    생년월일/사업자번호
	* parameter ==> valid:          유효기간(MM/YY)
	* parameter ==> cardPasswd:     카드비밀번호(앞2자리)
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId         = $input_data->{'userId'};
	$paymentKind    = $input_data->{'paymentKind'};
	$paymentCompany = $input_data->{'paymentCompany'};
	$paymentNumber  = $input_data->{'paymentNumber'};
	$payerName      = $input_data->{'payerName'};
	$payerNumber    = $input_data->{'payerNumber'};
	$valid          = $input_data->{'valid'};
	$cardPasswd     = $input_data->{'cardPasswd'};

	//$userId = "a27233377";
	//$paymentKind    = "CARD";
	//$paymentCompany = "004";
	//$paymentNumber  = "6251032309586218";
	//$payerName      = "박태수";
	//$payerNumber    = "670225";
	//$valid          = "03/24";
	//$cardPasswd     = "10";

	// 신용카드 유효기간
	if ($valid != "") {
		$arrValid   = explode("/", $valid);
		$validMonth = $arrValid[0];
		$validYear  = $arrValid[1];

	} else {
		$validMonth = "";
		$validYear  = "";
	}

	// 회원정보 검색
	$sql = "SELECT m.memName, m.cmsId, m.hpNo, c.cmsAmount 
	        FROM cms c 
			     inner join member m on c.memId = m.memId 
	        WHERE c.memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

		$memName   = $row->memName;
		$hpNo      = $row->hpNo;
		$cmsId     = $row->cmsId;
		$cmsAmount = $row->cmsAmount;

		// 기존 CMS 등록 정보 삭제 함수
		cmsDelete($cmsId);

		// 휴대폰번호에서 "-"를 제거
		$cmsHpNo = str_replace("-", "", $hpNo);

		$cms_body = Array(
			"memberId"         => $userId, 
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
			* 1. 회원정보 변경
			************************************************************************************* */
			if ($paymentKind == "CARD") {
				$agreeStatus = "9";
				$cmsStatus   = "9";

			} else {
				$agreeStatus = "0";
				$cmsStatus   = "0";
			}

			// 회원정보 변경
			$sql = "UPDATE member SET cmsStatus = '$cmsStatus', 
									  agreeStatus = '$agreeStatus'
						WHERE memId = '$userId'";
			$connect->query($sql);

			/* ************************************************************************************
			* 2. CMS정보 변경
			************************************************************************************* */
			$sql = "UPDATE cms SET paymentKind = '$paymentKind' WHERE memId = '$userId'";
			$connect->query($sql);

			// CMS로그 등록
			$assort  = "1";
			$message = "정상";
			$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
								 VALUES ('$userId', '$assort', '$message', '$userId', '$memName', now())";
			$connect->query($sql);

			/* ************************************************************************************
			* 3. 자동이체 동의서 등록
			************************************************************************************* */
			// 자동이체
			if ($paymentKind == "CMS") {
				cmsAgreeRegist($userId);
			}

		} else {
			$sql = "UPDATE member SET cmsStatus = '2' WHERE memId = '$userId'";
			$connect->query($sql);

			// CMS로그 등록
			$assort  = "1";
			$message = "정상";
			$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
								 VALUES ('$userId', '$assort', '$message', '$userId', '$memName', now())";
			$connect->query($sql);

			// 기존 CMS 등록 정보 삭제 함수
			cmsDelete($cmsId);
		}

	} else {
		$result_status = "1";
		$result_message = "CMS가 등록되어 있지 않는 회원입니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>