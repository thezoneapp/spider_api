<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 신청 > 부가서비스 > 정보
	* parameter ==> idx: 신청서에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, serviceCode, serviceName, servicePrice, periodAssort, periodDay, descript, telecom, useYn 
	        FROM hp_add_service 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$telecomName = selected_object($row->telecom, $arrTelecomAssort3);

		$data = array(
			'idx'          => $row->idx,
			'serviceCode'  => $row->serviceCode,
			'serviceName'  => $row->serviceName,
			'servicePrice' => $row->servicePrice,
			'periodAssort' => $row->periodAssort,
			'periodDay'    => $row->periodDay,
			'descript'     => $row->descript,
			'telecom'      => $row->telecom,
			'telecomName'  => $telecomName,
			'useYn'        => $row->useYn,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		$modelCode = "";

		$data = array(
			'idx'          => '',
			'serviceCode'  => '',
			'serviceName'  => '',
			'servicePrice' => '',
			'periodAssort' => '',
			'periodDay'    => '',
			'descript'     => '',
			'telecom'      => '',
			'telecomName'  => '',
			'useYn'        => 'Y',
		);

		// 추가모드로 결과를 반환합니다.
		$result_status = "1";
	}

	$response = array(
		'result'          => $result_status,
		'data'            => $data,
		'telecomOptions'  => $arrTelecomAssort,
		'useOptions'      => $arrUseAssort,
		'periodOptions'   => $arrPeriodAssort,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>