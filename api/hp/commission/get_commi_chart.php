<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/hpCommission.php";

	/*
	* 휴대폰 신청 > 수수료관리 > 수수료율표
	* parameter 
		discountType: 약정구분(S: 공지지원, C: 요금할인)
		searchValue:  검색어
	*/
	$input_data   = json_decode(file_get_contents('php://input'));
	$discountType = $input_data->{'discountType'};
	$searchValue  = $input_data->{'searchValue'};

	if ($discountType == "") $discountType = "S";

	if ($searchValue == null || $searchValue == "") $search_sql = "";
	else $search_sql = "and (modelCode like '%$searchValue%' or modelName like '%$searchValue%') ";
	
	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT modelCode, modelName 
			FROM hp_model 
			WHERE useYn = 'Y' $search_sql 
			ORDER BY modelName ASC";
	$result = $connect->query($sql);
	$total = $result->num_rows;

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$modelCode = $row[modelCode];
			$modelName = $row[modelName];

			$response = modelCommission($modelCode, $discountType, "", "", "", "");
			$response = json_decode($response, true);

			$skData = $response[skData];
			$ktData = $response[ktData];
			$lgData = $response[lgData];

			$data_info = array(
				'modelCode'    => $modelCode,
				'modelName'    => $modelName,
				'skData'       => $skData,
				'ktData'       => $ktData,
				'lgData'       => $lgData,
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
		'result'    => $result_status,
		'rowTotal'  => $total,
		'data'      => $data,
    );
//print_r($response);
//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
