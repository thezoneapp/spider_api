<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 > 할인제 정보
	* parameter ==> idx: 신청서에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, discountCode, discountName, discountPrice, discountType, allYn, useYn 
	        FROM hp_discount 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$discountCode = $row->discountCode;
		$allName = selected_object($row->allYn, $arrAllYnAssort);
		$useName = selected_object($row->useYn, $arrUseAssort);

		$data = array(
			'idx'           => $row->idx,
			'discountCode'  => $row->discountCode,
			'discountName'  => $row->discountName,
			'discountPrice' => $row->discountPrice,
			'discountType'  => $row->discountType,
			'typeOptions'   => $arrDiscountType2,
			'allYn'         => $row->allYn,
			'allName'       => $allName,
			'allOptions'    => $arrAllYnAssort2,
			'useYn'         => $row->useYn,
			'useName'       => $useName,
			'useOptions'    => $arrUseAssort2,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		$discountCode = "";

		$data = array(
			'idx'           => '',
			'discountCode'  => '',
			'discountName'  => '',
			'discountPrice' => '',
			'discountType'  => 'M',
			'typeOptions'   => $arrDiscountType2,
			'allYn'         => 'Y',
			'allName'       => '',
			'allOptions'    => $arrAllYnAssort2,
			'useYn'         => 'Y',
			'useName'       => '',
			'useOptions'    => $arrUseAssort2,
		);

		// 추가모드로 결과를 반환합니다.
		$result_status = "1";
	}

	// ************************************** 단말기 정보  ******************************************
	// 기존 단말기 데이타 검색
	$models = array();
    $sql = "SELECT hdm.idx, hdm.modelCode, hm.modelName, hdm.useYn 
	        FROM hp_discount_model hdm 
			     left outer join hp_model hm on hdm.modelCode = hm.modelCode 
			WHERE hdm.discountCode = '$discountCode' 
			ORDER BY hdm.idx ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'idx'       => $row[idx],
				'modelCode' => $row[modelCode],
				'modelName' => $row[modelName],
			);
			array_push($models, $data_info);
		}
	}

	// 모든 단말기 옵션
	$modelOptions = array();
    $sql = "SELECT modelCode, modelName 
	        FROM hp_model 
			WHERE useYn = 'Y' 
			ORDER BY modelName ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'code' => $row[modelCode],
				'name' => $row[modelName]
			);
			array_push($modelOptions, $data_info);
		}
	}

	// ************************************** 요금제 정보  ******************************************
	// 기존 요금제 데이타 검색
	$charges = array();
    $sql = "SELECT hdc.idx, hdc.chargeCode, hc.imtAssort, hc.telecom, hc.chargeName, hdc.useYn 
	        FROM hp_discount_charge hdc 
			     left outer join hp_charge hc on hdc.chargeCode = hc.chargeCode 
			WHERE hdc.discountCode = '$discountCode' 
			ORDER BY hdc.idx ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$imtName = selected_object($row[imtAssort], $arrImtAssort);
			$telecomName = selected_object($row[telecom], $arrTelecomAssort);

			$data_info = array(
				'idx'        => $row[idx],
				'chargeCode' => $row[chargeCode],
				'chargeName' => $row[chargeName],
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
				'name' => $telecomName . "/" . $imtName . "/" . $row[chargeName],
			);
			array_push($chargeOptions, $data_info);
		}
	}

	$response = array(
		'result'        => $result_status,
		'data'          => $data,
		'assortOptions' => $arrDiscountAssort2,
		'allOptions'    => $arrAllYnAssort2,
		'useOptions'    => $arrUseAssort2,
		'models'        => $models,
		'modelOptions'  => $modelOptions,
		'charges'       => $charges,
		'chargeOptions' => $chargeOptions,
		'ynOptions'     => $arrUseAssort,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>