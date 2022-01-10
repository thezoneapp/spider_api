<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 레그 목록
	* parameter
		memId:       회원ID
		memStatus:   회원상태
		month:       당월/전월
		searchValue: 검색값
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$sponsId     = $input_data->{'memId'};
	$memStatus   = $input_data->{'memStatus'};
	$month       = $input_data->{'month'};
	$searchValue = trim($input_data->{'searchValue'});
//$sponsId = "a33368055";
//$month = "C";
//$memStatus = "0";

	if ($sponsId == "" || $sponsId == null) $sponsId = "spider";

	if ($memStatus == null || $memStatus == "") $memStatus_sql = "";
	else {
		if ($memStatus == "0") $memStatus_sql = "and memStatus = '9' ";
		else $memStatus_sql = "and memStatus != '9' ";
	}

	if ($month == null || $month == "") $month_sql = "";
	else {
		if ($month == "C") $month_sql = "and date_format(wdate, '%Y-%m') = date_format(NOW(), '%Y-%m') ";
		else $month_sql = "and date_format(wdate, '%Y-%m') = date_format(DATE_SUB(  curdate(),  INTERVAL 1 MONTH  ), '%Y-%m') ";
	}

	if ($searchValue == null || $searchValue == "") $search_sql = "";
	else $search_sql = "and (memName like '%$searchValue%' or memId like '%$searchValue%') ";

	$sql = "SELECT m.idx, m.memId, m.memName, m.memAssort, c.childCnt 
			FROM member m 
				INNER JOIN ( select sponsId, count(idx) as childCnt 
							 from member 
							 group by sponsId 
							) c ON m.memId = c.sponsId 
			WHERE m.memId = '$sponsId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$idx = $row->idx;
	$memId = $row->memId;
	$memName = $row->memName;
	$memAssort = $row->memAssort;
	$childCnt = $row->childCnt;

	$memAssort = selected_object($row->memAssort, $arrMemAssort);

	$personPoint = 0;
	$depthPoint = 0;
	$sumPoint = 0;

	$sql = "SELECT SUM(depthPrice) AS depthPoint, SUM(personPrice) AS personPoint 
			FROM ( SELECT if (assort = 'CS' OR assort = 'MA' OR assort = 'MS' OR assort = 'R2', price, 0) AS depthPrice, 
						  if (assort = 'R1' OR assort = 'P1' OR assort = 'A1', price, 0) AS personPrice 
				   FROM commission
				   WHERE sponsId = '$sponsId' $month_sql $search_sql 
				 ) t";
	$result2 = $connect->query($sql);
	$row2 = mysqli_fetch_object($result2);

	$personPoint = $row2->personPoint;
	$depthPoint = $row2->depthPoint;
	$sumPoint = $personPoint + $depthPoint;

	$root = array(
		'id'          => $idx,
		'memId'       => $memId,
		'memName'     => $memName,
		'memAssort'   => $memAssort,
		'depthCnt'    => $childCnt,
		'personPoint' => number_format($personPoint),
		'depthPoint'  => number_format($depthPoint),
		'sumPoint'    => number_format($sumPoint),
	);

	$root[employees] = make_map($root, $memStatus, $month, $searchValue);

	$data = array();
	array_push($data, $root);

	// 회원상태 구분
	$statusOptions = array(
		['code' => '',  'name' => '전체'],
		['code' => '0', 'name' => '정상'],
		['code' => '1', 'name' => '중지'],
	);

	// 가입월 구분
	$monthOptions = array(
		['code' => '',  'name' => '전체'],
		['code' => 'C', 'name' => '당월'],
		['code' => 'P', 'name' => '전월'],
	);

	$response = array(
		'result'        => "0",
        'statusOptions' => $statusOptions,
        'monthOptions'  => $monthOptions,
        'data'          => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>