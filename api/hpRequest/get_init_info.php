<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/hpCommission.php";

	/*
	* 휴대폰신청 > 기초 정보
		userId: 회원아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId = $input_data->{'userId'};
	
	//$userId = 'a55645136';

	$protocol = isset($_SERVER['HTTPS']) ? "https://" : "http://";
	
	// 회원정보
	$sql = "SELECT groupCode, organizeCode, payType FROM member WHERE memId = '$userId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$groupCode    = $row->groupCode;
	$organizeCode = $row->organizeCode;
	$payType      = $row->payType;

	// 그룹 마진 정보
	$groupMarginPrice = 0;
	$sql = "SELECT hpPrice FROM group_commi WHERE groupCode = '$groupCode'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$groupMarginPrice = $row->hpPrice;
	}

	// 그룹정보 > 회원구성정보 > 서비스정보
	$sql = "SELECT commiType, totalPayAssort, totalPayFee, hpPayAssort, hpPayFee 
			FROM group_organize_service 
			WHERE groupCode = '$groupCode' AND organizeCode = '$organizeCode'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	if ($row->commiType == "B") { // 이용수수료(일괄적용)
		$payAssort = $row->totalPayAssort;
		$payFee    = $row->totalPayFee;

	} else if ($row->commiType == "E") { // 이용수수료(건별적용)
		$payAssort = $row->hpPayAssort;
		$payFee    = $row->hpPayFee;
	} else {
		$payAssort = "";
		$payFee    = "0";
	}

	// **************************************** 휴대폰 기종 정보 *******************************************************
	$goodsData = array();
    $sql = "SELECT no, goodsCode, goodsName, makerCode, telecoms, imtAssort, thumbnail 
	        FROM ( select @a:=@a+1 no, goodsCode, goodsName, makerCode, telecoms, imtAssort, thumbnail 
		           from hp_goods, (select @a:= 0) AS a 
		           where useYn = 'Y' 
		         ) m 
			ORDER BY no DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$goodsThumb = $protocol . "spiderplatform.co.kr/". $row[thumbnail];

			$goodsCode = $row[goodsCode];
			$goodsName = $row[goodsName];
			$makerCode = $row[makerCode];
			$telecoms  = $row[telecoms];
			$imtAssort = $row[imtAssort];

			// 모델 정보
			$modelData = array();
			$sql = "SELECT no, modelCode, modelName, thumbnail 
					FROM ( select @a:=@a+1 no, modelCode, modelName, thumbnail 
						   from hp_model, (select @a:= 0) AS a 
						   where useYn = 'Y' and goodsCode = '$goodsCode' 
						 ) m 
					ORDER BY no DESC";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					$modelCode = $row2[modelCode];
					$modelName = $row2[modelName];

					$modelThumb = $protocol . "spiderplatform.co.kr/". $row2[thumbnail];

					// 색상 데이타 검색 
					$colorData = array();
					$sql = "SELECT idx as colorCode, colorName, telecoms FROM hp_model_color WHERE useYn = 'Y' and modelCode = '$modelCode' ORDER BY idx ASC";
					$result3 = $connect->query($sql);

					if ($result3->num_rows > 0) {
						while($row3 = mysqli_fetch_array($result3)) {
							$data_info = array(
								'colorCode' => $row3[colorCode],
								'colorName' => $row3[colorName],
								'telecoms'  => $row3[telecoms],
							);
							array_push($colorData, $data_info);
						}
					}

					// 용량 데이타 검색 
					$capacityData = array();
					$sql = "SELECT capacityCode, factoryPrice FROM hp_model_capacity WHERE useYn = 'Y' and modelCode = '$modelCode' ORDER BY idx ASC";
					$result3 = $connect->query($sql);

					if ($result3->num_rows > 0) {
						while($row3 = mysqli_fetch_array($result3)) {
							$capacityName = selected_object($row3[capacityCode], $arrCapacityAssort);

							$data_info = array(
								'capacityCode' => $row3[capacityCode],
								'capacityName' => $capacityName,
								'factoryPrice' => $row3[factoryPrice],
							);
							array_push($capacityData, $data_info);
						}
					}

					// *********************** 수수료 정보 ***********************************************************
					// 공시지원
					$commiDiscountData = array();
					$discountType = "S";
					$response = modelCommission($modelCode, $discountType, $groupMarginPrice, $payType, $payAssort, $payFee);
					$response = json_decode($response, true);

					$skData = $response[skData];
					$ktData = $response[ktData];
					$lgData = $response[lgData];

					$data_info = array(
						'assortCode' => $discountType,
						'skData'     => $skData,
						'ktData'     => $ktData,
						'lgData'     => $lgData,
					);
					array_push($commiDiscountData, $data_info);

					// 요금할인
					$discountType = "C";
					$response = modelCommission($modelCode, $discountType, $groupMarginPrice, $payType, $payAssort, $payFee);
					$response = json_decode($response, true);

					$skData = $response[skData];
					$ktData = $response[ktData];
					$lgData = $response[lgData];

					$data_info = array(
						'assortCode' => $discountType,
						'skData'     => $skData,
						'ktData'     => $ktData,
						'lgData'     => $lgData,
					);
					array_push($commiDiscountData, $data_info);

					// 우선순위 수수료 선정
					$response = choiceCommission($commiDiscountData);
					$commiData = json_decode($response, true);

					// *********************** 기본요금제정보 ***********************************************************
					$charges = array();
					$sql = "SELECT hmc.telecom, hmc.chargeCode, hc.chargeName, hc.chargePrice, discountPrice  
							FROM hp_model_charge hmc 
								  inner join hp_charge hc on hmc.chargeCode = hc.chargeCode 
							WHERE hmc.modelCode = '$modelCode' and hmc.useYn = 'Y'";
					$result4 = $connect->query($sql);

					if ($result4->num_rows > 0) {
						while($row4 = mysqli_fetch_array($result4)) {
							$telecomCode = $row4[telecom];
							$chargeCode  = $row4[chargeCode];
							$chargeName  = $row4[chargeName];

							$data_info = array(
								'telecom'       => $row4[telecom],
								'chargeCode'    => $row4[chargeCode],
								'chargeName'    => $row4[chargeName],
								'chargePrice'   => $row4[chargePrice],
								'discountPrice' => $row4[discountPrice],
							);
							array_push($charges, $data_info);
						}
					}

					// 모델정보 취합
					$data_info = array(
						'modelCode'    => $modelCode,
						'modelName'    => $modelName,
						'thumbnail'    => $modelThumb,
						'colorData'    => $colorData,
						'capacityData' => $capacityData,
						'commiData'    => $commiData,
						'charges'      => $charges,
					);
					array_push($modelData, $data_info);
				}
			}
		
			// 상품정보 취합
			$data_info = array(
				'goodsCode'    => $goodsCode,
				'goodsName'    => $goodsName,
				'makerCode'    => $makerCode,
				'telecoms'     => $telecoms,
				'imtAssort'    => $imtAssort,
				'thumbnail'    => $goodsThumb,
				'modelData'    => $modelData,
			);
			array_push($goodsData, $data_info);
		}
	}

	// ************************************* 제휴카드 할인 *********************************************************
	$cardData = array();
    $sql = "SELECT no, telecom, cardCode, cardName, telecom, usePrice, discountPrice, thumbnail, cardExplain 
	        FROM ( select @a:=@a+1 no, cardCode, cardName, telecom, usePrice, discountPrice, thumbnail, cardExplain 
		           from hp_alliance_card, (select @a:= 0) AS a 
		           where useYn = 'Y' 
		         ) m 
			ORDER BY discountPrice ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[thumbnail] != "") $row[thumbnail] = $protocol . "spiderplatform.co.kr/" . $row[thumbnail];

			$data_info = array(
				'no'            => $row[no],
				'cardCode'      => $row[cardCode],
				'cardName'      => $row[cardName],
				'usePrice'      => number_format($row[usePrice]),
				'discountPrice' => number_format($row[discountPrice]),
				'cardExplain'   => $row[cardExplain],
				'thumbnail'     => $row[thumbnail],
			);
			array_push($cardData, $data_info);
		}
	}

	// ************************************* 공시지원가 *********************************************************
	$supportData = array();
    $sql = "SELECT telecom, modelCode, priceNew, priceMnp, priceChange
			FROM hp_support_price
			WHERE useYn = 'Y'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'telecom'     => $row[telecom],
				'modelCode'   => $row[modelCode],
				'priceNew'    => number_format($row[priceNew]),
				'priceMnp'    => number_format($row[priceMnp]),
				'priceChange' => number_format($row[priceChange]),
			);
			array_push($supportData, $data_info);
		}
	}

	// ************************************* 요금제 *********************************************************
	$chargeData = array();
    $sql = "SELECT telecom, imtAssort, chargeCode, chargeName, chargePrice, discountPrice, expireDayS, expireDayC, chargeExplain, builtData 
			FROM hp_charge
			WHERE useYn = 'Y'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'telecom'       => $row[telecom],
				'imtAssort'     => $row[imtAssort],
				'chargeCode'    => $row[chargeCode],
				'chargeName'    => $row[chargeName],
				'chargePrice'   => number_format($row[chargePrice]),
				'discountPrice' => number_format($row[discountPrice]),
				'expireDayS'    => $row[expireDayS],
				'expireDayC'    => $row[expireDayC],
				'chargeExplain' => $row[chargeExplain],
				'builtData'     => $row[builtData],
			);
			array_push($chargeData, $data_info);
		}
	}

	// ************************************* 부가서비스 *********************************************************
	$addServiceData = array();
    $sql = "SELECT telecom, serviceCode, serviceName, servicePrice, periodAssort, periodDay 
			FROM hp_add_service
			WHERE useYn = 'Y'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[periodAssort] != "D") $periodName = selected_object($row[periodAssort], $arrPeriodAssort);
			else $periodName = $row[periodDay] . "일";

			$data_info = array(
				'telecom'      => $row[telecom],
				'serviceCode'  => $row[serviceCode],
				'serviceName'  => $row[serviceName],
				'servicePrice' => number_format($row[servicePrice]),
				'periodDay'    => $periodName,
			);
			array_push($addServiceData, $data_info);
		}
	}

	// 할인 정보
	$discountData = array();
	$sql = "SELECT discountCode, discountName, discountPrice, discountType, allYn 
			FROM hp_discount 
			WHERE useYn = 'Y'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$discountCode  = $row[discountCode];
			$discountName  = $row[discountName];
			$discountPrice = $row[discountPrice];
			$discountType  = $row[discountType];
			$allYn         = $row[allYn];

			 // 해당 단말기 검색
			$discountModelData = array();
			$sql = "SELECT modelCode FROM hp_discount_model WHERE discountCode = '$discountCode'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					$data_info = array(
						'modelCode' => $row2[modelCode],
					);
					array_push($discountModelData, $data_info);
				}
			}

			 // 해당 요금제 검색
			$discountChargeData = array();
			$sql = "SELECT chargeCode FROM hp_discount_charge WHERE discountCode = '$discountCode'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					$data_info = array(
						'chargeCode' => $row2[chargeCode],
					);
					array_push($discountChargeData, $data_info);
				}
			}

			$data_info = array(
				'discountCode'  => $discountCode,
				'discountName'  => $discountName,
				'discountPrice' => $discountPrice,
				'discountType'  => $discountType,
				'allYn'         => $allYn,
				'models'        => $discountModelData,
				'charges'       => $discountChargeData,
			);
			array_push($discountData, $data_info);
		}
	}

	// 통신사별 할부개월
	$arrInstallmentData = array();
    $sql = "SELECT telecom, installment FROM hp_installment";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecom     = $row[telecom];
			$installment = $row[installment];

			$installmentCode = array();

			for ($n = 0; count($arrInstallment) > $n; $n++) {
				$row2 = $arrInstallment[$n];
				$code = $row2[code];
				$name = $row2[name];

				if(strpos($installment, $code) > -1) {
					$data_info = array(
						'code' => $code,
						'name' => $name,
					);
					array_push($installmentCode, $data_info);
				}
			}

			$data_info = array(
				'telecom'     => $telecom,
				'installment' => $installmentCode,
			);
			array_push($arrInstallmentData, $data_info);
		}
	}

	// 최종 결과
	$response = array(
		'markerOptions'   => $arrMakerAssort,
		'telecomOptions'  => $arrTelecomAssort,
		'supportOptions'  => $arrSupportAssort,
		'goodsData'       => $goodsData,
		'cardData'        => $cardData,
		'supportData'     => $supportData,
		'chargeData'      => $chargeData,
		'addServiceData'  => $addServiceData,
		'discountData'    => $discountData,
		'installmentData' => $arrInstallmentData,
	);
//print_r($response);
//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>