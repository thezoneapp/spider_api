<?
	include "../../../inc/common.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 목록
	* parameter ==> page:         해당페이지
	* parameter ==> rows:         페이지당 행의 갯수
	* parameter ==> searchKey:    검색항목
	* parameter ==> searchName:   검색값
	* parameter ==> cmsStatus:    CMS상태
	* parameter ==> memStatus:    회원상태
	*/
	$data_back  = json_decode(file_get_contents('php://input'));
	$page         = $data_back->{'page'};
	$rows         = $data_back->{'rows'};
	$searchKey    = $data_back->{'searchKey'};
	$searchName   = trim($data_back->{'searchName'});
	$cmsStatus    = $data_back->{'cmsStatus'};
	$memStatus     = $data_back->{'memStatus'};

	if ($searchKey === null || $searchKey === "") {
		if ($searchName !== "") $search_sql = "and (memId like '%$searchName%' or memName like '%$searchName%') ";
		else $search_sql = "";
	} else $search_sql = "and $searchKey like '%$searchName%' ";

	if ($cmsStatus === null || $cmsStatus === "") $cmsStatus_sql = "";
	else $cmsStatus_sql = "and cmsStatus = '$cmsStatus' ";

	if ($memStatus === null || $memStatus === "") $memStatus_sql = "";
	else $memStatus_sql = "and memStatus = '$memStatus' ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM member WHERE idx > 0 $search_sql $cmsStatus_sql $memStatus_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$page_count = ceil($total / $rows);
	if ($page < 1 || $page > $page_count) $page = 1;
	$start = ($page - 1) * $rows;

	$data = array();

	// 조건에 맞는 데이타 검색 
    $sql = "SELECT memId, memName, hpNo 
	        FROM member 
			WHERE idx > 0 $search_sql $cmsStatus_sql $memStatus_sql 
			ORDER BY idx DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[hpNo] !== "") $row[hpNo] = aes_decode($row[hpNo]);

			$data_info = array(
				'memId'   => $row[memId],
				'memName' => $row[memName] . " / " . $row[hpNo]
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result = "1";
	}

	//$response = array(
	//	'result'    => $result,
	//	'pageCount' => $page_count,
    //    'data'      => $data
    //);

    echo json_encode( $data );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>