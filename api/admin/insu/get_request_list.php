<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 다이렉트보험 > 신청 목록
	* parameter
		page:           해당페이지
		rows:           페이지당 행의 갯수
		searchValue:    검색값
		memId:          회원아이디
		requestStatus:  신청상태
		assort:         상태별 > 기간 검색
		minDate:        만기일자-최소일자
		maxDate:        만기일자-최대일자
	*/
	$input_data     = json_decode(file_get_contents('php://input'));
	$page           = $input_data->{'page'};
	$rows           = $input_data->{'rows'};
	$searchValue    = trim($input_data->{'searchValue'});
	$memId          = trim($input_data->{'memId'});
	$requestStatus  = $input_data->{'requestStatus'};
	$assort         = $input_data->{'assort'};
	$minDate        = $input_data->{'minDate'};
	$maxDate        = $input_data->{'maxDate'};

	$assortType1   = $assort->{'type1'};
	$assortType2   = $assort->{'type2'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	$searchHpNo = aes128encrypt($searchValue);

	// 상태
	$statusValue = getCheckedToString($requestStatus);

	if ($searchValue == "") $search_sql = "";
	else $search_sql = "and (memName like '%$searchValue%' or custName like '%$searchValue%' or seqNo = '$searchValue' or hpNo like '%$searchHpNo%') ";

	if ($memId == null || $memId == "") $memId_sql = "";
	else $memId_sql = "and memId = '$memId' ";

	if ($statusValue == null || $statusValue == "") $requestStatus_sql = "";
	else $requestStatus_sql = "and requestStatus IN ($statusValue) ";

	if ($assortType1 == "") {
		$assort_sql = "";

	} else {
		if ($assortType1 == "request") {
			if ($assortType2 == "td") $assort_sql = "and date_format(wdate, '%Y-%m-%d') = date_format(curdate(), '%Y-%m-%d') ";
			else if ($assortType2 == "cm") $assort_sql = "and date_format(wdate, '%Y-%m') = date_format(curdate(), '%Y-%m') ";
			else if ($assortType2 == "bm") $assort_sql = "and date_format(wdate, '%Y-%m') = date_format(date_sub(curdate(), INTERVAL 1 month), '%Y-%m') ";
			else $assort_sql = "";

		} else if ($assortType1 == "counsel") {
			if ($assortType2 == "td") $assort_sql = "and counselStatus = 'Y' and counselDate = date_format(curdate(), '%Y-%m-%d') ";
			else if ($assortType2 == "cm") $assort_sql = "and counselStatus = 'Y' and counselDate = date_format(curdate(), '%Y-%m') ";
			else if ($assortType2 == "bm") $assort_sql = "and counselStatus = 'Y' and date_format(counselDate, '%Y-%m') = date_format(date_sub(curdate(), INTERVAL 1 month), '%Y-%m') ";
			else $assort_sql = "and counselStatus = 'Y' ";

		} else if ($assortType1 == "contract") {
			if ($assortType2 == "td") $assort_sql = "and contractStatus = 'Y' and contractDate = date_format(curdate(), '%Y-%m-%d') ";
			else if ($assortType2 == "cm") $assort_sql = "and contractStatus = 'Y' and contractDate = date_format(curdate(), '%Y-%m') ";
			else if ($assortType2 == "bm") $assort_sql = "and contractStatus = 'Y' and date_format(contractDate, '%Y-%m') = date_format(date_sub(curdate(), INTERVAL 1 month), '%Y-%m') ";
			else $assort_sql = "and contractStatus = 'Y' ";

		} else if ($assortType1 == "status") {
			$assort_sql = "and requestStatus = '$assortType2' ";
		}
	}

	// 만기일자
	if ($maxDate == "" || $maxDate == null) $date_sql = "";
	else $date_sql = "and (expiredDate >= '$minDate' and expiredDate <= '$maxDate') ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM insu_request WHERE idx > 0 $search_sql $memId_sql $requestStatus_sql $assort_sql $date_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, memId, memName, seqNo, custName, hpNo, carNoType, carNo, expiredDate, custRegion, marketingFlag, insurFee, commission, counselStatus, contractStatus, requestStatus, wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, seqNo, custName, hpNo, carNoType, carNo, expiredDate, custRegion, marketingFlag, insurFee, commission, counselStatus, contractStatus, requestStatus, date_format(wdate, '%Y-%m-%d') as wdate 
		           from insu_request, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $memId_sql $requestStatus_sql $assort_sql $date_sql 
		         ) t 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$carNoType = selected_object($row[carNoType], $arrCarNoType);
			//$expiredDate = selected_object($row[expiredDate], $arrExpiredDate);
			$custRegion = selected_object($row[custRegion], $arrCustRegion);
			$marketingFlag = selected_object($row[requestStatus], $arrYesNo);
			$counselName = selected_object($row[counselStatus], $arrYesNo);
			$contractName = selected_object($row[contractStatus], $arrYesNo);
			$requestStatus = selected_object($row[requestStatus], $arrInsuStatus);

			if ($row[hpNo] !== "") $row[hpNo] = aes_decode($row[hpNo]);

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'seqNo'          => $row[seqNo],
				'custName'       => $row[custName],
				'hpNo'           => $row[hpNo],
				'carNoType'      => $carNoType,
				'carNo'          => $row[carNo],
				'expiredDate'    => $row[expiredDate],
				'custRegion'     => $custRegion,
				'marketingFlag'  => $marketingFlag,
				'insurFee'       => number_format($row[insurFee]),
				'commission'     => number_format($row[commission]),
				'counselStatus'  => $counselName,
				'contractStatus' => $contractName,
				'requestStatus'  => $requestStatus,
				'wdate'          => $row[wdate],
				'isChecked'      => false,
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}


	// 엑셀 데이타 검색 
	$excelData = array();
    $sql = "SELECT no, idx, memId, memName, seqNo, custName, hpNo, carNoType, carNo, expiredDate, custRegion, marketingFlag, insurFee, commission, counselStatus, contractStatus, requestStatus, wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, seqNo, custName, hpNo, carNoType, carNo, expiredDate, custRegion, marketingFlag, insurFee, commission, counselStatus, contractStatus, requestStatus, date_format(wdate, '%Y-%m-%d') as wdate 
		           from insu_request, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $memId_sql $requestStatus_sql $assort_sql $date_sql 
		         ) t 
			ORDER BY no DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$carNoType = selected_object($row[carNoType], $arrCarNoType);
			//$expiredDate = selected_object($row[expiredDate], $arrExpiredDate);
			$custRegion = selected_object($row[custRegion], $arrCustRegion);
			$counselName = selected_object($row[counselStatus], $arrYesNo);
			$contractName = selected_object($row[contractStatus], $arrYesNo);
			$marketingFlag = selected_object($row[requestStatus], $arrYesNo);
			$requestStatus = selected_object($row[requestStatus], $arrInsuStatus);

			if ($row[hpNo] !== "") $row[hpNo] = aes_decode($row[hpNo]);

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'seqNo'          => $row[seqNo],
				'custName'       => $row[custName],
				'hpNo'           => $row[hpNo],
				'carNoType'      => $carNoType,
				'carNo'          => $row[carNo],
				'expiredDate'    => $row[expiredDate],
				'custRegion'     => $custRegion,
				'marketingFlag'  => $marketingFlag,
				'insurFee'       => number_format($row[insurFee]),
				'commission'     => number_format($row[commission]),
				'counselStatus'  => $counselName,
				'contractStatus' => $contractName,
				'requestStatus'  => $requestStatus,
				'wdate'          => $row[wdate],
				'isChecked'      => false,
			);
			array_push($excelData, $data_info);
		}
	}

	// 신청 합계 정보 
	$tdCount  = 0;
	$cmCount  = 0;
	$bmCount  = 0;
	$sumCount = 0;
    $sql = "SELECT 'TD' as assort, COUNT(idx) AS statusCnt 
			FROM insu_request 
			WHERE date_format(wdate, '%Y-%m-%d') = date_format(curdate(), '%Y-%m-%d') 
			UNION 
			SELECT 'CM' as assort, COUNT(idx) AS statusCnt 
			FROM insu_request 
			WHERE date_format(wdate, '%Y-%m') = date_format(curdate(), '%Y-%m') 
			UNION 
			SELECT 'BM' as assort, COUNT(idx) AS statusCnt 
			FROM insu_request 
			WHERE date_format(wdate, '%Y-%m') = date_format(date_sub(curdate(), INTERVAL 1 month), '%Y-%m') 
			UNION 
			SELECT 'SUM' as assort, COUNT(idx) AS statusCnt 
			FROM insu_request";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[assort] == "TD") $tdCount = $row[statusCnt];
			else if ($row[assort] == "CM") $cmCount = $row[statusCnt];
			else if ($row[assort] == "BM") $bmCount = $row[statusCnt];
			else if ($row[assort] == "SUM") $sumCount = $row[statusCnt];
		}
	}

	$requestSummary = array(
		'tdCount'  => $tdCount,
		'cmCount'  => $cmCount,
		'bmCount'  => $bmCount,
		'sumCount' => $sumCount,
	);

	// 상담완료 합계 정보 
	$tdCount  = 0;
	$cmCount  = 0;
	$bmCount  = 0;
	$sumCount = 0;
    $sql = "SELECT 'TD' as assort, COUNT(idx) AS statusCnt 
			FROM insu_request 
			WHERE counselStatus = 'Y' and counselDate = date_format(curdate(), '%Y-%m-%d') 
			UNION 
			SELECT 'CM' as assort, COUNT(idx) AS statusCnt 
			FROM insu_request 
			WHERE counselStatus = 'Y' and counselDate = date_format(curdate(), '%Y-%m') 
			UNION 
			SELECT 'BM' as assort, COUNT(idx) AS statusCnt 
			FROM insu_request 
			WHERE counselStatus = 'Y' and date_format(counselDate, '%Y-%m') = date_format(date_sub(curdate(), INTERVAL 1 month), '%Y-%m')
			UNION 
			SELECT 'SUM' as assort, COUNT(idx) AS statusCnt 
			FROM insu_request 
			WHERE counselStatus = 'Y'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[assort] == "TD") $tdCount = $row[statusCnt];
			else if ($row[assort] == "CM") $cmCount = $row[statusCnt];
			else if ($row[assort] == "BM") $bmCount = $row[statusCnt];
			else if ($row[assort] == "SUM") $sumCount = $row[statusCnt];
		}
	}

	$counselSummary = array(
		'tdCount'  => $tdCount,
		'cmCount'  => $cmCount,
		'bmCount'  => $bmCount,
		'sumCount' => $sumCount,
	);

	// 계약완료 합계 정보 
	$tdCount  = 0;
	$cmCount  = 0;
	$bmCount  = 0;
	$sumCount = 0;
    $sql = "SELECT 'TD' as assort, COUNT(idx) AS statusCnt 
			FROM insu_request 
			WHERE contractStatus = 'Y' and contractDate = date_format(curdate(), '%Y-%m-%d') 
			UNION 
			SELECT 'CM' as assort, COUNT(idx) AS statusCnt 
			FROM insu_request 
			WHERE contractStatus = 'Y' and contractDate = date_format(curdate(), '%Y-%m') 
			UNION 
			SELECT 'BM' as assort, COUNT(idx) AS statusCnt 
			FROM insu_request 
			WHERE contractStatus = 'Y' and date_format(contractDate, '%Y-%m') = date_format(date_sub(curdate(), INTERVAL 1 month), '%Y-%m') 
			UNION 
			SELECT 'SUM' as assort, COUNT(idx) AS statusCnt 
			FROM insu_request 
			WHERE contractStatus = 'Y'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[assort] == "TD") $tdCount = $row[statusCnt];
			else if ($row[assort] == "CM") $cmCount = $row[statusCnt];
			else if ($row[assort] == "BM") $bmCount = $row[statusCnt];
			else if ($row[assort] == "SUM") $sumCount = $row[statusCnt];
		}
	}

	$contractSummary = array(
		'tdCount'  => $tdCount,
		'cmCount'  => $cmCount,
		'bmCount'  => $bmCount,
		'sumCount' => $sumCount,
	);

	// 상태별 합계 정보 
	$status0 = 0;
	$status5 = 0;
	$status6 = 0;
	$status7 = 0;
	$status8 = 0;
    $sql = "SELECT requestStatus, COUNT(idx) AS statusCnt  
			FROM insu_request 
			GROUP BY requestStatus";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[requestStatus] == "0") $status0 = $row[statusCnt];
			else if ($row[requestStatus] == "5") $status5 = $row[statusCnt];
			else if ($row[requestStatus] == "7") $status7 = $row[statusCnt];
			else if ($row[requestStatus] == "8") $status8 = $row[statusCnt];
			else if ($row[requestStatus] == "9") $status9 = $row[statusCnt];
		}
	}

	$statusSummary = array(
		'status0'  => $status0,
		'status5'  => $status5,
		'status7'  => $status7,
		'status8'  => $status8,
		'status9'  => $status9,
	);

	// 요약정보
	$summary = array(
		'request'  => $requestSummary,
		'counsel'  => $counselSummary,
		'contract' => $contractSummary,
		'status'   => $statusSummary,
	);

	// 검색항목
	$arrSearchOption = array(
		['code' => 'memId',   'name' => '회원ID'],
		['code' => 'memName', 'name' => '회원명'],
		['code' => 'hpNo',    'name' => '휴대폰번호'],
		['code' => 'carNo',   'name' => '차량번호'],
	);

	$response = array(
		'result'           => $result_status,
		'rowTotal'         => $total,
		'pageCount'        => $pageCount,
		'searchOptions'    => $arrSearchOption,
		'statusOptions'    => array_all_add($arrInsuStatus),
		'noTypeOptions'    => $arrCarNoType,
		'expiredOptions'   => $arrExpiredDate,
		'regionOptions'    => $arrCustRegion,
		'marketingOptions' => $arrYesNo,
		'summary'          => $summary,
		'data'             => $data,
		'excelData'        => $excelData,
    );
//print_r($summary);
//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
