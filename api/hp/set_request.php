<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 휴대폰신청 등록 === >현재 사용 안함
	* parameter ==> memId:         회원ID
	* parameter ==> custName:      고객명
	* parameter ==> hpNo:          휴대폰번호
	* parameter ==> useTelecom:    현재통신사
	* parameter ==> changeTelecom: 희망통신사
	* parameter ==> assort:        구분코드(N: 신규, M: 번호이동, C: 기기변경)
	* parameter ==> modelCode:     모델
	* parameter ==> colorCode:     색상
	* parameter ==> capacityCode:  용량
	* parameter ==> chargeCode:    요금제
	* parameter ==> discountType:  할인구분(S: 공시지원할인, C: 요금할인)
	* parameter ==> installment:   할부개월
	* parameter ==> cardCode:      제휴카드할인코드 
	* parameter ==> benefitAssort: 할인혜택 (M: 단말기할인, C: 캐시백)
	* parameter ==> marginPrice:   마진금액 (회원이 선택한 마진금액 --> Pull이면 0)

	* parameter ==> bankCode:      고객-은행코드(캐시백)
	* parameter ==> accountName:   고객-예금주(캐시백)
	* parameter ==> accountNo:     고객-계좌번호(캐시백)

	* parameter ==> comment:       기타메모
	*/

	$input_data    = json_decode(file_get_contents('php://input'));
	$memId         = $input_data->{'memId'};
	$custName      = $input_data->{'custName'};
	$hpNo          = $input_data->{'hpNo'};

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
	$marginPrice   = $input_data->{'marginPrice'};

	$bankCode      = $input_data->{'bankCode'};
	$accountName   = $input_data->{'accountName'};
	$accountNo     = $input_data->{'accountNo'};

	$comment       = $input_data->{'comment'};

	//$memId         = "a27233377";
	//$custName      = "박하민";
	//$hpNo          = "010-2723-3377";
	//$useTelecom    = "K";
	//$changeTelecom = "K";
	//$assort        = "C";
	//$modelCode     = "G996";
	//$capacityCode  = "128";
	//$colorCode     = "258";
	//$chargeCode    = "SKT-005";
	//$discountType  = "S";
	//$installment   = "24";
	//$cardCode      = "SKT003";
	//$benefitAssort = "C";
	//$marginPrice   = "50000";
	//$bankCode      = "003";
	//$accountName   = "박하민";
	//$accountNo     = "123-45-6789";
	//$comment       = "개발자 테스트";

	$custHpNo = $hpNo; // 알림톡 전송을 위한 고객 휴대폰번호

	if ($installment == "") $installment = "36";
	if ($cardDiscount == "") $cardDiscount = "N";
	if ($marginPrice == "") $marginPrice = "0";
	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
	if ($accountNo != "") $accountNo = aes128encrypt($accountNo);

    $comment = str_replace("'", "′", $comment);

	// 회원정보
	$sql = "SELECT memName, hpNo FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$memName = $row->memName;

		if ($hpNo != "") $memHpNo = aes_decode($row->hpNo);
		else $memHpNo = "";

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

		// 신청서 등록
		$siteAssort = "S";
		$sql = "INSERT INTO hp_request (siteAssort, memId, memName, custName, hpNo, useTelecom, changeTelecom, requestAssort, 
		                                modelCode, modelName, colorCode, colorName, capacityCode, capacityName, basicChargeCode, basicChargeName, chargeCode, chargeName, discountType, 
										factoryPrice, installment, benefitAssort, comment, requestStatus, wdate)
						        VALUES ('$siteAssort', '$memId', '$memName', '$custName', '$hpNo', '$useTelecom', '$changeTelecom', '$assort', 
								        '$modelCode', '$modelName', '$colorCode', '$colorName', '$capacityCode', '$capacityName', '$basicChargeCode', '$basicChargeName', '$chargeCode', '$chargeName', '$discountType', 
										'$factoryPrice', '$installment', '$benefitAssort', '$comment', '0', now())";
		$connect->query($sql);

		// 등록된 신청서의 일련번호를 구한다.
		$sql = "SELECT idx FROM hp_request WHERE memId = '$memId' and custName = '$custName' ORDER BY idx DESC LIMIT 1";
		$result3 = $connect->query($sql);

		if ($result3->num_rows > 0) {
			$row3 = mysqli_fetch_object($result3);
			$requestIdx = $row3->idx;

			// ********** 1순위. 공시지원 할인 **************************************
			$sumDiscount = 0;

			if ($discountType == "S") { // 할인받을 구분 = 공시지원
				$supportPrice = 0;
				$sql = "SELECT priceNew, priceMnp, priceChange FROM hp_support_price WHERE telecom = '$changeTelecom' and modelCode = '$modelCode' and useYn = 'Y'";
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

			// ********************************************************************
			// 2순위. 제휴카드 할인
			// ********************************************************************
			if ($cardCode != "" && $cardCode != null) {
				$sql = "SELECT cardName, discountPrice FROM hp_alliance_card WHERE cardCode = '$cardCode' and useYn = 'Y'";
				$result = $connect->query($sql);

				if ($result->num_rows > 0) {
					$row = mysqli_fetch_object($result);

					$cardName = $row->cardName;
					$discountPrice = $row->discountPrice * $installment;
					$discountPrice = 0 - $discountPrice;

					if ($factoryPrice + ($sumDiscount + $discountPrice) < 0) $discountPrice = 0 - ($factoryPrice + $sumDiscount);

					$sumDiscount += $discountPrice;
				}

			} else {
				$discountPrice = 0;
			}

			$discountAssort = "C"; // 제휴카드할인
			$discountName = selected_object($discountAssort, $arrDiscountAssort);

			$sql = "INSERT INTO hp_request_discount (requestIdx, discountAssort, discountName, discountPrice, wdate)
											 VALUES ('$requestIdx', '$discountAssort', '$discountName', '$discountPrice', now())";
			$connect->query($sql);

			// ********** 3순위. 사업자할인(단말기할인/캐시백) ********************
			// 수수료정책
			$commiPrice = 0;
			$sql = "SELECT priceNew, priceMnp, priceChange FROM hp_commi WHERE telecom = '$changeTelecom' and modelCode = '$modelCode' and useYn = 'Y'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);

				if ($assort == "N") $commiPrice = $row2->priceNew;
				else if ($assort == "M") $commiPrice = $row2->priceMnp;
				else if ($assort == "C") $commiPrice = $row2->priceChange;
			}

			if ($marginPrice == 0) {
				$marginPrice = $commiPrice;

				$discountAssort = "A"; // 사업자할인 = 0
				$discountName = selected_object($discountAssort, $arrDiscountAssort);
				$discountPrice = 0;

				$sql = "INSERT INTO hp_request_discount (requestIdx, discountAssort, discountName, discountPrice, wdate)
												 VALUES ('$requestIdx', '$discountAssort', '$discountName', '$discountPrice', now())";
				$connect->query($sql);

			} else {
				// 할인금액 계산
				$discountPrice = 0 - ($commiPrice - $marginPrice);

				if ($factoryPrice + ($sumDiscount + $discountPrice) < 0) {
					$discountPrice = 0 - ($factoryPrice + $sumDiscount);
					$marginPrice = $commiPrice;
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
			}

			// 할부원금, 월할부금, 할부이자
			$buyPrice = $factoryPrice + $sumDiscount;                             // 할부원금
			$monthInstFee = (int) (($buyPrice / 382) * $installment);             // 월할부수수료 = (실구매가 / 382) * 할부개월
			$monthInstPrice = (int) (($buyPrice + $monthInstFee) / $installment); // 월할부금 = (실구매가 + 월할부수수료) / 할부개월

			// ********** 신청정보 업데이트 ********************
			$sql = "UPDATE hp_request SET cardCode = '$cardCode', 
			                              cardName = '$cardName', 
										  factoryPrice = '$factoryPrice', 
										  buyPrice = '$buyPrice', 
										  monthInstFee = '$monthInstFee', 
										  monthInstPrice = '$monthInstPrice', 
										  commiPrice = '$commiPrice', 
										  commission = '$marginPrice' 
						WHERE idx = '$requestIdx'";
			$connect->query($sql);

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "'신청서 등록' 오류가 발생하였습니다.";
		}

		$result_status = "0";
		$result_message = "'신청완료'되었습니다.";

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
		sendTalk("HP_01_01", $receiptInfo);

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
		sendTalk("HP_02_03", $receiptInfo);

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
		sendTalk("HP_03_03", $receiptInfo);

		// 알림톡 전송(관리자)
		$adminHpNo = "010-5190-7770";
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
		sendTalk("HP_03_03", $receiptInfo);

	} else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "존재하지 않는 회원입니다.";
	}

	$response = array(
		'memId'   => $memId,
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>