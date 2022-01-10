<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰모델 추가/수정
	* parameter ==> mode:           insert(추가), update(수정)
	* parameter ==> idx:            수정할 레코드 id
	* parameter ==> makerCode:      제조사코드
	* parameter ==> modelCode:      모델코드
	* parameter ==> modelName:      모델명
	* parameter ==> telecom:        통신사코드
	* parameter ==> imtAssort:      통신망구분
	* parameter ==> factoryPrice:   출고가격 (사용안함)
	* parameter ==> installment:    할부개월
	* parameter ==> thumbnail:      썸네일
	* parameter ==> useYn:          사용여부

	* parameter ==> colors:         색상배열
	* parameter ==> capacitys:      용량배열
	* parameter ==> charges:        요금제배열
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$mode         = $data_back->{'mode'};
	$idx          = $data_back->{'idx'};
	$makerCode    = $data_back->{'makerCode'};
	$modelCode    = $data_back->{'modelCode'};
	$modelName    = $data_back->{'modelName'};
	$telecom      = $data_back->{'telecom'};
	$imtAssort    = $data_back->{'imtAssort'};
	//$factoryPrice = $data_back->{'factoryPrice'};
	$installment  = $data_back->{'installment'};
	$thumbnail    = $data_back->{'thumbnail'};
	$useYn        = $data_back->{'useYn'};

	$makerCode = $makerCode->{'code'};
	$telecom   = $telecom->{'code'};
	$colors    = $data_back->{'colors'};
	$capacitys = $data_back->{'capacitys'};
	$charges   = $data_back->{'charges'};

	if ($mode == "insert") {
		$sql = "SELECT idx FROM hp_model WHERE modelCode = '$modelCode'";
		$result = $connect->query($sql);

	    if ($result->num_rows == 0) {
			$sql = "INSERT INTO hp_model (makerCode, modelCode, modelName, telecom, imtAssort, installment, thumbnail, useYn)
							      VALUES ('$makerCode', '$modelCode', '$modelName', '$telecom', '$imtAssort', '$installment', '$thumbnail', '$useYn')";
			$connect->query($sql);

			// 성공 결과를 반환합니다.
			$result_status = "0";
			$result_message = "등록하였습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "중복된 '모델코드'입니다.";
		}

	} else {
		if ($thumbnail == null || $thumbnail == "") $thumbnail_update = "";
		else $thumbnail_update = ", thumbnail = '$thumbnail' ";

		$sql = "UPDATE hp_model SET makerCode   = '$makerCode',
		                            modelCode   = '$modelCode', 
								    modelName   = '$modelName', 
									telecom     = '$telecom', 
								    imtAssort   = '$imtAssort', 
									installment = '$installment', 
								    useYn       = '$useYn' 
								    $thumbnail_update 
						WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "변경하였습니다.";
	}

	// ******************************** 용량 옵션 *********************************************
	$sql = "UPDATE hp_model_capacity SET updateCheck = 'Y' WHERE modelCode = '$modelCode'";
	$connect->query($sql);

	for ($i = 0; count($capacitys) > $i; $i++) {
		$capacity = $capacitys[$i];
		$idx = $capacity->{'idx'};

		$capacityCode = $capacity->{'capacityCode'};
		$capacityCode = $capacityCode->{'code'};

		$factoryPrice = $capacity->{'factoryPrice'};

		$capacityYn = $capacity->{'capacityYn'};
		$useYn = $capacityYn->{'code'};

		if ($idx == "0") {
			$sql = "INSERT INTO hp_model_capacity (modelCode, capacityCode, factoryPrice, useYn) 
			                            VALUES ('$modelCode', '$capacityCode', '$factoryPrice', '$useYn')";
			$connect->query($sql);

		} else {
			$sql = "UPDATE hp_model_capacity SET modelCode = '$modelCode', 
			                                     capacityCode = '$capacityCode', 
												 factoryPrice = '$factoryPrice', 
											     useYn = '$useYn',
											     updateCheck = null 
					WHERE idx = '$idx'";
			$connect->query($sql);
		}
	}

	$sql = "DELETE FROM hp_model_capacity WHERE modelCode = '$modelCode' and updateCheck = 'Y'";
	$connect->query($sql);

	// ************************************* 색상 옵션 ****************************************
	$sql = "UPDATE hp_model_color SET updateCheck = 'Y' WHERE modelCode = '$modelCode'";
	$connect->query($sql);

	for ($i = 0; count($colors) > $i; $i++) {
		$color = $colors[$i];
		$idx = $color->{'idx'};
		$colorName = $color->{'colorName'};

		$target = $color->{'targetCode'};
		$targetCode = $target->{'code'};

		$colorYn = $color->{'colorYn'};
		$useYn = $colorYn->{'code'};

		if ($idx == "0") {
			$sql = "INSERT INTO hp_model_color (modelCode, colorName, targetCode, useYn) 
			                            VALUES ('$modelCode', '$colorName', '$targetCode', '$useYn')";
			$connect->query($sql);

		} else {
			$sql = "UPDATE hp_model_color SET modelCode = '$modelCode', 
			                                  colorName = '$colorName', 
											  targetCode = '$targetCode',
											  useYn = '$useYn',
											  updateCheck = null 
					WHERE idx = '$idx'";
			$connect->query($sql);
		}
	}

	$sql = "DELETE FROM hp_model_color WHERE modelCode = '$modelCode' and updateCheck = 'Y'";
	$connect->query($sql);

	// ********************************** 요금제 옵션 *****************************************
	$sql = "UPDATE hp_model_charge SET updateCheck = 'Y' WHERE modelCode = '$modelCode'";
	$connect->query($sql);

	for ($i = 0; count($charges) > $i; $i++) {
		$charge = $charges[$i];
		$idx = $charge->{'idx'};

		$telecom = $charge->{'telecom'};
		$telecom = $telecom->{'code'};

		$chargeCode = $charge->{'chargeCode'};
		$chargeCode = $chargeCode->{'code'};

		$chargeYn = $charge->{'chargeYn'};
		$useYn = $chargeYn->{'code'};

		if ($idx == "0") {
			$sql = "INSERT INTO hp_model_charge (modelCode, telecom, chargeCode, useYn) 
			                            VALUES ('$modelCode', '$telecom', '$chargeCode', '$useYn')";
			$connect->query($sql);

		} else {
			$sql = "UPDATE hp_model_charge SET modelCode = '$modelCode', 
			                                   telecom = '$telecom', 
											   chargeCode = '$chargeCode', 
											   useYn = '$useYn',
											   updateCheck = null 
					WHERE idx = '$idx'";
			$connect->query($sql);
		}
	}

	$sql = "DELETE FROM hp_model_charge WHERE modelCode = '$modelCode' and updateCheck = 'Y'";
	$connect->query($sql);


	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>