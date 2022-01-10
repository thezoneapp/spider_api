<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 회원 목록
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> searchValue:    검색값
	* parameter ==> searchValue:    검색값
	* parameter ==> memId:          회원아이디
	* parameter ==> cmsStatus:      CMS상태
	* parameter ==> contractStatus: 계약상태
	* parameter ==> joinPayStatus   가입비납부상태
	* parameter ==> memStatus:      회원상태
	* parameter ==> minDate:        가입기간최소일자
	* parameter ==> maxDate:        가입기간최대일자
	*/
	$input_data     = json_decode(file_get_contents('php://input'));
	$page           = $input_data->{'page'};
	$rows           = $input_data->{'rows'};
	$searchKey      = $input_data->{'searchKey'};
	$searchValue    = trim($input_data->{'searchValue'});
	$memId          = trim($input_data->{'memId'});
	$memAssort      = $input_data->{'memAssort'};
	$cmsStatus      = $input_data->{'cmsStatus'};
	$contractStatus = $input_data->{'contractStatus'};
	$joinPayStatus  = $input_data->{'joinPayStatus'};
	$memStatus      = $input_data->{'memStatus'};
	$minDate        = $input_data->{'minDate'};
	$maxDate        = $input_data->{'maxDate'};

	$searchKey      = $searchKey->{'code'};
	$memAssort      = $memAssort->{'code'};
	$cmsStatus      = $cmsStatus->{'code'};
	$contractStatus = $contractStatus->{'code'};
	$joinPayStatus  = $joinPayStatus->{'code'};
	$memStatus      = $memStatus->{'code'};

	$minDate        = str_replace(".", "-", $minDate);
	$maxDate        = str_replace(".", "-", $maxDate);

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchKey == null || $searchKey == "") {
		if ($searchValue !== "") $search_sql = "and (memName like '%$searchValue%' or sponsId like '%$searchValue%') ";
		else $search_sql = "";
	} else $search_sql = "and $searchKey like '%$searchValue%' ";

	if ($memId == null || $memId == "") $memId_sql = "";
	else $memId_sql = "and memId = '$memId' ";

	if ($memAssort === null || $memAssort=== "") $memAssort_sql = "";
	else $memAssort_sql = "and memAssort = '$memAssort' ";

	if ($cmsStatus === null || $cmsStatus=== "") $cmsStatus_sql = "";
	else $cmsStatus_sql = "and cmsStatus = '$cmsStatus' ";

	if ($contractStatus === null || $contractStatus=== "") $contractStatus_sql = "";
	else $contractStatus_sql = "and contractStatus = '$contractStatus' ";

	if ($joinPayStatus === null || $joinPayStatus=== "") $joinPayStatus_sql = "";
	else $joinPayStatus_sql = "and joinPayStatus = '$joinPayStatus' ";

	if ($memStatus == null || $memStatus == "") $memStatus_sql = "";
	else $memStatus_sql = "and memStatus = '$memStatus' ";

	if ($maxDate === null || $maxDate=== "") $joinDate_sql = "";
	else $joinDate_sql = "and (wdate >= '$minDate' and wdate <= '$maxDate 23:59:59') ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM member WHERE idx > 0 $search_sql $memId_sql $memAssort_sql $cmsStatus_sql $contractStatus_sql $joinPayStatus_sql $memStatus_sql $joinDate_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT no, idx, leg, sponsId, memId, memName, memAssort, cmsStatus, contractStatus, joinPayStatus, memStatus, wdate 
	        FROM ( select @a:=@a+1 no, idx, leg, sponsId, memId, memName, memAssort, hpNo, cmsStatus, contractStatus, joinPayStatus, memStatus, date_format(wdate, '%Y-%m-%d') as wdate 
		           from member, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $memId_sql $memAssort_sql $cmsStatus_sql $contractStatus_sql $joinPayStatus_sql $memStatus_sql $joinDate_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$memAssort      = selected_object($row[memAssort], $arrMemAssort);
			$cmsStatus      = selected_object($row[cmsStatus], $arrCmsStatus);
			$contractStatus = selected_object($row[contractStatus], $arrContractStatus);
			$joinPayStatus  = selected_object($row[joinPayStatus], $arrJoinPayStatus);
			$memStatus      = selected_object($row[memStatus], $arrMemStatus);

			if ($row[linkTo] == null) $row[linkTo] = "";

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'leg'            => $row[leg],
				'sponsId'        => $row[sponsId],
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'memAssort'      => $memAssort,
				'cmsStatus'      => $cmsStatus,
				'contractStatus' => $contractStatus,
				'joinPayStatus'  => $joinPayStatus,
				'memStatus'      => $memStatus,
				'wdate'          => $row[wdate],
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result = "1";
	}

	// 검색항목
	$arrSearchOption = array(
		['code' => 'memName', 'name' => '회원명'],
		['code' => 'hpNo',    'name' => '휴대폰번호'],
		['code' => 'sponsId', 'name' => '스폰서아이디'],
	);

	$response = array(
		'result'          => $result,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'searchOptions'   => $arrSearchOption,
		'assortOptions'   => $arrMemAssort,
		'cmsOptions'      => $arrCmsStatus,
		'contractOptions' => $arrContractStatus,
		'joinPayOptions'  => $arrJoinPayStatus,
		'memOptions'      => $arrMemStatus,
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
