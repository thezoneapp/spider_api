<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 관리자 목록
	* parameter ==> page:         해당페이지
	* parameter ==> rows:         페이지당 행의 갯수
	* parameter ==> searchKey:    검색항목
	* parameter ==> searchValue:  검색값
	* parameter ==> assort:       수수료 구분
	* parameter ==> status:       정산상태
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchKey   = $input_data->{'searchKey'};
	$searchValue = trim($input_data->{'searchValue'});
	$assort      = $input_data->{'assort'};
	$status      = $input_data->{'status'};

	$searchKey = $searchKey->{'code'};
	$assort    = $assort->{'code'};
	$status    = $status->{'code'};

	if ($page === null) $page = 1;
	if ($rows === null) $rows = 20;

	if ($searchKey === null || $searchKey === "") {
		if ($searchValue !== "") $search_sql = "and (sponsId like '%$searchValue%' or sponsName like '%$searchValue%' or memId like '%$searchValue%' or memName like '%$searchValue%') ";
		else $search_sql = "";
	} else $search_sql = "and $searchKey like '%$searchValue%' ";

	if ($assort === null || $assort === "") $assort_sql = "";
	else $assort_sql = "and assort = '$assort' ";

	if ($status === null || $status === "") $status_sql = "";
	else $status_sql = "and accurateStatus = '$status' ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM commission WHERE idx > 0 $search_sql $assort_sql $status_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT @a:=@a+1 no, idx, sponsId, sponsName, memId, memName, assort, price, accurateStatus, wdate 
	        FROM ( select idx, sponsId, sponsName, memId, memName, assort, price, accurateStatus, date_format(wdate, '%Y-%m-%d') as wdate 
		           from commission 
		           where idx > 0 $search_sql $assort_sql $status_sql 
		   		   order by idx desc 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$assort = selected_object($row[assort], $arrCommiAssort);
			$status = selected_object($row[accurateStatus], $arrAccurateStatus);
			
			$data_info = array(
				'no'        => $row[no],
				'idx'       => $row[idx],
				'sponsId'   => $row[sponsId],
				'sponsName' => $row[sponsName],
			    'memId'     => $row[memId],
				'memName'   => $row[memName],
				'assort'    => $assort,
				'price'     => number_format($row[price]),
				'status'    => $status,
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

	// 검색항목
	$arrSearchOption = array(
		['code' => 'memId',     'name' => '회원ID'],
		['code' => 'memName',   'name' => '회원명'],
		['code' => 'sponsId',   'name' => '스폰서ID'],
		['code' => 'sponsName', 'name' => '스폰서명'],
	);

	$response = array(
		'result'          => $result,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'searchOptions'   => $arrSearchOption,
		'assortOptions'   => $arrCommiAssort,
		'statusOptions'   => $arrAccurateStatus,
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
