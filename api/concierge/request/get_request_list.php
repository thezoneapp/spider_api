<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 컨시어지 > ID발급신청 > 목록
	* parameter
		page:           해당페이지
		rows:           페이지당 행의 갯수
		searchValue:    검색값
		minDate:        기간최소일자
		maxDate:        기간최대일자
		requestStatus:  발급상태
	*/
	$input_data    = json_decode(file_get_contents('php://input'));
	$page          = $input_data->{'page'};
	$rows          = $input_data->{'rows'};
	$searchValue   = trim($input_data->{'searchValue'});
	$minDate       = $input_data->{'minDate'};
	$maxDate       = $input_data->{'maxDate'};
	$requestStatus = $input_data->{'requestStatus'};

	//$memId = "a27233377";

	$searchHpNo = aes128encrypt($searchValue);

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 신청상태
	$statusValue = getCheckedToString($requestStatus);

	if ($searchValue == null || $searchValue == "") $search_sql = "";
	else $search_sql = "and (memId like '%$searchValue%' or memName like '%$searchValue%' or hpNo like '%$searchHpNo%') ";

	if ($statusValue == null || $statusValue == "") $status_sql = "";
	else $status_sql = "and requestStatus IN ($statusValue) ";

	if ($maxDate == "") $date_sql = "";
	else $date_sql = "and (date_format(wdate, '%Y-%m-%d') >= '$minDate' and date_format(wdate, '%Y-%m-%d') <= '$maxDate') ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM concierge_request WHERE idx > 0 $search_sql $status_sql $date_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, memId, memName, conciergeId, registNo, hpNo, postNum, addr1, addr2, email, requestStatus, wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, conciergeId, registNo, hpNo, postNum, addr1, addr2, email, requestStatus, date_format(wdate, '%Y-%m-%d') as wdate 
		           from concierge_request, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $status_sql $date_sql 
		         ) t 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$statusName = selected_object($row[requestStatus], $arrIdRequestStatus);

			if ($row[registNo] != "") $row[registNo] = aes_decode($row[registNo]);
			if ($row[hpNo] != "") $row[hpNo] = aes_decode($row[hpNo]);
			if ($row[email] != "") $row[email] = aes_decode($row[email]);

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'conciergeId'    => $row[conciergeId],
				'hpNo'           => $row[hpNo],
				'addr1'          => $row[addr1],
				'addr2'          => $row[addr2],
				'email'          => $row[email],
				'requestStatus'  => $statusName,
				'wdate'          => $row[wdate],
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
    $sql = "SELECT no, idx, memId, memName, conciergeId, registNo, hpNo, postNum, addr1, addr2, email, requestStatus, wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, conciergeId, registNo, hpNo, postNum, addr1, addr2, email, requestStatus, date_format(wdate, '%Y-%m-%d') as wdate 
		           from concierge_request, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $status_sql $date_sql 
		         ) t 
			ORDER BY no DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$statusName = selected_object($row[requestStatus], $arrIdRequestStatus);

			if ($row[registNo] != "") $row[registNo] = aes_decode($row[registNo]);
			if ($row[hpNo] != "") $row[hpNo] = aes_decode($row[hpNo]);
			if ($row[email] != "") $row[email] = aes_decode($row[email]);

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'conciergeId'    => $row[conciergeId],
				'registNo'       => $row[registNo],
				'hpNo'           => $row[hpNo],
				'address'        => $row[addr1] . " " . $row[addr2],
				'email'          => $row[email],
				'requestStatus'  => $statusName,
				'wdate'          => $row[wdate],
			);
			array_push($excelData, $data_info);
		}
	}

	$response = array(
		'result'          => $result_status,
		'message'         => "정상",
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'statusOptions'   => array_all_add($arrIdRequestStatus),
		'data'            => $data,
		'excelData'       => $excelData,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
