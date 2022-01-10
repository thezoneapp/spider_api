<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 관리자페이지 > 휴대폰신청 > 조건변경
	* parameter
		requestIdx:    신청서idx
		useTelecom:    현재통신사
		changeTelecom: 희망통신사
		assort:        구분코드(N: 신규, M: 번호이동, C: 기기변경)
		modelCode:     모델
		colorCode:     색상
		capacityCode:  용량
		chargeCode:    요금제
		discountType:  할인구분(S: 공시지원할인, C: 요금할인)
		installment:   할부개월
		cardCode:      제휴카드할인코드 
		benefitAssort: 할인혜택 (M: 단말기할인, C: 캐시백)
	*/

	$input_data    = json_decode(file_get_contents('php://input'));
	$requestIdx    = $input_data->{'requestIdx'};

	$useTelecom    = $input_data->{'useTelecom'};
	$changeTelecom = $input_data->{'changeTelecom'};
	$assort        = $input_data->{'assort'};

	$modelCode     = $input_data->{'modelCode'};
	$colorCode     = $input_data->{'colorCode'};
	$capacityCode  = $input_data->{'capacityCode'};
	$chargeCode    = $input_data->{'chargeCode'};
	$discountType  = $input_data->{'discountType'};
	$installment   = $input_data->{'installment'};
	$cardCode      = $input_data->{'cardCode'};
	$benefitAssort = $input_data->{'benefitAssort'};

	//$requestIdx = "764";
	//$useTelecom    = "L";
	//$changeTelecom = "L";
	//$assort        = "C";
	//$modelCode     = "F707";
	//$capacityCode  = "256";
	//$colorCode     = "299";
	//$chargeCode    = "LG-5G85";
	//$discountType  = "S";
	//$installment   = "30";
	//$cardCode      = "";
	//$benefitAssort = "C";

	if ($installment == "") $installment = "24";
	if ($cardDiscount == "") $cardDiscount = "N";

	// 신청서정보
	$sql = "SELECT memId, memName, custName, hpNo, marginPrice FROM hp_request WHERE idx = '$requestIdx'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$memId       = $row->memId;
		$memName     = $row->memName;
		$custName    = $row->custName;
		$custHpNo    = $row->hpNo;
		$marginPrice = $row->marginPrice;

		if ($custHpNo != "") $custHpNo = aes_decode($row->hpNo);
		else $custHpNo = "";

		// 회원정보
		$sql = "SELECT groupCode, organizeCode, payType FROM member WHERE memId = '$memId'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);
			$groupCode    = $row2->groupCode;
			$organizeCode = $row2->organizeCode;
			$payType      = $row2->payType;

			// 그룹 마진 정보
			$groupMarginPrice = 0;
			$sql = "SELECT hpPrice FROM group_commi WHERE groupCode = '$groupCode'";
			$result = $connect->query($sql);

			if ($result->num_rows > 0) {
				$row = mysqli_fetch_object($result);
				$groupMarginPrice = $row->hpPrice;

			} else $groupMarginPrice = 0;

			// 그룹정보 > 회원구성정보 > 서비스정보
			$sql = "SELECT commiType, totalPayAssort, totalPayFee, hpPayAssort, hpPayFee 
					FROM group_organize_service 
					WHERE groupCode = '$groupCode' AND organizeCode = '$organizeCode'";
			$result3 = $connect->query($sql);
			$row3 = mysqli_fetch_object($result3);

			if ($row3->commiType == "B") { // 이용수수료(일괄적용)
				$payAssort = $row3->totalPayAssort;
				$payFee    = $row3->totalPayFee;

			} else if ($row3->commiType == "E") { // 이용수수료(건별적용)
				$payAssort = $row3->hpPayAssort;
				$payFee    = $row3->hpPayFee;
			} else {
				$payAssort = "";
				$payFee    = "0";
			}
		}

		// 휴대폰 모델
		$modelName = "";
		$sql = "SELECT modelName FROM hp_model WHERE modelCode = '$modelCode'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);
			$modelName = $row2->modelName;
		}

		// 휴대폰 용량
		$capacityName = "";
		$factoryPrice = "0";
		$sql = "SELECT factoryPrice FROM hp_model_capacity WHERE modelCode = '$modelCode' and capacityCode = '$capacityCode'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);
			$factoryPrice = $row2->factoryPrice;
			$capacityName = selected_object($capacityCode, $arrCapacityAssort);
		}

		// 휴대폰 색상
		$colorName = "";
		$sql = "SELECT colorName FROM hp_model_color WHERE idx = '$colorCode'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);
			$colorName = $row2->colorName;
		}

		// 기본 요금제
		$sql = "SELECT hmc.chargeCode, hc.chargeName  
				FROM hp_model_charge hmc 
					 INNER JOIN hp_charge hc ON hmc.chargeCode = hc.chargeCode
				WHERE hmc.modelCode = '$modelCode' AND hmc.telecom = '$changeTelecom'";
		$result3 = $connect->query($sql);

		if ($result3->num_rows > 0) {
			$row3 = mysqli_fetch_object($result3);
			$basicChargeCode = $row3->chargeCode;
			$basicChargeName = $row3->chargeName;
		}

		// 희망 요금제
		$sql = "SELECT chargeName FROM hp_charge WHERE chargeCode = '$chargeCode'";
		$result3 = $connect->query($sql);

		if ($result3->num_rows > 0) {
			$row3 = mysqli_fetch_object($result3);
			$chargeName = $row3->chargeName;
		}

		// 부가서비스정보
		$addServices = "";
		$sql = "SELECT serviceName, servicePrice, periodDay FROM hp_add_service WHERE useYn = 'Y' and telecom = '$changeTelecom'";
		$result3 = $connect->query($sql);

		if ($result3->num_rows > 0) {
			while($row3 = mysqli_fetch_array($result3)) {
				if ($addServices != "") $addServices .= ", ";
				$addServices .= $row3[serviceName];
			}
		}

		// 제휴카드 할인
		if ($cardCode != "" && $cardCode != null) {
			$sql = "SELECT cardName, discountPrice FROM hp_alliance_card WHERE cardCode = '$cardCode' and useYn = 'Y'";
			$result4 = $connect->query($sql);

			if ($result4->num_rows > 0) {
				$row4 = mysqli_fetch_object($result4);

				$cardName = $row4->cardName;
				$cardDiscountPrice = 0 - $row4->discountPrice;
			}

		} else {
			$cardName = "";
			$cardDiscountPrice = 0;
		}

		// 기존 할인정보 삭제
		$sql = "DELETE FROM hp_request_discount WHERE requestIdx = '$requestIdx'";
		$connect->query($sql);

		// ********** 1순위. 공시지원 할인 **************************************
		$sumDiscount = 0;

		if ($discountType == "S") { // 할인받을 구분 = 공시지원
			$supportPrice = 0;
			$sql = "SELECT priceNew, priceMnp, priceChange FROM hp_support_price WHERE telecom = '$changeTelecom' and modelCode = '$modelCode' and assortCode = '$discountType' and useYn = 'Y'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);

				if ($assort == "N") $supportPrice = $row2->priceNew;
				else if ($assort == "M") $supportPrice = $row2->priceMnp;
				else if ($assort == "C") $supportPrice = $row2->priceChange;
			}

			if ($supportPrice > 0) {
				$discountPrice = 0 - $supportPrice;
				$sumDiscount += $discountPrice;
			}

		} else {
			$discountPrice = 0;
		}

		$discountAssort = "S"; // 공시지원할인
		$discountName = selected_object($discountAssort, $arrDiscountAssort);

		$sql = "INSERT INTO hp_request_discount (requestIdx, discountAssort, discountName, discountPrice, wdate)
										 VALUES ('$requestIdx', '$discountAssort', '$discountName', '$discountPrice', now())";
		$connect->query($sql);

		// ********** 2순위. 사업자할인(단말기할인/캐시백) ********************
		// 수수료정책
		$response = modelCommission($modelCode, $discountType, $groupMarginPrice, $payType, $payAssort, $payFee);
		$response = json_decode($response, true);

		// 통신사별 수수료
		if ($changeTelecom == "S") $telecomData = $response[skData];
		else if ($changeTelecom == "K") $telecomData = $response[ktData];
		else if ($changeTelecom == "L") $telecomData = $response[lgData];

		// 구분 수수료
		if ($assort == "N") $assortData = $telecomData['new'];
		else if ($assort == "M") $assortData = $telecomData['mnp'];
		else if ($assort == "C") $assortData = $telecomData['change'];

		if (count($assortData) == 0) {
			$agencyId    = "";
			$agencyPrice = 0;
			$payPrice    = 0;
			$commiPrice  = 0;

		} else {
			$row = $assortData[0];
			$agencyId    = $row['agencyId'];
			$agencyPrice = str_replace(",", "", $row['agencyPrice']);
			$payPrice    = str_replace(",", "", $row['payPrice']);
			$commiPrice  = str_replace(",", "", $row['price']);
		}

		// 할인금액 계산
		$discountPrice = 0 - ($commiPrice - $marginPrice);
		$balance = $factoryPrice + ($sumDiscount + $discountPrice);

		if ($balance < 0) {
			$discountPrice = 0 - ($factoryPrice + $sumDiscount);
			$marginPrice = $marginPrice - $balance;
		}

		// 추가지원 등록
		if ($benefitAssort == "M") { // 단말기 할인
			$sumDiscount += $discountPrice;

			$discountAssort = "A"; // 사업자할인
			$discountName = selected_object($discountAssort, $arrDiscountAssort);

			$sql = "INSERT INTO hp_request_discount (requestIdx, discountAssort, discountName, discountPrice, wdate)
											 VALUES ('$requestIdx', '$discountAssort', '$discountName', '$discountPrice', now())";
			$connect->query($sql);

		} else if ($benefitAssort == "C") { // 캐시백
			$discountAssort = "A"; // 사업자할인 = 0
			$discountName = selected_object($discountAssort, $arrDiscountAssort);
			$discountPrice = 0;

			$sql = "INSERT INTO hp_request_discount (requestIdx, discountAssort, discountName, discountPrice, wdate)
											 VALUES ('$requestIdx', '$discountAssort', '$discountName', '$discountPrice', now())";
			$connect->query($sql);

			// 캐시백정보을 등록한다.
			$discountPrice = 0 - $discountPrice;
			$marginPrice += $discountPrice;
			$sql = "INSERT INTO hp_cash_back (requestIdx, cash, bankCode, accountName, accountNo, status, wdate)
									  VALUES ('$requestIdx', '$discountPrice', '$bankCode', '$accountName', '$accountNo', '0', now())";
			$connect->query($sql);
		}

		// 할부원금, 월할부금, 할부이자
		$buyPrice = $factoryPrice + $sumDiscount;                             // 할부원금
		$monthInstFee = (int) (($buyPrice / 382) * $installment);             // 월할부수수료 = (실구매가 / 382) * 할부개월
		$monthInstPrice = (int) (($buyPrice + $monthInstFee) / $installment); // 월할부금 = (실구매가 + 월할부수수료) / 할부개월

		// ********** 신청정보 업데이트 ********************
		if ($agencyPrice == "") $agencyPrice = "0";
		if ($payPrice == "") $payPrice = "0";

		$sql = "UPDATE hp_request SET useTelecom = '$useTelecom', 
									  changeTelecom = '$changeTelecom', 		
									  requestAssort = '$assort', 
									  modelCode = '$modelCode', 
									  modelName = '$modelName', 
									  colorCode = '$colorCode', 
									  colorName = '$colorName', 
									  capacityCode = '$capacityCode', 
									  capacityName = '$capacityName', 
									  basicChargeCode = '$basicChargeCode', 
									  basicChargeName = '$basicChargeName', 									  
									  chargeCode = '$chargeCode', 									  
									  chargeName = '$chargeName', 									  
									  discountType = '$discountType', 									  
									  installment = '$installment', 									  
									  benefitAssort = '$benefitAssort', 									  
									  cardCode = '$cardCode', 
		                              cardName = '$cardName',
									  cardDiscountPrice = '$cardDiscountPrice',
									  factoryPrice = '$factoryPrice', 
									  buyPrice = '$buyPrice', 
									  monthInstFee = '$monthInstFee', 
									  monthInstPrice = '$monthInstPrice', 
									  commiPrice = '$commiPrice', 
									  payPrice = '$payPrice', 
									  commission = '$marginPrice' 
					WHERE idx = '$requestIdx'";
		$connect->query($sql);

		$result_status = "0";
		$result_message = "'변경완료'되었습니다.";

		// ************** 알림톡 전송 ********************************
		$useTelecomName = selected_object($useTelecom, $arrTelecomAssort);
		$changeTelecomName = selected_object($changeTelecom, $arrTelecomAssort);

		// 알림톡 전송(고객)
		$custHpNo = preg_replace('/\D+/', '', $custHpNo);
		$receiptInfo = array(
			"custName"    => $custName,
			"memName"     => $memName,
			"receiptHpNo" => $custHpNo,
		);
		//sendTalk("HP_01_01", $receiptInfo);

		// 알림톡 전송(회원)
		$memHpNo = preg_replace('/\D+/', '', $memHpNo);
		$receiptInfo = array(
			"memName"       => $memName,
			"custName"      => $custName,
			"custHpNo"      => $custHpNo,
			"modelName"     => $modelName . " / " . $capacityName,
			"colorName"     => $colorName,
			"useTelecom"    => $useTelecomName,
			"changeTelecom" => $changeTelecomName,
			"chargeName"    => $chargeName,
			"requestMemo"   => $comment,
			"receiptHpNo"   => $memHpNo,
		);
		//sendTalk("HP_02_03", $receiptInfo);

		// 알림톡 전송(관리자)
		$adminHpNo = "010-6702-0903";
		$adminHpNo = preg_replace('/\D+/', '', $adminHpNo);
		$receiptInfo = array(
			"memName"       => $memName,
			"memHpNo"       => $memHpNo,
			"custHpNo"      => $custHpNo,
			"custName"      => $custName,
			"modelName"     => $modelName . " / " . $capacityName,
			"colorName"     => $colorName,
			"useTelecom"    => $useTelecomName,
			"changeTelecom" => $changeTelecomName,
			"chargeName"    => $chargeName,
			"requestMemo"   => $comment,
			"receiptHpNo"   => $adminHpNo,
		);
		//sendTalk("HP_03_03", $receiptInfo);

	} else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "존재하지 않는 신청서입니다.";
	}

	$response = array(
		'result'     => $result_status,
		'message'    => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>