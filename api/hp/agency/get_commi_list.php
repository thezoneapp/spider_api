<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 신청 > 수수료율 목록
	* parameter:
		page:           해당페이지
		rows:           페이지당 행의 갯수
		agencyId:       공급업체ID
		telecom:        통신사
		policyDate:     정책일자
		searchValue:    검색어
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$agencyId    = $input_data->{'agencyId'};
	$telecom     = $input_data->{'telecom'};
	$policyDate  = $input_data->{'policyDate'};
	$searchValue = $input_data->{'searchValue'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($telecom == null || $telecom == "") $telecom_sql = "";
	else $telecom_sql = "and hc.telecom = '$telecom' ";

	if ($policyDate == null || $policyDate == "") $policyDate_sql = "";
	else $policyDate_sql = "and hc.policyDate = '$policyDate' ";

	if ($searchValue == null || $searchValue == "") $search_sql = "";
	else $search_sql = "and (hc.modelCode like '%$searchValue%' or hm.modelName like '%$searchValue%') ";

	// 전체 데이타 갯수
    $sql = "SELECT hc.idx 
			FROM hp_agency_commi hc
                 LEFT OUTER JOIN hp_model hm ON hc.modelCode = hm.modelCode 
	        WHERE hc.agencyId = '$agencyId' $telecom_sql $policyDate_sql $search_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, policyDate, telecom, discountType, modelCode, modelName, priceNew, newUseYn, priceMnp, mnpUseYn, priceChange, changeUseYn, wdate 
			FROM ( select @a:=@a+1 no, idx, policyDate, telecom, discountType, modelCode, modelName, priceNew, newUseYn, priceMnp, mnpUseYn, priceChange, changeUseYn, wdate 
				   from ( select hc.idx, hc.policyDate, hc.telecom, hc.discountType, hc.modelCode, hm.modelName, hc.priceNew, hc.newUseYn, hc.priceMnp, hc.mnpUseYn, hc.priceChange, hc.changeUseYn, date_format(wdate, '%Y-%m-%d') as wdate  
                          from hp_agency_commi hc 
                               left outer join hp_model hm on hc.modelCode = hm.modelCode 
						  where hc.agencyId = '$agencyId' $telecom_sql $policyDate_sql $search_sql 
                        ) t1, (select @a:= 0) AS a 
                 ) t2 
            ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecom = selected_object($row[telecom], $arrTelecomAssort);
			$discountName = selected_object($row[discountType], $arrSupportAssort2);
			$newUseName = selected_object($row[newUseYn], $arrUseAssort);
			$mnpUseName = selected_object($row[mnpUseYn], $arrUseAssort);
			$changeUseName = selected_object($row[changeUseYn], $arrUseAssort);

			$data_info = array(
				'no'            => $row[no],
				'policyDate'    => $row[policyDate],
				'idx'           => $row[idx],
				'telecom'       => $telecom,
				'discountType'  => $discountName,
				'modelCode'     => $row[modelCode],
				'modelName'     => $row[modelName],
				'priceNew'      => number_format($row[priceNew]),
				'priceMnp'      => number_format($row[priceMnp]),
				'priceChange'   => number_format($row[priceChange]),
				'newUseYn'      => $row[newUseYn],
				'newUseName'    => $newUseName,
				'mnpUseYn'      => $row[mnpUseYn],
				'mnpUseName'    => $mnpUseName,
				'changeUseYn'   => $row[changeUseYn],
				'changeUseName' => $changeUseName,
				'wdate'         => $row[wdate],
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	// 엑셀다운로드 데이타 검색 
	$excelData = array();
    $sql = "SELECT no, idx, policyDate, telecom, discountType, modelCode, modelName, priceNew, newUseYn, priceMnp, mnpUseYn, priceChange, changeUseYn, wdate 
			FROM ( select @a:=@a+1 no, idx, policyDate, telecom, discountType, modelCode, modelName, priceNew, newUseYn, priceMnp, mnpUseYn, priceChange, changeUseYn, wdate 
				   from ( select hc.idx, hc.policyDate, hc.telecom, hc.discountType, hc.modelCode, hm.modelName, hc.priceNew, hc.newUseYn, hc.priceMnp, hc.mnpUseYn, hc.priceChange, hc.changeUseYn, date_format(wdate, '%Y-%m-%d') as wdate  
                          from hp_agency_commi hc 
                               left outer join hp_model hm on hc.modelCode = hm.modelCode 
						  where hc.agencyId = '$agencyId' $telecom_sql $policyDate_sql $search_sql 
                        ) t1, (select @a:= 0) AS a 
                 ) t2 
            ORDER BY no DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecom = selected_object($row[telecom], $arrTelecomAssort);
			$discountName = selected_object($row[discountType], $arrSupportAssort2);
			$useYn = selected_object($row[useYn], $arrUseAssort);

			$data_info = array(
				'no'           => $row[no],
				'idx'          => $row[idx],
				'modelCode'    => $row[modelCode],
				'modelName'    => $row[modelName],
				'policyDate'   => $row[policyDate],
				'telecom'      => $telecom,
				'discountType' => $discountName,
				'priceNew'     => number_format($row[priceNew]),
				'newUseYn'     => $row[newUseYn],
				'priceMnp'     => number_format($row[priceMnp]),
				'mnpUseYn'     => $row[mnpUseYn],
				'priceChange'  => number_format($row[priceChange]),
				'changeUseYn'  => $row[changeUseYn],
				'wdate'        => $row[wdate],
			);
			array_push($excelData, $data_info);
		}
	}

	// 정책일자 Options
	$policyOptions = array();
    $sql = "SELECT policyDate 
			FROM hp_agency_commi
			WHERE agencyId = '$agencyId'
			GROUP BY policyDate
			ORDER BY policyDate DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'code'  => $row[policyDate],
				'name'  => $row[policyDate],
			);
			array_push($policyOptions, $data_info);
		}
	}

	// 최종결과 반환
	$response = array(
		'result'         => $result_status,
		'rowTotal'       => $total,
		'pageCount'      => $pageCount,
		'policyOptions'  => $policyOptions,
		'telecomOptions' => array_all_add($arrTelecomAssort),
		'useOptions'     => $arrUseAssort,
		'data'           => $data,
		'excelData'      => $excelData,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
