<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 회원 목록
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> searchValue:    검색값
	* parameter ==> searchValue:    검색값
	* parameter ==> sponsId:        스폰서ID
	* parameter ==> memId:          회원아이디
	* parameter ==> cmsStatus:      CMS상태
	* parameter ==> contractStatus: 계약상태
	* parameter ==> memStatus:      회원상태
	*/
	$input_data     = json_decode(file_get_contents('php://input'));
	$page           = $input_data->{'page'};
	$rows           = $input_data->{'rows'};
	$searchValue    = trim($input_data->{'searchValue'});
	$sponsId        = trim($input_data->{'sponsId'});
	$memId          = trim($input_data->{'memId'});
	$memAssort      = $input_data->{'memAssort'};
	$cmsStatus      = $input_data->{'cmsStatus'};
	$contractStatus = $input_data->{'contractStatus'};
	$joinPayStatus  = $input_data->{'joinPayStatus'};
	$clearStatus    = $input_data->{'clearStatus'};
	$memStatus      = $input_data->{'memStatus'};
	$dateAssort     = $input_data->{'dateAssort'};
	$minDate        = $input_data->{'minDate'};
	$maxDate        = $input_data->{'maxDate'};

	$dateAssort     = $dateAssort->{'code'};

	$searchHpNo     = aes128encrypt($searchValue);
	$minDate        = str_replace(".", "-", $minDate);
	$maxDate        = str_replace(".", "-", $maxDate);

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 회원상태
	$memStatusValue = getCheckedToString($memStatus);
	// 가입비상태
	$joinPayValue = getCheckedToString($joinPayStatus);
	// CMS상태
	$cmsStatusValue = getCheckedToString($cmsStatus);
	// 회원구분
	$memAssortValue = getCheckedToString($memAssort);
	// 구독료상태
	$clearStatusValue = "";

	for ($i = 0; $i < count($clearStatus); $i++) {
		$item    = $clearStatus[$i];
		$code    = $item->code;
		$checked = $item->checked;

		if ($checked == true) {
			if ($clearStatusValue != "") $clearStatusValue .= ",";

			if ($code == "3") $clearStatusValue .= "'3','4','5','6','7','8','9','10','11','12'";
			else $clearStatusValue .= "'" . $code . "'";
		}
	}

	if ($memStatusValue == null || $memStatusValue == "") $memStatus_sql = "";
	else $memStatus_sql = "and memStatus IN ($memStatusValue) ";

	if ($joinPayValue == null || $joinPayValue == "") $joinPayStatus_sql = "";
	else $joinPayStatus_sql = "and joinPayStatus IN ($joinPayValue) ";

	if ($cmsStatusValue == null || $cmsStatusValue == "") $cmsStatus_sql = "";
	else $cmsStatus_sql = "and cmsStatus IN ($cmsStatusValue) ";

	if ($memAssortValue == null || $memAssortValue == "") $memAssort_sql = "";
	else $memAssort_sql = "and memAssort IN ($memAssortValue) ";

	if ($clearStatusValue == null || $clearStatusValue == "") $clearStatus_sql = "";
	else $clearStatus_sql = "and clearStatus IN ($clearStatusValue) ";

	if ($dateAssort == null || $dateAssort == "") {
		if ($maxDate == "") $date_sql = "";
		else $date_sql = "and ((date_format(wdate, '%Y-%m-%d') >= '$minDate' and date_format(wdate, '%Y-%m-%d') <= '$maxDate') or (approvalDate >= '$minDate' and approvalDate <= '$maxDate')) ";
	} else {
		if ($dateAssort == "wdate") $date_sql = "and (date_format(wdate, '%Y-%m-%d') >= '$minDate' and date_format(wdate, '%Y-%m-%d') <= '$maxDate') ";
		else $date_sql = "and (approvalDate >= '$minDate' and approvalDate <= '$maxDate') ";
	}

	if ($searchValue == null || $searchValue == "") $search_sql = "";
	else $search_sql = "and (memName like '%$searchValue%' or memId like '%$searchValue%' or sponsId like '%$searchValue%' or hpNo like '%$searchHpNo%') ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM member WHERE sponsId = '$sponsId' $search_sql $memAssort_sql $cmsStatus_sql $contractStatus_sql $memStatus_sql $clearStatus_sql $joinPayStatus_sql $date_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, sponsId, memId, memName, memAssort, hpNo, cmsStatus, contractStatus, joinPayStatus, clearStatus, memStatus, approvalDate, wdate 
	        FROM ( select @a:=@a+1 no, idx, sponsId, memId, memName, memAssort, hpNo, cmsStatus, contractStatus, joinPayStatus, clearStatus, memStatus, approvalDate, date_format(wdate, '%Y-%m-%d') as wdate 
		           from member, (select @a:= 0) AS a 
		           where sponsId = '$sponsId' $search_sql $memAssort_sql $cmsStatus_sql $contractStatus_sql $memStatus_sql $clearStatus_sql $joinPayStatus_sql $date_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0 && $sponsId != null) {
		while($row = mysqli_fetch_array($result)) {
			$memAssort      = selected_object($row[memAssort], $arrMemAssort);
			$cmsStatus      = selected_object($row[cmsStatus], $arrCmsStatus);
			$contractStatus = selected_object($row[contractStatus], $arrContractStatus);
			$joinPayStatus  = selected_object($row[joinPayStatus], $arrJoinPayStatus);
			$clearStatus    = selected_object($row[clearStatus], $arrClearStatus);
			$memStatus      = selected_object($row[memStatus], $arrMemStatus);

			if ($row[hpNo] != "") $row[hpNo] = aes_decode($row[hpNo]);

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'leg'            => $row[leg],
				'sponsId'        => $row[sponsId],
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'hpNo'           => $row[hpNo],
				'memAssort'      => $memAssort,
				'cmsStatus'      => $cmsStatus,
				'contractStatus' => $contractStatus,
				'joinPayStatus'  => $joinPayStatus,
				'clearStatus'    => $clearStatus,
				'memStatus'      => $memStatus,
				'approvalDate'   => $row[approvalDate],
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

	// 월별 회원합계정보
    $sql = "SELECT ifnull(SUM(mdCount),0) AS mdCount, ifnull(SUM(ssCount),0) AS ssCount 
			FROM (SELECT if(memAssort = 'M', 1, 0) AS mdCount, if(memAssort = 'S', 1, 0) AS ssCount 
				  FROM member 
				  WHERE sponsId = '$sponsId' AND date_format(wdate, '%Y-%m') = date_format(NOW(), '%Y-%m')
				 ) t";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$monthMd  = $row->mdCount;
	$monthSs  = $row->ssCount;
	$monthSum = $monthMd + $monthSs;

	// 전체 회원합계정보
    $sql = "SELECT ifnull(SUM(mdCount),0) AS mdCount, ifnull(SUM(ssCount),0) AS ssCount 
			FROM (SELECT if(memAssort = 'M', 1, 0) AS mdCount, if(memAssort = 'S', 1, 0) AS ssCount 
				  FROM member 
				  WHERE sponsId = '$sponsId' 
				 ) t";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$allMd  = $row->mdCount;
	$allSs  = $row->ssCount;
	$allSum = $allMd + $allSs;

	$countData = array(
		'monthMd'  => number_format($monthMd),
		'monthSs'  => number_format($monthSs),
		'monthSum' => number_format($monthSum),
		'allMd'    => number_format($allMd),
		'allSs'    => number_format($allSs),
		'allSum'   => number_format($allSum)
	);

	// 회원 상태별 카운트
	$sumCount = 0;
	$arrCount = array();
    $sql = "SELECT memStatus as statusName, count(idx) statusCount FROM member WHERE sponsId = '$sponsId' GROUP BY memStatus";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$sumCount += $row[statusCount];
			array_push($arrCount, $row);
		}
	}

	// 검색일자 키
	$dateOptions = array(
		['code' => 'wdate',        'name' => '가입일자'],
		['code' => 'approvalDate', 'name' => '승인일자'],
	);

	$response = array(
		'result'          => $result_status,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'searchOptions'   => $arrSearchOption,
		'assortOptions'   => array_all_add($arrMemAssort),
		'cmsOptions'      => array_all_add($arrCmsStatus),
		'contractOptions' => $arrContractStatus,
		'joinPayOptions'  => array_all_add($arrJoinPayStatus),
		'clearOptions'    => array_all_add($arrClearStatus2),
		'memOptions'      => array_all_add_count($arrMemStatus, $arrCount, $sumCount),
		'dateOptions'     => array_all_add($dateOptions),
		'data'            => $data,
		'countData'       => $countData,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
