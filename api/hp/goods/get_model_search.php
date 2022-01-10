<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 휴대폰신청 > 모델검색 (autocomplete용)
	*/

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT hm.modelCode, hm.modelName, hg.telecoms, hg.imtAssort
			FROM hp_model hm 
				 INNER JOIN hp_goods hg ON hm.goodsCode = hg.goodsCode 
			WHERE hm.useYn = 'Y' 
			ORDER BY hm.modelName ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$imtAssortName = selected_object($row[imtAssort], $arrImtAssort);

			$telecoms = "";
			$arrTelecoms = explode(",", $row[telecoms]);

			for ($i=0; $i < count($arrTelecoms); $i++) {
				$telecom = $arrTelecoms[$i];
				$telecomName = selected_object($telecom, $arrTelecomAssort);

				if ($telecoms != "") $telecoms .= ",";
				$telecoms .= $telecomName;
			}

			$data_info = array(
				'modelCode' => $row[modelCode],
				'modelName' => $row[modelName],
				'imtAssort' => $imtAssortName,
				'telecom'   => $telecoms,
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	$response = array(
		'result' => $result_status,
		'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
