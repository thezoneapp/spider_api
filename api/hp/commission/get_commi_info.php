<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	/*
	* 휴대폰 신청 > 수수료율 정보
	* parameter ==> idx: 신청서에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};
	
	//$idx = "0";
    
	$sql = "SELECT hc.idx, hc.telecom, hc.assortCode, hc.modelCode, hm.modelName, hc.priceNew, hc.priceMnp, hc.priceChange, hc.useYn 
	        FROM hp_commi hc 
	               LEFT OUTER join hp_model hm on hc.modelCode = hm.modelCode 
			WHERE hc.idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$telecomName = selected_object($row->telecom, $arrTelecomAssort3);

		$data = array(
			'idx'            => $row->idx,
			'telecom'        => $row->telecom,
			'telecomName'    => $telecomName,
			'assortCode'     => $row->assortCode,
			'modelCode'      => $row->modelCode,
			'modelName'      => $row->modelName,
			'priceNew'       => $row->priceNew,
			'priceMnp'       => $row->priceMnp,
			'priceChange'    => $row->priceChange,
			'useYn'          => $row->useYn,
			'useName'        => $useName,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		$data = array(
			'idx'            => '',
			'telecom'        => '',
			'telecomName'    => '',
			'assortCode'     => '',
			'modelCode'      => '',
			'modelName'      => '',
			'priceNew'       => '0',
			'priceMnp'       => '0',
			'priceChange'    => '0',
			'useYn'          => 'Y',
			'useName'        => '',
		);

		// 추가모드로 결과를 반환합니다.
		$result_status = "1";
	}

	// ************************************** 단말기 정보  ******************************************
	// 기존 단말기 데이타 검색
	$modelOptions = array();
    $sql = "SELECT telecom, modelCode, modelName FROM hp_model WHERE useYn = 'Y' ORDER BY modelName ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecomName = selected_object($row[telecom], $arrTelecomAssort3);

			$data_info = array(
				'code' => $row[modelCode],
				'name' => $telecomName . "/" . $row[modelName],
			);
			array_push($modelOptions, $data_info);
		}
	}

	$response = array(
		'result'          => $result_status,
		'data'            => $data,
		'telecomOptions'  => $arrTelecomAssort,
		'assortOptions'   => $arrSupportAssort3,
		'modelOptions'    => $modelOptions,
		'useOptions'      => $arrUseAssort2,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>