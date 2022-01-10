<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 요금제 정보
	* parameter ==> idx: 신청서에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, chargeCode, chargeName, chargePrice, discountPrice, imtAssort, telecom, expireDayS, expireDayC, chargeExplain, useYn 
	        FROM hp_charge 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$imtName = selected_object($row->imtAssort, $arrImtAssort);
		$telecomName = selected_object($row->telecom, $arrTelecomAssort);
		$useName = selected_object($row->useYn, $arrUseAssort);

		if ($row->builtData == null) $row->builtData = "";

		$data = array(
			'idx'           => $row->idx,
			'chargeCode'    => $row->chargeCode,
			'chargeName'    => $row->chargeName,
			'chargePrice'   => $row->chargePrice,
			'discountPrice' => $row->discountPrice,
			'imtAssort'     => $row->imtAssort,
			'imtName'       => $imtName,
			'imtOptions'    => $arrImtAssort,
			'telecom'       => $row->telecom,
			'telecomName'   => $telecomName,
			'telecomOptions'=> $arrTelecomAssort,
			'expireDayS'    => $row->expireDayS,
			'expireDayC'    => $row->expireDayC,
			'chargeExplain' => $row->chargeExplain,
			'useYn'         => $row->useYn,
			'useName'       => $useName,
			'useOptions'    => $arrUseAssort,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		$data = array(
			'idx'           => '',
			'chargeCode'    => '',
			'chargeName'    => '',
			'chargePrice'   => '',
			'discountPrice' => '',
			'imtAssort'     => '5',
			'imtName'       => '',
			'imtOptions'    => $arrImtAssort,
			'telecom'       => '',
			'telecomName'   => '',
			'telecomOptions'=> $arrTelecomAssort,
			'expireDayS'    => '',
			'expireDayC'    => '',
			'chargeExplain' => '',
			'useYn'         => 'Y',
			'useName'       => '',
			'useOptions'    => $arrUseAssort,
		);

		// 추가모드로 결과를 반환합니다.
		$result_status = "1";
	}

	$response = array(
		'result'          => $result_status,
		'data'            => $data,
		'imtOptions'      => $arrImtAssort,
		'telecomOptions'  => $arrTelecomAssort,
		'useOptions'      => $arrUseAssort,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>