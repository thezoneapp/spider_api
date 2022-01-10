<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 회원 목록
	* parameter
		page:           해당페이지
		rows:           페이지당 행의 갯수
		searchKey:      검색값
		searchValue:    검색값
		groupCode:      그룹코드
		memId:          회원아이디
		cmsStatus:      CMS상태
		contractStatus: 계약상태
		joinPayStatus   가입비납부상태
		clearStatus:    구독료납부상태
		memStatus:      회원상태
		minDate:        기간최소일자
		maxDate:        기간최대일자
	*/
	$input_data     = json_decode(file_get_contents('php://input'));
	$page           = $input_data->{'page'};
	$rows           = $input_data->{'rows'};
	$searchKey      = $input_data->{'searchKey'};
	$searchValue    = trim($input_data->{'searchValue'});
	$groupCode      = $input_data->{'groupCode'};
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

	$searchKey      = $searchKey->{'code'};
	$dateAssort     = $dateAssort->{'code'};

//$searchKey = "memId";
//$searchValue = "010-2723-3377";

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	$searchHpNo     = aes128encrypt($searchValue);
	$minDate        = str_replace(".", "-", $minDate);
	$maxDate        = str_replace(".", "-", $maxDate);

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

	if ($groupCode == null || $groupCode == "") $group_sql = "";
	else $group_sql = "and groupCode = '$groupCode' ";

	if ($memStatusValue == null || $memStatusValue == "") $memStatus_sql = "";
	else $memStatus_sql = "and memStatus IN ($memStatusValue) ";

	if ($joinPayValue == null || $joinPayValue == "") $joinPayStatus_sql = "";
	else $joinPayStatus_sql = "and joinPayStatus IN ($joinPayValue) ";

	if ($cmsStatusValue == null || $cmsStatusValue== "") $cmsStatus_sql = "";
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

	if ($searchKey == null || $searchKey == "") {
		if ($searchValue == null || $searchValue == "") $search_sql = "";
		else $search_sql = "and (memName like '%$searchValue%' or memId like '%$searchValue%' or sponsId like '%$searchValue%' or hpNo like '%$searchHpNo%') ";
	} else {
		if ($searchKey == "hpNo") $search_sql = "and hpNo like '%$searchHpNo%' ";
		else $search_sql = "and $searchKey like '%$searchValue%' ";
	}

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM member WHERE idx > 0 $search_sql $group_sql $memId_sql $memAssort_sql $cmsStatus_sql $contractStatus_sql $joinPayStatus_sql $clearStatus_sql $memStatus_sql $date_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, groupCode, sponsId, recommendId, memId, memName, hpNo, memAssort, payType, cmsStatus, contractStatus, joinPayStatus, clearStatus, memStatus, taxAssort, approvalDate, wdate 
	        FROM ( select @a:=@a+1 no, idx, groupCode, sponsId, recommendId, memId, memName, hpNo, memAssort, payType, cmsStatus, contractStatus, joinPayStatus, clearStatus, memStatus, taxAssort, date_format(approvalDate, '%Y/%m/%d') as approvalDate, date_format(wdate, '%Y/%m/%d') as wdate 
		           from member, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $group_sql $memId_sql $memAssort_sql $cmsStatus_sql $contractStatus_sql $joinPayStatus_sql $clearStatus_sql $memStatus_sql $date_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$groupCode      = $row[groupCode];
			$memId          = $row[memId];
			$memAssort      = selected_object($row[memAssort], $arrMemAssort);
			$payType        = selected_object($row[payType], $arrPayType);
			$cmsStatus      = selected_object($row[cmsStatus], $arrCmsStatus);
			$contractStatus = selected_object($row[contractStatus], $arrContractStatus);
			$joinPayStatus  = selected_object($row[joinPayStatus], $arrJoinPayStatus);
			$clearStatus    = selected_object($row[clearStatus], $arrClearStatus);
			$taxAssort      = selected_object($row[taxAssort], $arrBusinessAssort);
			$memStatus      = selected_object($row[memStatus], $arrMemStatus);

			if ($row[hpNo] != "") $row[hpNo] = aes_decode($row[hpNo]);
			if ($row[payType] == "C") $clearStatus = "";
			// 그룹정보
			$sql = "SELECT groupName FROM group_info WHERE groupCode = '$groupCode'";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);
			$groupName = $row2->groupName;

			// 포인트 잔액
			$sql = "SELECT ifnull(SUM(point),0) as point FROM point WHERE memId = '$memId'";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);
			$point = $row2->point;

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'groupName'      => $groupName,
				'sponsId'        => $row[sponsId],
				'recommendId'    => $row[recommendId],
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'hpNo'           => $row[hpNo],
				'memAssort'      => $memAssort,
				'payType'        => $payType,
				'cmsStatus'      => $cmsStatus,
				'contractStatus' => $contractStatus,
				'joinPayStatus'  => $joinPayStatus,
				'clearStatus'    => $clearStatus,
				'taxAssort'      => $taxAssort,
				'memStatus'      => $memStatus,
				'point'          => number_format($point),
				'approvalDate'   => $row[approvalDate],			
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

	// 엑셀다운로드 데이타 검색 
	$excelData = array();
    $sql = "SELECT idx, groupCode, sponsId, memId, memName, memAssort, cmsStatus, contractStatus, joinPayStatus, clearStatus, memStatus, approvalDate, date_format(wdate, '%Y-%m-%d') as wdate 
	        FROM member 
		    WHERE idx > 0 $search_sql $group_sql $memId_sql $memAssort_sql $cmsStatus_sql $contractStatus_sql $joinPayStatus_sql $clearStatus_sql $memStatus_sql $joinDate_sql $appovalDate_sql 
			ORDER BY wdate DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$memId          = $row[memId];
			$memAssort      = selected_object($row[memAssort], $arrMemAssort);
			$cmsStatus      = selected_object($row[cmsStatus], $arrCmsStatus);
			$contractStatus = selected_object($row[contractStatus], $arrContractStatus);
			$joinPayStatus  = selected_object($row[joinPayStatus], $arrJoinPayStatus);
			$clearStatus    = selected_object($row[clearStatus], $arrClearStatus);
			$memStatus      = selected_object($row[memStatus], $arrMemStatus);

			// 그룹정보
			$sql = "SELECT groupName FROM group_info WHERE groupCode = '$groupCode'";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);
			$groupName = $row2->groupName;

			// 포인트 정보
			$totalPoint = 0;       // 포인트합계
			$havePoint = 0;        // 포인트잔액
			$pointOutPause = 0;    // 인출대기포인트
			$pointOutComplete = 0; // 인출완료포인트

			$sql = "SELECT assort, point 
					FROM (
						SELECT 'SP' as assort, ifnull(SUM(price),0) AS point
						FROM commission
						WHERE sponsId = '$memId' AND accurateStatus != '9'
						union
						SELECT 'SA' as assort, ifnull(SUM(point),0) AS point
						FROM point
						WHERE memId = '$memId' AND assort = 'OA' 
						union
						SELECT 'SO' as assort, ifnull(SUM(point),0) AS point
						FROM point
						WHERE memId = '$memId' AND assort IN ('IJ','N1','N2')
						union
						SELECT 'OC' as assort, ifnull(SUM(point),0) AS point
						FROM point
						WHERE memId = '$memId' AND assort = 'OC' 
						union
						SELECT 'OP' as assort, 0 - ifnull(SUM(point),0) AS point
						FROM cash_request
						WHERE memId = '$memId' AND (status != '8' AND status != '9')
						union		
						SELECT 'OB' as assort, ifnull(SUM(point),0) AS point 
						FROM point
						WHERE memId = '$memId'
					 ) t";	
			$result2 = $connect->query($sql);

			while($row2 = mysqli_fetch_array($result2)) {
				if ($row2[assort] == "SP") { // 적립대기
					$totalPoint += $row2[point];
				} else if ($row2[assort] == "SA") { // 적립완료
					$totalPoint += $row2[point];
				} else if ($row2[assort] == "SO") { // 기타적립
					$totalPoint += $row2[point];
				} else if ($row2[assort] == "OC") { // 출금완료
					$outComplete = abs($row2[point]);
				} else if ($row2[assort] == "OP") { // 출금대기
					$outPause = abs($row2[point]);
				} else if ($row2[assort] == "OB") { // 포인트잔액
					$havePoint = $row2[point];
				}
			}

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'groupName'      => $groupName,
				'sponsId'        => $row[sponsId],
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'memAssort'      => $memAssort,
				'cmsStatus'      => $cmsStatus,
				'contractStatus' => $contractStatus,
				'joinPayStatus'  => $joinPayStatus,
				'clearStatus'    => $clearStatus,
				'memStatus'      => $memStatus,
				'point'          => number_format($havePoint),
				'sumPoint'       => number_format($totalPoint),
				'approvalDate'   => $row[approvalDate],			
				'wdate'          => $row[wdate],
			);
			array_push($excelData, $data_info);
		}
	}

	// 회원 상태별 카운트
	$sumCount = 0;
	$arrCount = array();
    $sql = "SELECT memStatus as statusName, count(idx) statusCount FROM member GROUP BY memStatus";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$sumCount += $row[statusCount];
			array_push($arrCount, $row);
		}
	}

	// 검색항목
	$arrSearchOption = array(
		['code' => 'memId',       'name' => '회원ID'],
		['code' => 'memName',     'name' => '회원명'],
		['code' => 'hpNo',        'name' => '휴대폰번호'],
		['code' => 'sponsId',     'name' => '스폰서아이디'],
		['code' => 'recommendId', 'name' => '추천인아이디'],
	);

	// 검색일자 키
	$dateOptions = array(
		['code' => 'wdate',        'name' => '가입일자'],
		['code' => 'approvalDate', 'name' => '승인일자'],
	);

	$response = array(
		'result'          => $result_status,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'assortOptions'   => array_all_add($arrMemAssort),
		'cmsOptions'      => array_all_add($arrCmsStatus),
		'contractOptions' => $arrContractStatus,
		'joinPayOptions'  => array_all_add($arrJoinPayStatus),
		'clearOptions'    => array_all_add($arrClearStatus2),
		'memOptions'      => array_all_add_count($arrMemStatus, $arrCount, $sumCount),
		'dateOptions'     => array_all_add($dateOptions),
		'searchOption'    => array_all_add($arrSearchOption),
		'data'            => $data,
		'excelData'       => $excelData,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
