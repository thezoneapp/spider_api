<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 게시판관리 목록
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> searchValue:    검색값
	* parameter ==> searchValue:    검색값
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchKey   = $input_data->{'searchKey'};
	$searchValue = trim($input_data->{'searchValue'});

	$searchKey   = $searchKey->{'code'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchKey == null || $searchKey == "") {
		if ($searchValue !== "") $search_sql = "and (bbsCode like '%$searchValue%' or title like '%$searchValue%') ";
		else $search_sql = "";
	} else $search_sql = "and $searchKey like '%$searchValue%' ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM bbs_manager WHERE idx > 0 $search_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT no, idx, bbsCode, title, thumbYn, replyYn 
	        FROM ( select @a:=@a+1 no, idx, bbsCode, title, thumbYn, replyYn 
		           from bbs_manager, (select @a:= 0) AS a 
		           where idx > 0 $search_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$thumbYn = selected_object($row[thumbYn], $arrUseAssort);
			$replyYn = selected_object($row[replyYn], $arrUseAssort);

			$data_info = array(
				'no'      => $row[no],
				'idx'     => $row[idx],
				'bbsCode' => $row[bbsCode],
				'title'   => $row[title],
				'thumbYn' => $thumbYn,
				'replyYn' => $replyYn,
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
		['code' => 'title',   'name' => '게시판명'],
		['code' => 'bbsCode', 'name' => '게시판코드'],
	);

	$response = array(
		'result'           => $result,
		'rowTotal'         => $total,
		'pageCount'        => $pageCount,
		'searchOptions'    => $arrSearchOption,
		'data'             => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
