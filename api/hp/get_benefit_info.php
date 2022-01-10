<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 휴대폰신청 할인혜택금액
	* parameter ==> changeTelecom:  희망통신사
	* parameter ==> assort:         구분코드(N: 신규, M: 번호이동, C: 기기변경)
	* parameter ==> modelCode:      모델코드
	* parameter ==> capacityCode:   용량코드
	* parameter ==> chargeCode:     요금제코드
	* parameter ==> discountType:   할인받을 구분(S: 공시지원할인, C: 요금할인)
	* parameter ==> cardCode:       제휴카드할인코드 
	* parameter ==> marginPrice:    마진금액 (회원이 선택한 마진금액 --> Pull이면 0)
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$telecom       = $input_data->{'changeTelecom'};
	$assort        = $input_data->{'assort'};
	$modelCode     = $input_data->{'modelCode'};
	$capacityCode  = $input_data->{'capacityCode'};
	$chargeCode    = $input_data->{'chargeCode'};
	$discountType  = $input_data->{'discountType'};
	$cardCode      = $input_data->{'cardCode'};
	$marginPrice   = $input_data->{'marginPrice'};

	//$telecom   = "S";
	//$modelCode = "A716";
	//$capacityCode = "128";
	//$chargeCode = "SKT-001";
	//$assort = "C";
	//$marginPrice = "50000";
	//$discountType = "C";
	//$cardCode = "SKT003";

	if ($telecom == "") $telecom = "S";
	if ($modelCode == "") $modelCode = "G998";
	if ($capacityCode == "") $capacityCode = "120";
	if ($chargeCode == "") $chargeCode = "SKT-001";
	if ($assort == "") $assort = "C";
	if ($marginPrice == "") $marginPrice = "0";
	if ($discountType == "") $discountType = "S";
	if ($marginPrice == "") $marginPrice = "0";

	// 휴대폰 모델
	$benefitPrice = 0;
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
				$discountPrice = 0 - $supportPrice;
				$sumDiscount += $discountPrice;
			}
		}

		// ********************************************************************
		// 2순위. 제휴카드 할인
		// ********************************************************************
		if ($cardCode != "" && $cardCode != null) {
			$installment = "24"; // 할부개월 --> 24개월
			$sql = "SELECT discountPrice FROM hp_alliance_card WHERE cardCode = '$cardCode' and useYn = 'Y'";
			$result = $connect->query($sql);

			if ($result->num_rows > 0) {
				$row = mysqli_fetch_object($result);

				$discountPrice = $row->discountPrice * $installment;
				$discountPrice = 0 - $discountPrice;

				if ($factoryPrice + ($sumDiscount + $discountPrice) < 0) $discountPrice = 0 - ($factoryPrice + $sumDiscount);

				$sumDiscount += $discountPrice;
			}
		}

		// ********** 3순위. 추가지원(단말기할인/캐시백) --> 단말기 할인 ********************
		$discountPrice = 0;

		if ($marginPrice > 0) {
			// 수수료정책
			$commitPrice = 0;
			$sql = "SELECT priceNew, priceMnp, priceChange FROM hp_commi WHERE telecom = '$telecom' and modelCode = '$modelCode' and useYn = 'Y'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);

				if ($assort == "N") $commiPrice = $row2->priceNew;
				else if ($assort == "M") $commiPrice = $row2->priceMnp;
				else if ($assort == "C") $commiPrice = $row2->priceChange;
			}

			// 할인혜택
			$discountPrice = 0 - ($commiPrice - $marginPrice);
		}

		if ($factoryPrice + ($sumDiscount + $discountPrice) < 0) $discountPrice = 0 - ($factoryPrice + $sumDiscount);

		$benefitPrice = 0 - $discountPrice;

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
		'benefitPrice'  => number_format($benefitPrice),
	);
	
	//print_r($response);
	//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>