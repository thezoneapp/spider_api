<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/cms.php";

	/*
	* 내정보 > CMS등록정보
	* parameter
		userId:         회원아이디
		paymentKind:    납부수단(CARD: 신용카드, CMS: 자동이체)
		paymentCompany: 은행코드
		paymentNumber:  계좌/카드번호
		payerName:      예금주/소유주
		payerNumber:    생년월일/사업자번호
		valid:          유효기간(MM/YY)
		cardPasswd:     카드비밀번호(앞2자리)
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
	$sql = "SELECT memId, memName, hpNo, groupCode, organizeCode FROM member WHERE memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

		$memId        = $row->memId;
		$memName      = $row->memName;
		$hpNo         = $row->hpNo;
		$groupCode    = $row->groupCode;
		$organizeCode = $row->organizeCode;

		// 그룹정보 > 회원구성정보 > 서비스정보
		$sql = "SELECT subsFee FROM group_organize_service WHERE groupCode = '$groupCode' and organizeCode = '$organizeCode'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);
			$cmsAmount = $row2->subsFee;

			// 그룹정보 > 회원구성정보 > 보너스정보 > 피추천인이 구독료 납부
			$payType = "S";
			$sql = "SELECT subsSaveAssort, subsSaveFee FROM group_organize_bonus WHERE groupCode = '$groupCode' and organizeCode = '$organizeCode' AND payType = '$payType'";
			$result3 = $connect->query($sql);

			if ($result3->num_rows > 0) {
				$row3 = mysqli_fetch_object($result3);
				$subsSaveAssort = $row3->subsSaveAssort;
				$subsSaveFee    = $row3->subsSaveFee;

				if ($subsSaveAssort == "R") $commiAmount = ($cmsAmount / 110 * 100) * ($subsSaveFee / 100);
				else $commiAmount = $subsSaveFee;

				// 기존 CMS 정보 삭제
				$sql = "DELETE FROM cms WHERE memId = '$memId'";
				$connect->query($sql);

				// CMS 정보 등록
				$sql = "INSERT INTO cms (memId, paymentKind, cmsAmount, commiAmount, changeDate, wdate) 
								 VALUES ('$memId', '$paymentKind', '$cmsAmount', '$commiAmount', now(), now())";
				$result = $connect->query($sql);

				// CMS로그 등록
				$assort  = "1";
				$message = "정상";
				$sql = "INSERT INTO cms_log (memId, assort, message, adminId, adminName, wdate)
									 VALUES ('$memId', '$assort', '$message', '$memId', '$memName', now())";
				$connect->query($sql);

				// 기존 CMS 등록 정보 삭제 함수
				cmsDelete($memId);

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

				// CMS등록 함수
				$response = cmsRegist($cms_body);
				$response = json_decode($response, true);

				$result_status  = $response[result];
				$result_message = $response[message];

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
						$cmsStatus   = "1";
					}

					// 회원정보 변경
					$sql = "UPDATE member SET cmsId = '$userId',
											  cmsStatus = '$cmsStatus', 
											  agreeStatus = '$agreeStatus', 
											  payType = 'S' 
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
				$result_message = "'보너스정보'가 존재하지 않습니다.";
			}

		} else {
			$result_status = "1";
			$result_message = "'서비스정보'가 존재하지 않습니다.";
		}

	} else {
		$result_status = "1";
		$result_message = "존재하지 않는 회원입니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>