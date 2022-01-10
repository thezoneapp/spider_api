<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자페이지 > 휴대폰신청 > 업체관리 > 목록 > 상세정보
	* parameter
	  idx: 해당업체에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, agencyId, agencyName, telNo, hpNo, openingForm, deliveryForm, telecoms, companyCode, companyName, useYn, wdate  
	        FROM hp_agency 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$telecoms = $row->telecoms;

		if ($row->telNo !== "") $row->telNo = aes_decode($row->telNo);
		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

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
			'idx'          => $row->idx,
			'agencyId'     => $row->agencyId,
			'agencyName'   => $row->agencyName,
			'telNo'        => $row->telNo,
			'hpNo'         => $row->hpNo,
			'openingForm'  => $row->openingForm,
			'deliveryForm' => $row->deliveryForm,
			'telecoms'    => $telecomOptions,
			'companyCode'  => $row->companyCode,
			'companyName'  => $row->companyName,
			'useYn'        => $row->useYn,
			'wdate'        => $row->wdate,
		);

    } else {
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
			'idx'          => '',
			'agencyId'     => '',
			'agencyName'   => '',
			'telNo'        => '',
			'hpNo'         => '',
			'openingForm'  => '',
			'deliveryForm' => '',
			'telecoms'     => $telecomOptions,
			'companyCode'  => '',
			'companyName'  => '',
			'useYn'        => 'Y',
			'wdate'        => '',
		);
	}

	// 택배업체 데이타 검색 
	$companyOptions = array();
    $sql = "SELECT companyCode, companyName FROM delivery_company ORDER BY companyName ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'code' => $row[companyCode],
				'name' => $row[companyName],
			);
			array_push($companyOptions, $data_info);
		}
	}

	// 성공 결과를 반환합니다.
	$result_status = "0";

	$response = array(
		'result'          => $result_status,
		'data'            => $data,
		'companyOptions'  => $companyOptions,
		'useOptions'      => $arrUseAssort2,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>