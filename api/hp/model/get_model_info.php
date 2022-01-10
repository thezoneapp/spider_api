<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 모델 정보
	* parameter ==> idx: 신청서에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, makerCode, modelCode, modelName, telecom, imtAssort, factoryPrice, installment, thumbnail, useYn 
	        FROM hp_model 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$modelCode = $row->modelCode;
		$makerName = selected_object($row->makerCode, $arrMakerAssort);
		$telecomName = selected_object($row->telecom, $arrTelecomAssort3);
		$imtName = selected_object($row->imtAssort, $arrImtAssort);

		if ($row->thumbnail == null || $row->thumbnail == "") $thumbnail = "";
		else $thumbnail = "http://spiderplatform.co.kr/upload/thumbnail/" . $row->thumbnail;

		$data = array(
			'idx'            => $row->idx,
			'makerCode'      => $row->makerCode,
			'makerName'      => $makerName,
			'makerOptions'   => $arrMakerAssort,
			'modelCode'      => $row->modelCode,
			'modelName'      => $row->modelName,
			'telecom'        => $row->telecom,
			'telecomName'    => $telecomName,
			'imtAssort'      => $row->imtAssort,
			'imtName'        => $imtName,
			'imtOptions'     => $arrImtAssort2,
			'factoryPrice'   => $row->factoryPrice,
			'installment'    => $row->installment,
			'thumbnail'      => $thumbnail,
			'useYn'          => $row->useYn,
			'useOptions'     => $arrUseAssort2,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		$modelCode = "";

		$data = array(
			'idx'          => '',
			'makerCode'    => '',
			'makerName'    => '',
			'makerOptions' => $arrMakerAssort,
			'modelCode'    => '',
			'modelName'    => '',
			'telecom'      => '',
			'telecomName'  => '',
			'imtAssort'    => '',
			'imtName'      => '',
			'imtOptions'   => $arrImtAssort2,
			'factoryPrice' => '',
			'installment'  => '',
			'thumbnail'    => '',
			'useYn'        => '',
			'useOptions'   => $arrUseAssort2,
		);

		// 추가모드로 결과를 반환합니다.
		$result_status = "1";
	}

	// ******************************************* 색상 데이타 검색  *************************************
	$colors = array();
    $sql = "SELECT idx, colorName, targetCode, useYn 
	        FROM hp_model_color 
			WHERE modelCode = '$modelCode' 
			ORDER BY idx ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$targetName = selected_object($row[targetCode], $arrTelecomAssort3);
			$useYnName = selected_object($row[useYn], $arrUseAssort);

			$target_info = array(
				'code' => $row[targetCode],
				'name' => $targetName,
			);

			$use_info = array(
				'code' => $row[useYn],
				'name' => $useYnName,
			);

			$data_info = array(
				'idx'          => $row[idx],
				'colorName'    => $row[colorName],
				'targetCode'   => $target_info,
				'colorYn'      => $use_info,
			);
			array_push($colors, $data_info);
		}
	}

	// ************************************* 용량 데이타 검색  ****************************************
	$capacitys = array();
    $sql = "SELECT idx, capacityCode, factoryPrice, useYn 
	        FROM hp_model_capacity 
			WHERE modelCode = '$modelCode' 
			ORDER BY idx ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$capacityName = selected_object($row[capacityCode], $arrCapacityAssort);
			$useYnName = selected_object($row[useYn], $arrUseAssort);

			$capacity_info = array(
				'code' => $row[capacityCode],
				'name' => $capacityName,
			);

			$use_info = array(
				'code' => $row[useYn],
				'name' => $useYnName,
			);

			$data_info = array(
				'idx'            => $row[idx],
				'capacityCode'   => $capacity_info,
				'factoryPrice'   => $row[factoryPrice],
				'capacityYn'     => $use_info
			);
			array_push($capacitys, $data_info);
		}
	}

	// ************************************* 기본요금제 데이타 검색  ************************************
	$charges = array();
    $sql = "SELECT hmc.idx, hmc.telecom, hmc.chargeCode, hc.imtAssort, hc.chargeName, hmc.useYn 
	        FROM hp_model_charge hmc 
			     inner join hp_charge hc on hmc.chargeCode = hc.chargeCode 
			WHERE hmc.modelCode = '$modelCode' 
			ORDER BY hmc.idx ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$imtName = selected_object($row[imtAssort], $arrImtAssort);
			$telecomName = selected_object($row[telecom], $arrTelecomAssort);
			$useYnName = selected_object($row[useYn], $arrUseAssort);

			$telecom_info = array(
				'code' => $row[telecom],
				'name' => $telecomName,
			);

			$charge_info = array(
				'code' => $row[chargeCode],
				'name' => $telecomName . "/" . $imtName . "/" . $row[chargeName],
			);

			$use_info = array(
				'code' => $row[useYn],
				'name' => $useYnName,
			);

			$data_info = array(
				'idx'        => $row[idx],
				'telecom'    => $telecom_info,
				'chargeCode' => $charge_info,
				'chargeYn'   => $use_info
			);
			array_push($charges, $data_info);
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
		'colors'          => $colors,
		'capacitys'       => $capacitys,
		'charges'         => $charges,
		'capacityOptions' => $arrCapacityAssort,
		'targetOptions'   => $arrTelecomAssort3,
		'telecomOptions'  => $arrTelecomAssort3,
		'chargeOptions'   => $chargeOptions,
		'ynOptions'       => $arrUseAssort,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>