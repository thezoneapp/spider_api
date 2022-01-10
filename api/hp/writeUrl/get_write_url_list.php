<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 휴대폰신청 > 가입신청서URL > 목록
	* parameter
		page:           해당페이지
		rows:           페이지당 행의 갯수
		telecom:        통신사코드
		requestAssort:  가입유형
		installment:    할부개월
		discountType:   약정유형
		searchValue:    검색값
	*/
	$input_data    = json_decode(file_get_contents('php://input'));
	$page          = $input_data->{'page'};
	$rows          = $input_data->{'rows'};
	$telecom       = $input_data->{'telecom'};
	$requestAssort = $input_data->{'requestAssort'};
	$installment   = $input_data->{'installment'};
	$discountType  = $input_data->{'discountType'};
	$searchValue   = $input_data->{'searchValue'};

	$telecom       = $telecom->{'code'};
	$requestAssort = $requestAssort->{'code'};
	$installment   = $installment->{'code'};
	$discountType  = $discountType->{'code'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($telecom == null || $telecom == "") $telecom_sql = "";
	else $telecom_sql = "and hwu.telecom = '$telecom' ";

	if ($requestAssort == null || $requestAssort == "") $assort_sql = "";
	else $assort_sql = "and hwu.requestAssort = '$requestAssort' ";

	if ($installment == null || $installment == "") $installment_sql = "";
	else $installment_sql = "and hwu.installment = '$installment' ";

	if ($discountType == null || $discountType == "") $discountType_sql = "";
	else $discountType_sql = "and hwu.discountType = '$discountType' ";

	if ($searchValue == null || $searchValue == "") $search_sql = "";
	else $search_sql = "and (hwu.modelCode like '%$searchValue%' or hm.modelName like '%$searchValue%') ";

	// 전체 데이타 갯수
    $sql = "SELECT hwu.idx 
			FROM hp_write_url hwu
			     INNER JOIN hp_model hm ON hwu.modelCode = hm.modelCode
	        WHERE hwu.idx > 0 $telecom_sql $assort_sql $installment_sql $discountType_sql $search_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT idx, modelCode, modelName, telecom, requestAssort, installment, discountType, writeUrl 
			FROM ( select hwu.idx, hwu.modelCode, hm.modelName, hwu.telecom, hwu.requestAssort, hwu.installment, hwu.discountType, hwu.writeUrl 
					 from hp_write_url hwu 
						  INNER JOIN hp_model hm ON hwu.modelCode = hm.modelCode 
					 where hwu.idx > 0 $telecom_sql $assort_sql $installment_sql $discountType_sql $search_sql 
				  ) t 
			ORDER BY modelCode, modelName, telecom, requestAssort, installment, discountType DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecomName = selected_object($row[telecom], $arrTelecomAssort3);
			$assortName = selected_object($row[requestAssort], $arrRequestAssort);
			$installmentName = selected_object($row[installment], $arrInstallmentOptions);
			$discountName = selected_object($row[discountType], $arrSupportAssort2);

			$data_info = array(
				'idx'           => $row[idx],
				'modelCode'     => $row[modelCode],
				'modelName'     => $row[modelName],
				'telecom'       => $telecomName,
				'requestAssort' => $assortName,
				'installment'   => $installmentName,
				'discountType'  => $discountName,
				'writeUrl'      => $row[writeUrl],
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
		'result'             => $result_status,
		'rowTotal'           => $total,
		'pageCount'          => $pageCount,
		'telecomOptions'     => array_all_add($arrTelecomAssort),
		'assortOptions'      => array_all_add($arrRequestAssort),
		'installmentOptions' => array_all_add($arrInstallmentOptions),
		'discountOptions'    => array_all_add($arrSupportAssort2),
		'data'               => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
