<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 > 상품 > 목록 > 상세정보
	* parameter ==> idx: 신청서에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

	$protocol = isset($_SERVER['HTTPS']) ? "https://" : "http://";

    $sql = "SELECT idx, goodsCode, goodsName, makerCode, telecoms, imtAssort, thumbnail, content, useYn 
	        FROM hp_goods 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$goodsCode = $row->goodsCode;
		$telecoms = $row->telecoms;
		$makerName = selected_object($row->makerCode, $arrMakerAssort);
		$imtName = selected_object($row->imtAssort, $arrImtAssort);

		//$goodsThumb = $protocol . "spiderplatform.co.kr/". $row->thumbnail;

		// 통신사
		$telecomOptions = array();

		for ($i=0; $i < count($arrTelecomAssort); $i++) {
			$code = $arrTelecomAssort[$i]["code"];
			$name = $arrTelecomAssort[$i]["name"];

			if(strpos($telecoms, $code) !== false) $checked = true;
			else $checked = false;

			$data_info = array(
				'code'    => $code,
				'name'    => $name,
				'checked' => $checked,
			);
			array_push($telecomOptions, $data_info);
		}

		$data = array(
			'idx'         => $row->idx,
			'goodsCode'   => $row->goodsCode,
			'goodsName'   => $row->goodsName,
			'makerCode'   => $row->makerCode,
			'makerName'   => $makerName,
			'telecoms'    => $telecomOptions,
			'imtAssort'   => $row->imtAssort,
			'imtName'     => $imtName,
			'thumbnail'   => $row->thumbnail,
			'content'     => $row->content,
			'useYn'       => $row->useYn,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		$goodsCode = "";
		$telecomOptions = array();

		for ($i=0; $i < count($arrTelecomAssort); $i++) {
			$data_info = array(
				'code'    => $arrTelecomAssort[$i]["code"],
				'name'    => $arrTelecomAssort[$i]["name"],
				'checked' => false,
			);
			array_push($telecomOptions, $data_info);
		}

		$data = array(
			'idx'         => '',
			'goodsCode'   => '',
			'goodsName'   => '',
			'makerCode'   => '',
			'makerName'   => '',
			'telecoms'    => $telecomOptions,
			'imtAssort'   => '',
			'imtName'     => '',
			'thumbnail'   => '',
			'content'     => '',
			'useYn'       => '',
		);

		// 추가모드로 결과를 반환합니다.
		$result_status = "1";
	}

	// ******************************************* 모델 정보  *************************************
	$models = array();
    $sql = "SELECT idx, modelCode, modelName, thumbnail, useYn 
            FROM hp_model 
			WHERE goodsCode = '$goodsCode' 
			ORDER BY idx ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$modelIdx   = $row[idx];
			$modelCode  = $row[modelCode];
			$modelName  = $row[modelName];
			$modelYn    = $row[useYn];
			$modelThumb = $row[thumbnail];
			//$modelThumb = $protocol . "spiderplatform.co.kr/". $row[thumbnail];

			// 용량정보
			$capacitys = array();
			$sql = "SELECT idx, capacityCode, factoryPrice, useYn 
					FROM hp_model_capacity 
					WHERE modelCode = '$modelCode' 
					ORDER BY idx ASC";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					$capacityIdx  = $row2[idx];
					$capacityCode = $row2[capacityCode];
					$factoryPrice = $row2[factoryPrice];
					$capacityYn   = $row2[useYn];

					$capacityName = selected_object($capacityCode, $arrCapacityAssort);
					$useName = selected_object($capacityYn, $arrUseAssort);

					$capacity_info = array(
						'code'    => $capacityCode,
						'name'    => $capacityName,
					);

					$use_info = array(
						'code'    => $capacityYn,
						'name'    => $useName,
					);

					$data_info = array(
						'idx'          => $capacityIdx,
						'capacityCode' => $capacity_info,
						'factoryPrice' => $factoryPrice,
						'capacityYn'   => $use_info,
					);
					array_push($capacitys, $data_info);
				}
			}

			// 색상정보
			$colors = array();
			$sql = "SELECT idx, colorName, telecoms, useYn 
					FROM hp_model_color 
					WHERE modelCode = '$modelCode' 
					ORDER BY idx ASC";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					$idx        = $row2[idx];
					$colorName  = $row2[colorName];
					$telecoms   = $row2[telecoms];
					$colorYn    = $row2[useYn];

					$useName = selected_object($colorYn, $arrUseAssort);

					// 색상 통신사
					$colorTelecoms = array();

					for ($i=0; $i < count($arrTelecomAssort); $i++) {
						$code = $arrTelecomAssort[$i]["code"];
						$name = $arrTelecomAssort[$i]["name"];

						if(strpos($telecoms, $code) !== false) $checked = true;
						else $checked = false;

						$telecom_info = array(
							'code'    => $code,
							'name'    => $name,
							'checked' => $checked,
						);
						array_push($colorTelecoms, $telecom_info);
					}

					$use_info = array(
						'code'    => $colorYn,
						'name'    => $useName,
					);

					$data_info = array(
						'idx'           => $idx,
						'colorName'     => $colorName,
						'colorTelecoms' => $colorTelecoms,
						'colorYn'       => $use_info,
					);
					array_push($colors, $data_info);
				}
			}

			// 기본요금제정보
			$charges = array();
			$sql = "SELECT hmc.idx, hmc.telecom, hmc.chargeCode, hc.chargeName, hmc.useYn 
					FROM hp_model_charge hmc 
					     inner join hp_charge hc on hmc.chargeCode = hc.chargeCode 
					WHERE hmc.modelCode = '$modelCode' 
					ORDER BY hmc.idx ASC";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					$chargeIdx   = $row2[idx];
					$telecomCode = $row2[telecom];
					$chargeCode  = $row2[chargeCode];
					$chargeName  = $row2[chargeName];
					$capacityYn  = $row2[useYn];

					$telecomName = selected_object($telecomCode, $arrTelecomAssort);
					$useName = selected_object($capacityYn, $arrUseAssort);

					$telecom_info = array(
						'code'    => $telecomCode,
						'name'    => $telecomName,
					);

					$charge_info = array(
						'code'    => $chargeCode,
						'name'    => $chargeName,
					);

					$use_info = array(
						'code'    => $capacityYn,
						'name'    => $useName,
					);

					$data_info = array(
						'idx'           => $chargeIdx,
						'chargeTelecom' => $telecom_info,
						'chargeCode'    => $charge_info,
						'chargeYn'      => $use_info,
					);
					array_push($charges, $data_info);
				}
			}

			$useName = selected_object($modelYn, $arrUseAssort);

			$use_info = array(
				'code'    => $modelYn,
				'name'    => $useName,
			);

			$data_info = array(
				'idx'        => $modelIdx,
				'modelCode'  => $modelCode,
				'modelName'  => $modelName,
				'modelThumb' => $modelThumb,
				'modelYn'    => $use_info,
				'capacitys'  => $capacitys,
				'colors'     => $colors,
				'charges'    => $charges,
			);
			array_push($models, $data_info);
		}
	}

	// 요금제 정보
	$chargeOptions = array();
    $sql = "SELECT chargeCode, chargeName, imtAssort, telecom 
	        FROM hp_charge 
			WHERE useYn = 'Y' 
			ORDER BY telecom ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$imtName = selected_object($row[imtAssort], $arrImtAssort);
			$telecomName = selected_object($row[telecom], $arrTelecomAssort);

			$data_info = array(
				'code' => $row[chargeCode],
				'name' => $telecomName . "/" . $imtName . "/" . $row[chargeName]
			);
			array_push($chargeOptions, $data_info);
		}
	}

	$response = array(
		'result'          => $result_status,
		'data'            => $data,
		'models'          => $models,
		'makerOptions'    => $arrMakerAssort,
		'imtOptions'      => $arrImtAssort2,
		'telecomOptions'  => $arrTelecomAssort,
		'capacityOptions' => $arrCapacityAssort,
		'chargeOptions'   => $chargeOptions,
		'useOptions'      => $arrUseAssort,
		'useOptions2'     => $arrUseAssort2,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>