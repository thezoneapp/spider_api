<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 휴대폰신청 > 요금제검색 (autocomplete용)
	*/

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT chargeCode, chargeName, telecom, imtAssort 
			FROM hp_charge 
			WHERE useYn = 'Y' 
			ORDER BY chargeName ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$imtAssortName = selected_object($row[imtAssort], $arrImtAssort);
			$telecomName = selected_object($row[telecom], $arrTelecomAssort);

			$data_info = array(
				'chargeCode' => $row[chargeCode],
				'chargeName' => $row[chargeName],
				'imtAssort'  => $imtAssortName,
				'telecom'    => $telecomName,
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
