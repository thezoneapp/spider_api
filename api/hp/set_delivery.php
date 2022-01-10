<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 휴대폰신청 예상요금 정보
	* parameter ==> changeTelecom:  희망통신사
	* parameter ==> assort:         구분코드(N: 신규, M: 번호이동, C: 기기변경)
	* parameter ==> modelCode:      모델코드
	* parameter ==> capacityCode:   용량코드
	* parameter ==> chargeCode:     요금제코드
	* parameter ==> discountType:   할인받을 구분(S: 공시지원할인, C: 요금할인)
	* parameter ==> installment:    할부개월
	* parameter ==> cardCode:       제휴카드할인코드 
	* parameter ==> benefitAssort:  할인혜택 (M: 단말기할인, C: 캐시백)
	* parameter ==> marginPrice:    마진금액 (회원이 선택한 마진금액 --> Pull이면 0)
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$telecom       = $input_data->{'changeTelecom'};
	$assort        = $input_data->{'assort'};
	$modelCode     = $input_data->{'modelCode'};
	$capacityCode  = $input_data->{'capacityCode'};
	$chargeCode    = $input_data->{'chargeCode'};
	$discountType  = $input_data->{'discountType'};
	$installment   = $input_data->{'installment'};
	$cardCode      = $input_data->{'cardCode'};
	$benefitAssort = $input_data->{'benefitAssort'};
	$marginPrice   = $input_data->{'marginPrice'};

	//$telecom   = "S";
	//$modelCode = "A716";
	//$capacityCode = "128";
	//$chargeCode = "SKT-001";
	//$assort = "C";
	//$marginPrice = "0";
	//$discountType = "C";
	//$installment = "24";
	//$cardCode = "SKT003";

	if ($telecom == "") $telecom = "S";
	if ($modelCode == "") $modelCode = "";
	if ($capacityCode == "") $capacityCode = "120";
	if ($chargeCode == "") $chargeCode = "SKT-001";
	if ($assort == "") $assort = "C";
	if ($marginPrice == "") $marginPrice = "0";
	if ($discountType == "") $discountType = "S";

	if ($installment == "") $installment = "36";
	if ($benefitAssort == "") $benefitAssort = "M";
	if ($marginPrice == "") $marginPrice = "0";

	// 휴대폰 모델
	$sql = "SELECT modelName, hm.imtAssort, chargeCode 
			FROM hp_model hm 
				 INNER JOIN hp_goods hg ON hm.goodsCode = hg.goodsCode and hg.telecoms like '%$telecom%' and hg.useYn = 'Y' 
				 INNER JOIN hp_model_charge hmc ON hm.modelCode = hmc.modelCode and hmc.telecom = '$telecom' and hmc.useYn = 'Y' 
			WHERE hm.modelCode = '$modelCode'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$modelName    = $row->modelName;
		$imtAssort    = $row->imtAssort;
		$chargeBasic  = $row->chargeCode;

		// 출고가 정보
		$factoryPrice = "0";
		$sql = "SELECT factoryPrice FROM hp_model_capacity WHERE modelCode = '$modelCode' and capacityCode = '$capacityCode'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);
			$factoryPrice = $row2->factoryPrice;
		}

		// ********** 1순위. 공시지원 할인 **************************************
		$discount = array();
		$sumDiscount = 0;

		if ($discountType == "S") { // 할인받을 구분 = 공시지원
			$supportPrice = 0;
			$sql = "SELECT priceNew, priceMnp, priceChange FROM hp_support_price WHERE telecom = '$telecom' and modelCode = '$modelCode' and useYn = 'Y'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);

				if ($assort == "N") $supportPrice = $row2->priceNew;
				else if ($assort == "M") $supportPrice = $row2->priceMnp;
				else if ($assort == "C") $supportPrice = $row2->priceChange;
			}

			if ($supportPrice > 0) {
				$discountName = selected_object("S", $arrDiscountAssort);
				$discountPrice = 0 - $supportPrice;
				$sumDiscount += $discountPrice;

				$discountInfo = array(
					'discountName'  => $discountName,
					'discountPrice' => number_format($discountPrice),
				);
				array_push($discount, $discountInfo);
			}
		}

		// ********************************************************************
		// 2순위. 제휴카드 할인
		// ********************************************************************
		if ($cardCode != "" && $cardCode != null) {
			$sql = "SELECT discountPrice FROM hp_alliance_card WHERE cardCode = '$cardCode' and useYn = 'Y'";
			$result = $connect->query($sql);

			if ($result->num_rows > 0) {
				$row = mysqli_fetch_object($result);

				$discountPrice = $row->discountPrice * $installment;
				$discountPrice = 0 - $discountPrice;

				if ($factoryPrice + ($sumDiscount + $discountPrice) < 0) $discountPrice = 0 - ($factoryPrice + $sumDiscount);

				$sumDiscount += $discountPrice;

				$discountInfo = array(
					'discountName'  => "제휴카드할인",
					'discountPrice' => number_format($discountPrice),
				);
				array_push($discount, $discountInfo);
			}
		}

		// ********** 3순위. 추가지원(단말기할인/캐시백) --> 단말기 할인 ********************
		// 수수료정책
		$commitPrice = 0;
		$sql = "SELECT priceNew, priceMnp, priceChange FROM hp_commit WHERE telecom = '$telecom' and modelCode = '$modelCode' and useYn = 'Y'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);

			if ($assort == "N") $commitPrice = $row2->priceNew;
			else if ($assort == "M") $commitPrice = $row2->priceMnp;
			else if ($assort == "C") $commitPrice = $row2->priceChange;
		}

		// 할인혜택
		if ($benefitAssort == "M" && $marginPrice > 0) { // 단말기 할인 --> 추가지원
			$discountPrice = 0 - ($commitPrice - $marginPrice);

			if ($factoryPrice + ($sumDiscount + $discountPrice) < 0) $discountPrice = 0 - ($factoryPrice + $sumDiscount);

			$sumDiscount += $discountPrice;
			$discountName = selected_object("A", $arrDiscountAssort);

			$discountInfo = array(
				'discountName'  => $discountName,
				'discountPrice' => number_format($discountPrice),
			);
			array_push($discount, $discountInfo);
		}

		// ****************** 실구매가 *****************************************
		$buyPrice = $factoryPrice + $sumDiscount;

		// 월할부금 및 할부이자
		$monthInstFee = (int) (($buyPrice / 382) * $installment);             // 월할부수수료 = (실구매가 / 382) * 할부개월
		$monthInstPrice = (int) (($buyPrice + $monthInstFee) / $installment); // 월할부금 = (실구매가 + 월할부수수료) / 할부개월

		$mobileInfo = array(
			'factoryPrice'     => number_format($factoryPrice),     // 출고가
			'discount'         => $discount,                        // 할인배열
			'installmentPrice' => number_format($buyPrice),         // 할부원금
			'monthInstFee'     => number_format($monthInstFee),     // 월할부수수료
			'monthInstPrice'   => number_format($monthInstPrice),   // 월할부금
		);

		// ********************************************************************
		// 부가서비스
		// ********************************************************************
		$addService = array();
		$sumAddService = 0;

		$sql = "SELECT serviceCode, serviceName, servicePrice, periodDay, descript 
				FROM hp_add_service 
				WHERE telecom = '$telecom' and useYn = 'Y'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			while($row = mysqli_fetch_array($result)) {
				$serviceCode  = $row[serviceCode];
				$serviceName  = $row[serviceName];
				$servicePrice = $row[servicePrice];
				$periodDay    = $row[periodDay];
				$descript     = $row[descript];

				$sumAddService += $servicePrice;

				$addServiceInfo = array(
					'serviceName'  => $serviceName,
					'servicePrice' => number_format($servicePrice),
					'periodDay'    => $periodDay,
					'descript'     => $descript,
				);
				array_push($addService, $addServiceInfo);
			}
		}

		// **************************************************************************************************************
		// 의무 선택 요금 정보
		// **************************************************************************************************************
		$charge = array();
		$sql = "SELECT chargeName, chargePrice, discountPrice, chargeExplain 
				FROM hp_charge 
				WHERE chargeCode = '$chargeBasic' and useYn = 'Y'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);

			$chargeName    = $row->chargeName;
			$chargePrice   = $row->chargePrice;
			$discountPrice = $row->discountPrice;
			$chargeExplain = $row->chargeExplain;

			if ($discountType == "S") $discountPrice = 0; // 할인받을 구분 = 공시지원

			// 할인요금 정보
			$discount = array();
			$sumDiscount = 0;
			$sql = "SELECT discountCode, discountName, discountPrice, allYn 
					FROM hp_discount 
					WHERE discountType = 'C' and useYn = 'Y'";
			$result = $connect->query($sql);

			if ($result->num_rows > 0) {
				while($row = mysqli_fetch_array($result)) {
					$discountCode   = $row[discountCode];
					$discountName   = $row[discountName];
					$discountPrice2 = $row[discountPrice];
					$allYn          = $row[allYn];

					if ($allYn == "Y") { // 전체 요금제
						$access = "Y";
					
					} else { // 개별 요금제
						$sql = "SELECT idx FROM hp_discount_charge WHERE chargeCode = '$chargeBasic' and discountCode = '$discountCode' and useYn = 'Y'";
						$result2 = $connect->query($sql);
						$total = $result2->num_rows;

						if ($total > 0) $access = "Y";
						else $access = "N";
					}

					if ($access == "Y") {
						$sumDiscount += $discountPrice2;

						$discountInfo = array(
							'discountName'  => $discountName,
							'discountPrice' => number_format($discountPrice2),
						);
						array_push($discount, $discountInfo);
					}
				}
			}

			// 월할부금 및 할부이자
			$monthCharge = (int) ($chargePrice + $discountPrice + $sumDiscount + $sumAddService + $monthInstPrice);

			$chargeInfo = array(
				'chargeName'       => $chargeName,                         // 요금제명
				'chargePrice'      => number_format($chargePrice),         // 월요금
				'discountPrice'    => number_format($discountPrice),       // 요금할인
				'chargeExplain'    => $chargeExplain,                      // 요금제설명
				'discount'         => $discount,                           // 추가할인 배열
				'addService'       => $addService,                         // 부가서비스 배열
				'sumAddService'    => number_format($sumAddService),       // 부가서비스 합계금액
				'monthCharge'      => number_format($monthCharge),         // 월청구금액
			);
			array_push($charge, $chargeInfo);
		}

		// **************************************************************************************************************
		// 고객이 선택한 요금 정보
		// **************************************************************************************************************
		$sql = "SELECT chargeName, chargePrice, discountPrice, chargeExplain 
				FROM hp_charge 
				WHERE chargeCode = '$chargeCode'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);

			$chargeName    = $row->chargeName;
			$chargePrice   = $row->chargePrice;
			$discountPrice = $row->discountPrice;
			$chargeExplain = $row->chargeExplain;

			if ($discountType == "S") $discountPrice = 0; // 할인받을 구분 = 공시지원

			// 할인요금 정보
			$discount = array();
			$sumDiscount = 0;
			$sql = "SELECT discountCode, discountName, discountPrice, allYn 
					FROM hp_discount 
					WHERE discountType = 'C' and useYn = 'Y'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					$discountCode   = $row2[discountCode];
					$discountName   = $row2[discountName];
					$discountPrice2 = $row2[discountPrice];
					$allYn          = $row2[allYn];

					if ($allYn == "Y") { // 전체 요금제
						$access = "Y";
						
					} else { // 개별 요금제
						$sql = "SELECT idx FROM hp_discount_charge WHERE chargeCode = '$chargeCode' and discountCode = '$discountCode' and useYn = 'Y'";
						$result3 = $connect->query($sql);
						$total = $result3->num_rows;

						if ($total > 0) $access = "Y";
						else $access = "N";
					}

					if ($access == "Y") {
						$sumDiscount += $discountPrice2;

						$discountInfo = array(
							'discountName'  => $discountName,
							'discountPrice' => number_format($discountPrice2),
						);
						array_push($discount, $discountInfo);
					}
				}
			}

			// 월할부금 및 할부이자
			$monthCharge = (int) ($chargePrice + $discountPrice + $sumDiscount + $monthInstPrice);

			$chargeInfo = array(
				'chargeName'       => $chargeName,                         // 요금제명
				'chargePrice'      => number_format($chargePrice),         // 월요금
				'discountPrice'    => number_format($discountPrice),       // 요금할인
				'chargeExplain'    => $chargeExplain,                      // 요금제설명
				'discount'         => $discount,                           // 추가할인 배열
				'monthCharge'      => number_format($monthCharge),         // 월청구금액
			);
			array_push($charge, $chargeInfo);
		}

		// ****************** 최종정보 **************************************************
		$data = array(
			'modelName'  => $modelName,
			'mobileInfo' => $mobileInfo,
			'chargeInfo' => $charge,
			'BankInfo'   => $arrBankCode,
		);

		$result_status = "0";
		$result_message = "정상";

	} else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result_status = "1";
		$result_message = "존재하지 않는 '모델'입니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
		'data'    => $data,
	);
	
	//print_r($response);
	//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>