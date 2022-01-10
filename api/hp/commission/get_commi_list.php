<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 신청 > 수수료율 목록
	* parameter 
		page:       해당페이지
		rows:       페이지당 행의 갯수
		policyDate: 정책일자    
		telecom:    통신사
		useYn:      사용여부
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$policyDate  = $input_data->{'policyDate'};
	$telecom     = $input_data->{'telecom'};
	$useYn       = $input_data->{'useYn'};

	$telecom     = $telecom->{'code'};
	$useYn       = $useYn->{'code'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($policyDate == null || $policyDate == "") $policy_sql = "";
	else $policy_sql = "and hc.policyDate = '$policyDate' ";

	if ($telecom == null || $telecom == "") $telecom_sql = "";
	else $telecom_sql = "and hc.telecom = '$telecom' ";

	if ($useYn == null || $useYn == "") $useYn_sql = "";
	else $useYn_sql = "and hc.useYn = '$useYn' ";

	// 전체 데이타 갯수
    $sql = "SELECT hc.idx 
			FROM hp_commi hc
                 LEFT OUTER JOIN hp_model hm ON hc.modelCode = hm.modelCode 
	        WHERE hc.idx > 0 $policy_sql $telecom_sql $useYn_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, policyDate, telecom, assortCode, modelCode, modelName, priceNew, priceMnp, priceChange, useYn, wdate 
			FROM ( select @a:=@a+1 no, idx, policyDate, telecom, assortCode, modelCode, modelName, priceNew, priceMnp, priceChange, useYn, wdate 
				   from ( select hc.idx, hc.policyDate, hc.telecom, hc.assortCode, hc.modelCode, hm.modelName, hc.priceNew, hc.priceMnp, hc.priceChange, hc.useYn, date_format(wdate, '%Y-%m-%d') as wdate  
                          from hp_commi hc 
                               left outer join hp_model hm on hc.modelCode = hm.modelCode 
						  where hc.idx > 0 $policy_sql $telecom_sql $useYn_sql 
						  order by hc.policyDate desc, hc.telecom asc, hc.assortCode asc, hc.modelCode asc 
                        ) t1, (select @a:= 0) AS a 
                 ) t2 
            ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecom = selected_object($row[telecom], $arrTelecomAssort);
			$assortName = selected_object($row[assortCode], $arrSupportAssort2);
			$useYn = selected_object($row[useYn], $arrUseAssort);

			$data_info = array(
				'no'           => $row[no],
				'idx'          => $row[idx],
				'modelCode'    => $row[modelCode],
				'modelName'    => $row[modelName],
				'policyDate'   => $row[policyDate],
				'telecom'      => $telecom,
				'assort'       => $assortName,
				'priceNew'     => number_format($row[priceNew]),
				'priceMnp'     => number_format($row[priceMnp]),
				'priceChange'  => number_format($row[priceChange]),
				'useYn'        => $useYn,
				'wdate'        => $row[wdate],
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
    $sql = "SELECT no, idx, policyDate, telecom, assortCode, modelCode, modelName, priceNew, priceMnp, priceChange, useYn, wdate 
			FROM ( select @a:=@a+1 no, idx, policyDate, telecom, assortCode, modelCode, modelName, priceNew, priceMnp, priceChange, useYn, wdate 
				   from ( select hc.idx, hc.policyDate, hc.telecom, hc.assortCode, hc.modelCode, hm.modelName, hc.priceNew, hc.priceMnp, hc.priceChange, hc.useYn, date_format(wdate, '%Y-%m-%d') as wdate  
                          from hp_commi hc 
                               left outer join hp_model hm on hc.modelCode = hm.modelCode 
						  where hc.idx > 0 $policy_sql $telecom_sql $useYn_sql 
						  order by hc.policyDate desc, hc.telecom asc, hc.discountType asc, hc.modelCode asc 
                        ) t1, (select @a:= 0) AS a 
                 ) t2 
            ORDER BY no DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecom = selected_object($row[telecom], $arrTelecomAssort);
			$assortName = selected_object($row[assortCode], $arrSupportAssort2);
			$useYn = selected_object($row[useYn], $arrUseAssort);

			$data_info = array(
				'no'           => $row[no],
				'idx'          => $row[idx],
				'modelCode'    => $row[modelCode],
				'modelName'    => $row[modelName],
				'policyDate'   => $row[policyDate],
				'telecom'      => $telecom,
				'assort'       => $assortCode,
				'priceNew'     => number_format($row[priceNew]),
				'priceMnp'     => number_format($row[priceMnp]),
				'priceChange'  => number_format($row[priceChange]),
				'useYn'        => $useYn,
				'wdate'        => $row[wdate],
			);
			array_push($excelData, $data_info);
		}
	}

	// 정책일자 옵션
	$policyOptions = array();
	$data_info = array(
		'code' => "",
		'name' => "전체",
	);
	array_push($policyOptions, $data_info);

	$sql = "SELECT policyDate
			FROM hp_commi 
			GROUP BY policyDate
			ORDER BY policyDate DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'code' => $row[policyDate],
				'name' => $row[policyDate],
			);
			array_push($policyOptions, $data_info);
		}
	}

	$response = array(
		'result'          => $result_status,
		'message'         => $imtAssort2,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'policyOptions'   => $policyOptions,
		'assortOptions'   => $arrSupportAssort2,
		'telecomOptions'  => $arrTelecomAssort,
		'useOptions'      => $arrUseAssort,
		'data'            => $data,
		'excelData'       => $excelData,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
