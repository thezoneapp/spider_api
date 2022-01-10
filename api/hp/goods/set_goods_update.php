<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 > 상품정보 > 추가/수정
	* parameter ==> mode:           insert(추가), update(수정)
	* parameter ==> idx:            수정할 레코드 id
	* parameter ==> goodsCode:      상품코드
	* parameter ==> goodsName:      상품명
	* parameter ==> modelName:      모델명
	* parameter ==> makerCode:      제조사코드
	* parameter ==> telecoms:       통신사
	* parameter ==> imtAssort:      통신망구분
	* parameter ==> thumbnail:      썸네일
	* parameter ==> content:        상품설명
	* parameter ==> useYn:          사용여부
	* parameter ==> models:         모델정보
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$mode        = $data_back->{'mode'};
	$idx         = $data_back->{'idx'};
	$goodsCode   = $data_back->{'goodsCode'};
	$goodsName   = $data_back->{'goodsName'};
	$makerCode   = $data_back->{'makerCode'};
	$arrTelecoms = $data_back->{'telecoms'};
	$imtAssort   = $data_back->{'imtAssort'};
	$thumbnail   = $data_back->{'thumbnail'};
	$content     = $data_back->{'content'};
	$thumbnail   = $data_back->{'thumbnail'};
	$useYn       = $data_back->{'useYn'};
	$arrModels   = $data_back->{'models'};

	$makerCode = $makerCode->{'code'};

	// 상품 통신사
	$telecoms = "";
	for ($i=0; $i < count($arrTelecoms); $i++) {
		$telecom = $arrTelecoms[$i];
		
		$telecomCode = $telecom->code;
		$checked     = $telecom->checked;

		if ($checked) {
			if ($telecoms != "") $telecoms .= ",";
			$telecoms .= $telecomCode;
		}
	}

	if ($mode == "insert") {
		$sql = "SELECT idx FROM hp_goods WHERE goodsCode = '$goodsCode'";
		$result = $connect->query($sql);

	    if ($result->num_rows == 0) {
			$sql = "INSERT INTO hp_goods (goodsCode, goodsName, makerCode, telecoms, imtAssort, thumbnail, content, useYn)
							      VALUES ('$goodsCode', '$goodsName', '$makerCode', '$telecoms', '$imtAssort', '$thumbnail', '$content', '$useYn')";
			$connect->query($sql);

			// 성공 결과를 반환합니다.
			$result_status = "0";
			$result_message = "등록하였습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "중복된 '상품코드'입니다.";
		}

	} else {
		if ($thumbnail == null || $thumbnail == "") $thumbnail_update = "";
		else $thumbnail_update = ", thumbnail = '$thumbnail' ";

		$sql = "UPDATE hp_goods SET goodsName = '$goodsName', 
								   makerCode = '$makerCode', 
								   telecoms  = '$telecoms', 
								   imtAssort = '$imtAssort', 
								   content   = '$content', 
								   useYn     = '$useYn' 
								   $thumbnail_update 
						WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "변경하였습니다.";
	}

	// 상품정보에 이상이 없으면
	if ($result_status == "0") {
		// ******************************** 모델정보 *********************************************
		$sql = "UPDATE hp_model SET updateCheck = 'Y' WHERE goodsCode = '$goodsCode'";
		$connect->query($sql);

		for ($i = 0; count($arrModels) > $i; $i++) {
			$model = $arrModels[$i];

			$idx       = $model->idx;
			$modelCode = $model->modelCode;
			$modelName = $model->modelName;
			$thumbnail = $model->modelThumb;
			$modelYn   = $model->modelYn;
			$useYn     = $modelYn->code;

			$arrCapacitys = $model->capacitys;
			$arrColors    = $model->colors;
			$arrCharges   = $model->charges;

			if ($idx == 0) {
				$sql = "INSERT INTO hp_model (goodsCode, modelCode, modelName, thumbnail, useYn, updateCheck)
									  VALUES ('$goodsCode', '$modelCode', '$modelName', '$thumbnail', '$useYn', 'N')";
				$connect->query($sql);

			} else {
				if ($thumbnail == null || $thumbnail == "") $thumbnail_update = "";
				else $thumbnail_update = ", thumbnail = '$thumbnail' ";

				$sql = "UPDATE hp_model SET modelName   = '$modelName', 
											useYn       = '$useYn', 
											updateCheck = 'N' 
										    $thumbnail_update 
								WHERE idx = '$idx'";
				$connect->query($sql);
			}

			// -------------------------------- 용량정보 ---------------------------------------------------
			$sql = "UPDATE hp_model_capacity SET updateCheck = 'Y' WHERE modelCode = '$modelCode'";
			$connect->query($sql);

			for ($n = 0; count($arrCapacitys) > $n; $n++) {
				$capacity = $arrCapacitys[$n];

				$idx          = $capacity->idx;
				$capacityCode = $capacity->capacityCode;
				$factoryPrice = $capacity->factoryPrice;
				$capacityYn   = $capacity->capacityYn;

				$capacityCode = $capacityCode->code;
				$useYn        = $capacityYn->code;

				if ($idx == 0) {
					$sql = "INSERT INTO hp_model_capacity (modelCode, capacityCode, factoryPrice, useYn, updateCheck)
												   VALUES ('$modelCode', '$capacityCode', '$factoryPrice', '$useYn', 'N')";
					$connect->query($sql);

				} else {
					$sql = "UPDATE hp_model_capacity SET factoryPrice = '$factoryPrice', 
														 useYn        = '$useYn', 
														 updateCheck  = 'N' 
									WHERE idx = '$idx'";
					$connect->query($sql);
				}
			}

			$sql = "DELETE FROM hp_model_capacity WHERE modelCode = '$modelCode' and updateCheck = 'Y'";
			$connect->query($sql);

			// ------------------------------- 색상정보 ---------------------------------------------------
			$sql = "UPDATE hp_model_color SET updateCheck = 'Y' WHERE modelCode = '$modelCode'";
			$connect->query($sql);

			for ($n = 0; count($arrColors) > $n; $n++) {
				$color = $arrColors[$n];

				$idx         = $color->idx;
				$colorName   = $color->colorName;
				$arrTelecoms = $color->colorTelecoms;
				$colorYn     = $color->colorYn;
				$useYn       = $colorYn->code;

				$telecoms = "";
				for ($k = 0; $k < count($arrTelecoms); $k++) {
					$telecom = $arrTelecoms[$k];
					
					$telecomCode = $telecom->code;
					$checked     = $telecom->checked;

					if ($checked) {
						if ($telecoms != "") $telecoms .= ",";
						$telecoms .= $telecomCode;
					}
				}

				if ($idx == 0) {
					$sql = "INSERT INTO hp_model_color (modelCode, colorName, telecoms, useYn, updateCheck)
												VALUES ('$modelCode', '$colorName', '$telecoms', '$useYn', 'N')";
					$connect->query($sql);

				} else {
					$sql = "UPDATE hp_model_color SET colorName   = '$colorName', 
													  telecoms    = '$telecoms', 
													  useYn       = '$useYn', 
													  updateCheck = 'N' 
									WHERE idx = '$idx'";
					$connect->query($sql);
				}
			}

			$sql = "DELETE FROM hp_model_color WHERE modelCode = '$modelCode' and updateCheck = 'Y'";
			$connect->query($sql);

			// --------------------- 기본요금제정보 -------------------------------------------------------
			$sql = "UPDATE hp_model_charge SET updateCheck = 'Y' WHERE modelCode = '$modelCode'";
			$connect->query($sql);

			for ($n = 0; count($arrCharges) > $n; $n++) {
				$charge = $arrCharges[$n];

				$idx           = $charge->idx;
				$chargeTelecom = $charge->chargeTelecom;
				$chargeCode    = $charge->chargeCode;
				$chargeYn      = $charge->chargeYn;

				$telecom       = $chargeTelecom->code;
				$chargeCode    = $chargeCode->code;
				$useYn         = $chargeYn->code;

				if ($idx == 0) {
					$sql = "INSERT INTO hp_model_charge (modelCode, telecom, chargeCode, useYn, updateCheck)
												 VALUES ('$modelCode', '$telecom', '$chargeCode', '$useYn', 'N')";
					$connect->query($sql);

				} else {
					$sql = "UPDATE hp_model_charge SET chargeCode = '$chargeCode', 
													   useYn        = '$useYn', 
													   updateCheck  = 'N' 
									WHERE idx = '$idx'";
					$connect->query($sql);
				}
			}

			$sql = "DELETE FROM hp_model_charge WHERE modelCode = '$modelCode' and updateCheck = 'Y'";
			$connect->query($sql);
		}

		$sql = "DELETE FROM hp_model WHERE goodsCode = '$goodsCode' and updateCheck = 'Y'";
		$connect->query($sql);
	}

	// 결과 리턴
	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>