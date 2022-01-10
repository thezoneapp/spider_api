<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 목록
	* parameter
		page:         해당페이지
		rows:         페이지당 행의 갯수
		searchKey:    검색항목
		searchValue:  검색값
		memId:        회원ID
		memAssort:    회원구분
		cmsStatus:    CMS상태
		agreeStatus:  동의상태
		memStatus:    회원상태
		paymentKind:  납부수단
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$memId       = trim($input_data->{'memId'});
	$searchKey   = $input_data->{'searchKey'};
	$searchValue = trim($input_data->{'searchValue'});
	$memAssort   = $input_data->{'memAssort'};
	$cmsStatus   = $input_data->{'cmsStatus'};
	$agreeStatus = $input_data->{'agreeStatus'};
	$paymentKind = $input_data->{'paymentKind'};
	$memStatus   = $input_data->{'memStatus'};
	$minDate     = $input_data->{'minDate'};
	$maxDate     = $input_data->{'maxDate'};

	$searchKey    = $searchKey->{'code'};
	$assortValue  = getCheckedToString($memAssort);
	$cmsValue     = getCheckedToString($cmsStatus);
	$agreeValue   = getCheckedToString($agreeStatus);
	$paymentValue = getCheckedToString($paymentKind);
	$statusValue  = getCheckedToString($memStatus);

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchKey == null || $searchKey == "") {
		if ($searchValue != "") $search_sql = "and (m.memId like '%$searchValue%' or m.memName like '%$searchValue%') ";
		else $search_sql = "";
	} else $search_sql = "and m.$searchKey like '%$searchValue%' ";

	if ($memId == null || $memId == "") $memId_sql = "";
	else $memId_sql = "and m.memId = '$memId' ";

	if ($assortValue == null || $assortValue == "") $memAssort_sql = "";
	else $memAssort_sql = "and memAssort IN ($assortValue) ";

	if ($cmsValue == null || $cmsValue == "") $cmsStatus_sql = "";
	else $cmsStatus_sql = "and m.cmsStatus IN ($cmsValue) ";

	if ($agreeValue == null || $agreeValue == "") $agreeStatus_sql = "";
	else $agreeStatus_sql = "and m.agreeStatus IN ($agreeValue) ";

	if ($paymentValue == null || $paymentValue == "") $paymentKind_sql = "";
	else $paymentKind_sql = "and c.paymentKind IN ($paymentValue) ";

	if ($statusValue == null || $statusValue == "") $memStatus_sql = "";
	else $memStatus_sql = "and m.memStatus IN ($statusValue) ";

	if ($maxDate === null || $maxDate === "") $date_sql = "";
	else $date_sql = "and (date_format(c.wdate, '%Y-%m-%d') >= '$minDate' and date_format(c.wdate, '%Y-%m-%d') <= '$maxDate') ";

	// 전체 데이타 갯수
    $sql = "SELECT c.idx 
	        FROM cms c 
			     inner join member m on c.memId = m.memId 
			WHERE c.idx > 0 $search_sql $memId_sql $memAssort_sql $cmsStatus_sql $agreeStatus_sql $paymentKind_sql $memStatus_sql $date_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, sponsId, recommendId, memId, memName, memAssort, hpNo, cmsStatus, agreeStatus, paymentKind, memStatus, wdate 
	        FROM ( select @a:=@a+1 no, idx, sponsId, recommendId, memId, memName, memAssort, hpNo, cmsStatus, agreeStatus, paymentKind, memStatus, wdate 
			       from ( select c.idx, m.sponsId, m.recommendId, m.memId, m.memName, m.memAssort, m.hpNo, m.cmsStatus, m.agreeStatus, c.paymentKind, m.memStatus, date_format(c.wdate, '%Y-%m-%d') as wdate 
		                  from cms c 
					           inner join member m on c.memId = m.memId 
		                  where c.idx > 0 $search_sql $memId_sql $memAssort_sql $cmsStatus_sql $agreeStatus_sql $paymentKind_sql $memStatus_sql $date_sql 
						 ) t1, (select @a:= 0) AS a 
		         ) t2 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$cmsStatus = selected_object($row[cmsStatus], $arrCmsStatus);
			$agreeStatus = selected_object($row[agreeStatus], $arrAgreeStatus);
			$memAssort = selected_object($row[memAssort], $arrMemAssort);
			$memStatus = selected_object($row[memStatus], $arrMemStatus);
			$paymentKind = selected_object($row[paymentKind], $arrPaymentKind);

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'sponsId'        => $row[sponsId],
				'recommandId'    => $row[recommendId],
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'memAssort'      => $memAssort,
				'cmsStatus'      => $cmsStatus,
				'agreeStatus'    => $agreeStatus,
				'paymentKind'    => $paymentKind,
				'memStatus'      => $memStatus,
				'wdate'          => $row[wdate]
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	// 검색항목
	$arrSearchOption = array(
		['code' => 'memId',   'name' => '회원ID'],
		['code' => 'memName', 'name' => '회원명'],
		['code' => 'hpNo',    'name' => '휴대폰번호'],
		['code' => 'sponsId', 'name' => '스폰서아이디'],
	);

	$response = array(
		'result'          => $result_status,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'searchOptions'   => $arrSearchOption,
		'assortOptions'   => array_all_add($arrMemAssort),
		'cmsOptions'      => array_all_add($arrCmsStatus),
		'agreeOptions'    => array_all_add($arrAgreeStatus),
		'kindOptions'     => array_all_add($arrPaymentKind),
		'memOptions'      => array_all_add($arrMemStatus),
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>