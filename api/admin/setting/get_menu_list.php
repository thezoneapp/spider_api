<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 환경설정 > 메뉴관리 > 메뉴 목록
	* parameter ==> page:        해당페이지
	* parameter ==> rows:        페이지당 행의 갯수
	* parameter ==> authAssort:  메뉴구분 => A:관리자, M:MD플랫폼, S:구독플랫폼
	* parameter ==> menuStatus:  현상태 => Y:사용함, N:사용안함
	*/
	$input_data   = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchValue = trim($input_data->{'searchValue'});
	$authAssort  = $input_data->{'authAssort'};
	$menuStatus  = $input_data->{'menuStatus'};
	
	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 메뉴구분
	$authAssort = $authAssort->{'code'};
	// 사용여부
	$statusValue = getCheckedToString($menuStatus);

	if ($searchValue != "") $search_sql = "and (menuName like '%$searchValue%') ";
	else $search_sql = "";

	if ($authAssort == null || $authAssort == "") $authAssort_sql = "";
	else $authAssort_sql = "and authAssort = '$authAssort' ";

	if ($statusValue == null || $statusValue == "") $menuStatus_sql = "";
	else $menuStatus_sql = "and menuStatus IN ($statusValue) ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM menu WHERE depthNo = 1 $search_sql $authAssort_sql $menuStatus_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 메뉴 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, depthNo, parentIdx, authAssort, menuName, iconName, sortNo, menuStatus 
	        FROM ( select @a:=@a+1 no, idx, depthNo, parentIdx, authAssort, menuName, iconName, sortNo, menuStatus 
		           from menu, (select @a:= 0) AS a 
		           where depthNo = 1 $search_sql $authAssort_sql $menuStatus_sql 
				   order by sortNo asc 
		         ) m 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$assortName = selected_object($row[authAssort], $arrAuthAssort);
			$menuStatus = selected_object($row[menuStatus], $arrUseAssort);

			$data_info = array(
				'no'         => $row[no],
				'idx'        => $row[idx],
				'depthNo'    => $row[depthNo],
				'parentIdx'  => $row[parentIdx],
				'assortName' => $assortName,
				'menuName'   => $row[menuName],
				'iconName'   => $row[iconName],
				'sortNo'     => $row[sortNo],
				'menuStatus' => $menuStatus,
				'isChecked'  => false,
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
		'result'        => $result_status,
		'rowTotal'      => $total,
		'pageCount'     => $pageCount,
		'authOptions'   => array_all_add($arrAuthAssort),
		'statusOptions' => array_all_add($arrUseAssort),
		'data'          => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>