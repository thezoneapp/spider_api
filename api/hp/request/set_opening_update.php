<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자페이지 > 휴대폰신청 > 개통상태변경
	* parameter ==> 
		idx  :          신청서 일련번호
		adminId:        관리자ID
		openingStatus:  개통상태코드
		openingDate:    개통일자
	*/

	$input_data    = json_decode(file_get_contents('php://input'));
	$idx           = $input_data->{'idx'};
	$adminId       = $input_data->{'adminId'};
	$openingStatus = $input_data->{'openingStatus'};
	$openingDate   = $input_data->{'openingDate'};

	//$idx           = "731";
	//$adminId       = "admin";
	//$openingStatus = "9";
	//$openingDate   = "2021-10-12";

	// 신청정보
    $sql = "SELECT memId, memName, custId, custName, modelCode, changeTelecom, discountType, installment, basicChargeCode, 
				   openingCheck, openingStatus, commiPrice, payPrice, commission 
			FROM hp_request 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$memId         = $row->memId;
	$memName       = $row->memName;
	$custId        = $row->custId;
	$custName      = $row->custName;
	$modelCode     = $row->modelCode;
	$changeTelecom = $row->changeTelecom;
	$discountType  = $row->discountType;
	$installment   = $row->installment;
	$chargeCode    = $row->basicChargeCode;
	$commiPrice    = $row->commiPrice;
	$payPrice      = $row->payPrice;
	$commission    = $row->commission;
	$openingCheck  = $row->openingCheck;

	if ($row->openingStatus == $openingStatus) {
		$result_status = "1";
		$result_message = "'개통상태'가 이전과 동일합니다.";

	} else {
		// 회원 정보
		$sql = "SELECT memAssort, clearStatus FROM member WHERE memId = '$memId'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		$memAssort = $row->memAssort;

		if ($row->clearStatus == "0") $clearStatus = "N"; // 정상
		else $clearStatus = "Y"; // 보류

		// 개통전
		if ($openingStatus == "0") {
			$sql = "UPDATE hp_request SET requestStatus = '1', openingStatus = '$openingStatus', openingDate = null WHERE idx = '$idx'";
			$connect->query($sql);

			// 수수료정보 > 삭제
			$sql = "DELETE FROM commission WHERE hpRequestIdx = '$idx'";
			$connect->query($sql);

			$result_status = "0";
			$result_message = "'개통전'으로 처리되었습니다.";

		// 개통취소
		} else if ($openingStatus == "8") {
			$closeDate = date("Y-m-d");
			$sql = "UPDATE hp_request SET requestStatus = '$openingStatus', openingStatus = '$openingStatus', closeDate = '$closeDate' WHERE idx = '$idx'";
			$connect->query($sql);

			$commission = 0 - $commission;
			$remarks = $custName . " (해지)";
			$sql = "INSERT INTO commission (hpRequestIdx, sponsId, sponsName, memId, memName, memAssort, assort, custId, custName, price, remarks, wdate) 
									VALUES ('$idx', '$memId', '$memName', '$memId', '$memName', '$memAssort', 'P1', '$custId', '$custName', '$commission', '$remarks', now())";
			$connect->query($sql);

			$result_status = "0";
			$result_message = "'개통취소'로 처리되었습니다.";

		// 개통완료
		} else if ($openingStatus == "9") {
			if ($chargeCode == "") {
				// 기본 요금제
				$sql = "SELECT hmc.chargeCode, hc.chargeName  
						FROM hp_model_charge hmc 
							 INNER JOIN hp_charge hc ON hmc.chargeCode = hc.chargeCode
						WHERE hmc.modelCode = '$modelCode' AND hmc.telecom = '$changeTelecom'";
				$result2 = $connect->query($sql);

				if ($result2->num_rows > 0) {
					$row2 = mysqli_fetch_object($result2);
					$chargeCode = $row2->chargeCode;
				}
			}

			// 기본요금제 만료일자 정보
			$chargeExpire = "";
			$sql = "SELECT expireDayS, expireDayC FROM hp_charge WHERE chargeCode = '$chargeCode'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);

				if ($discountType == "S") $expireDay = $row2->expireDayS;
				else $expireDay = $row2->expireDayC;

				$expireDay = str_replace("일", "", $expireDay);
				$timestamp = strtotime("$openingDate $expireDay days");
				$chargeExpire = date("Y-m-d", $timestamp);
			}

			// 부가서비스 만료일자 정보
			$timestamp = strtotime("$openingDate 2 month");
			$addServiceExpire = date("Y-m-01", $timestamp);

			// 약정 만료일자 정보
			$timestamp = strtotime("$openingDate $installment month");
			$openingExipre = date("Y-m-d", $timestamp);

			if ($openingCheck == "Y") $openingCheckUpdate = "";
			else $openingCheckUpdate = ", openingCheck = 'Y' ";

			// 신청서 정보 변경
			$sql = "UPDATE hp_request SET requestStatus = '$openingStatus', 
			                              openingStatus = '$openingStatus', 
										  openingDate = '$openingDate', 
										  chargeExpire = '$chargeExpire', 
										  addServiceExpire = '$addServiceExpire', 
										  openingExipre = '$openingExipre' 
										  $openingCheckUpdate 
						WHERE idx = '$idx'";
			$connect->query($sql);

			$remarks = $custName . " (개통)";
			$sql = "INSERT INTO commission (hpRequestIdx, sponsId, sponsName, memId, memName, memAssort, assort, custId, custName, resultPrice, payPrice, price, remarks, wdate) 
									VALUES ('$idx', '$memId', '$memName', '$memId', '$memName', '$memAssort', 'P1', '$custId', '$custName', '$commiPrice', '$payPrice', '$commission', '$remarks', '$openingDate')";
			$connect->query($sql);

			$result_status = "0";
			$result_message = "'개통완료'로 처리되었습니다.";
		}
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>