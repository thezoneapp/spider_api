<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 관리자 목록
	* parameter ==> page:       해당페이지
	* parameter ==> rows:       페이지당 행의 갯수
	* parameter ==> auth:       관리자권한
	* parameter ==> useYn:      사용여부
	* parameter ==> searchKey:  검색항목
	* parameter ==> searchName: 검색값
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchKey   = $input_data->{'searchKey'};
	$searchValue = trim($input_data->{'searchValue'});
	$auth        = $input_data->{'auth'};
	$useYn       = $input_data->{'useYn'};

	$auth        = $auth->{'code'};
	$useYn       = $useYn->{'code'};

	if ($page === null) $page = 1;
	if ($rows === null) $rows = 20;

	if ($searchKey === null || $searchKey === "") {
		if ($searchName !== "") $search_sql = "and (id like '%$searchValue%' or name like '%$searchValue%') ";
		else $search_sql = "";
	} else $search_sql = "and $searchKey like '%$searchValue%' ";

	if ($auth === null || $auth === "") $auth_sql = "";
	else $auth_sql = "and auth = '$auth' ";

	if ($useYn === null || $useYn === "") $useYn_sql = "";
	else $useYn_sql = "and use_yn = '$useYn' ";

	// 전체 데이타 갯수
    $sql = "SELECT id FROM admin WHERE idx > 0 $search_sql $auth_sql $useYn_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	$data = array();

	// 조건에 맞는 데이타 검색 
    $sql = "SELECT idx, id, passwd, name, phone, email, auth, use_yn, wdate 
	        FROM admin 
			WHERE idx > 0 $search_sql $auth_sql $useYn_sql 
			ORDER BY idx DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$auth = selected_object($row[auth], $arrAdminAuth);
			$useYn = selected_object($row[use_yn], $arrUseYn);

			if ($row[passwd] !== "") $row[passwd] = aes_decode($row[passwd]);

			$data_info = array(
				'no'        => $no,
				'idx'       => $row[idx],
				'adminId'   => $row[id],
				'adminPw'   => $row[passwd],
				'adminName' => $row[name],
				'phone'     => $row[phone],
				'email'     => $row[email],
				'auth'      => $auth,
				'useYn'     => $useYn,
				'wdate'     => $row[wdate]
			);
			array_push($data, $data_info);
			$no--;
		}

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result = "1";
	}

	$response = array(
		'result'      => $result,
		'rowTotal'    => $total,
		'pageCount'   => $pageCount,
		'authOptions' => $arrAdminAuth,
		'useOptions'  => $arrUseYn,
        'data'        => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>