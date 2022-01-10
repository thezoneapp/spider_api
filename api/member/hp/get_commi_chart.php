<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/hpCommission.php";

	/*
	* 회원 > 휴대폰 신청 > 수수료율표
	* parameter 
	    memId       : 회원ID
		discountType: 약정구분(S: 공지지원, C: 요금할인)
		payType     : 납부방식S: CMS정기결제, C: 이용수수료)
		searchValue:  검색어
	*/
	$input_data   = json_decode(file_get_contents('php://input'));
	$memId        = $input_data->{'memId'};
	$discountType = $input_data->{'discountType'};
	$payType      = $input_data->{'payType'};
	$searchValue  = $input_data->{'searchValue'};

	//$memId = "a27233377";

	if ($discountType == "") $discountType = "S";
	if ($payType == "") $payType = "C";

	if ($searchValue == null || $searchValue == "") $search_sql = "";
	else $search_sql = "and (modelCode like '%$searchValue%' or modelName like '%$searchValue%') ";

	// 회원정보
	$sql = "SELECT groupCode, organizeCode FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$groupCode    = $row->groupCode;
	$organizeCode = $row->organizeCode;

	// 그룹 마진 정보
	$groupMarginPrice = 0;
	$sql = "SELECT hpPrice FROM group_commi WHERE groupCode = '$groupCode'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$groupMarginPrice = $row->hpPrice;
	}

	// 서비스이용료 > 납부방식 > 이용수수료
	if ($payType == "C") {
		// 그룹정보 > 회원구성정보 > 서비스정보
		$sql = "SELECT commiType, totalPayAssort, totalPayFee, hpPayAssort, hpPayFee 
				FROM group_organize_service 
				WHERE groupCode = '$groupCode' AND organizeCode = '$organizeCode'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		if ($row->commiType == "B") { // 이용수수료(일괄적용)
			$payAssort = $row->totalPayAssort;
			$payFee    = $row->totalPayFee;

		} else if ($row->commiType == "E") { // 이용수수료(건별적용)
			$payAssort = $row->hpPayAssort;
			$payFee    = $row->hpPayFee;
		}
	}

	// 모델 검색 
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

			$response = modelCommission($modelCode, $discountType, $groupMarginPrice, $payType, $payAssort, $payFee);
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
