<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 목록
	* parameter ==> page:       해당페이지
	* parameter ==> rows:       페이지당 행의 갯수
	* parameter ==> groupCode:  그룹코드
	* parameter ==> useYn:      사용여부
	* parameter ==> searchKey:  검색항목
	* parameter ==> searchName: 검색값
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchKey   = $input_data->{'searchKey'};
	$searchValue = trim($input_data->{'searchValue'});
	$groupCode   = $input_data->{'groupCode'};
	$useYn       = $input_data->{'useYn'};

	//$auth        = $auth->{'code'};
	//$useYn       = $useYn->{'code'};

	if ($page === null) $page = 1;
	if ($rows === null) $rows = 20;

	// 그룹코드
	$groupValue = getCheckedToString($groupCode);
	// 사용여부
	$useValue = getCheckedToString($useYn);

	if ($searchValue != "") $search_sql = "and (id like '%$searchValue%' or name like '%$searchValue%') ";
	else $search_sql = "";

	if ($groupValue == null || $groupValue == "") $group_sql = "";
	else $group_sql = "and a.groupCode IN ($groupValue) ";

	if ($useValue == null || $useValue == "") $useYn_sql = "";
	else $useYn_sql = "and a.use_yn IN ($useValue) ";

	// 전체 데이타 갯수
    $sql = "SELECT id 
			FROM admin a 
				 INNER JOIN group_info gi ON a.groupCode = gi.groupCode 
			WHERE a.idx > 0 $search_sql $group_sql $useYn_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	$data = array();

	// 조건에 맞는 데이타 검색 
    $sql = "SELECT a.idx, gi.groupName, a.id, a.name, a.passwd, a.NAME, a.phone, a.email, a.use_yn, a.wdate 
			FROM admin a
				 INNER JOIN group_info gi ON a.groupCode = gi.groupCode 
			WHERE a.idx > 0 $search_sql $group_sql $useYn_sql 
			ORDER BY a.idx DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$useYn = selected_object($row[use_yn], $arrUseYn);

			if ($row[passwd] !== "") $row[passwd] = aes_decode($row[passwd]);

			$data_info = array(
				'no'        => $no,
				'idx'       => $row[idx],
				'groupName' => $row[groupName],
				'adminId'   => $row[id],
				'adminPw'   => $row[passwd],
				'adminName' => $row[name],
				'phone'     => $row[phone],
				'email'     => $row[email],
				'useYn'     => $useYn,
				'wdate'     => $row[wdate]
			);
			array_push($data, $data_info);
			$no--;
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	// 그룹 선택 정보
	$groupOptions = array();
	$sql = "SELECT groupCode, groupName FROM group_info WHERE useYn = 'Y'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'code' => $row[groupCode],
				'name' => $row[groupName],
			);
			array_push($groupOptions, $data_info);
		}
	}

	$response = array(
		'result'       => $result_status,
		'rowTotal'     => $total,
		'pageCount'    => $pageCount,
		'groupOptions' => array_all_add($groupOptions),
		'useOptions'   => array_all_add($arrUseYn),
        'data'         => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>