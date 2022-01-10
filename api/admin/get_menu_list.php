<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 관리자 목록
	* parameter ==> page:        해당페이지
	* parameter ==> rows:        페이지당 행의 갯수
	* parameter ==> searchKey:   검색항목
	* parameter ==> searchValue: 검색값
	* parameter ==> depthNo:     Depth No => 1:1단계, 2:2단계
	* parameter ==> authAssort:  메뉴구분 => C:클라이언트, A:관리자, M:MD플랫폼, S:구독플랫폼
	* parameter ==> menuStatus:  현상태 => Y:사용함, N:사용안함
	*/

	$input_data   = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchKey   = $input_data->{'searchKey'};
	$searchValue = trim($input_data->{'searchValue'});
	$depthNo     = $input_data->{'depthNo'};
	$authAssort  = $input_data->{'authAssort'};
	$menuStatus  = $input_data->{'menuStatus'};

	$searchKey   = $searchKey->{'code'};
	$authAssort  = $authAssort->{'code'};
	$menuStatus  = $menuStatus->{'code'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchKey == null || $searchKey == "") {
		if ($searchValue !== "") $search_sql = "and (menuName like '%$searchValue%' or linkTo like '%$searchValue%') ";
		else $search_sql = "";
	} else $search_sql = "and $searchKey like '%$searchValue%' ";

	if ($depthNo == null || $depthNo == "") $depthNo_sql = "";
	else $depthNo_sql = "and depthNo = '$depthNo' ";

	if ($authAssort === null || $authAssort=== "") $authAssort_sql = "";
	else $authAssort_sql = "and authAssort = '$authAssort' ";

	if ($menuStatus == null || $menuStatus == "") $menuStatus_sql = "";
	else $menuStatus_sql = "and menuStatus = '$menuStatus' ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM menu WHERE idx > 0 $search_sql $depthNo_sql $authAssort_sql $menuStatus_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT @a:=@a+1 no, idx, depthNo, parentIdx, menuName, iconName, linkTo, authAssort, sortNo, menuStatus 
	        FROM ( select idx, depthNo, parentIdx, menuName, iconName, linkTo, authAssort, sortNo, menuStatus 
		           from menu 
		           where idx > 0 $search_sql $depthNo_sql $authAssort_sql $menuStatus_sql 
				   order by authAssort asc, parentIdx asc 
		         ) m, (select @a:= 0) AS a 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$authAssort = selected_object($row[authAssort], $arrAuthAssort);
			$menuStatus = selected_object($row[menuStatus], $arrMenuStatus);

			if ($row[linkTo] == null) $row[linkTo] = "";

			$data_info = array(
				'no'         => $row[no],
				'idx'        => $row[idx],
				'depthNo'    => $row[depthNo],
				'parentIdx'  => $row[parentIdx],
				'menuName'   => $row[menuName],
				'iconName'   => $row[iconName],
				'linkTo'     => $row[linkTo],
				'sortNo'     => $row[sortNo],
				'authAssort' => $authAssort,
				'menuStatus' => $menuStatus
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
		['code' => 'menuName', 'name' => '메뉴명'],
		['code' => 'linkTo',   'name' => 'Link To']
	);

	$response = array(
		'result'        => $result,
		'rowTotal'      => $total,
		'pageCount'     => $pageCount,
		'searchOptions' => $arrSearchOption,
		'authOptions'   => $arrAuthAssort,
		'statusOptions' => $arrMenuStatus,
		'data'          => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>