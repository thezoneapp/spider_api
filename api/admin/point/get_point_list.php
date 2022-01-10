<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 포인트 목록
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> searchKey:      검색항목
	* parameter ==> searchValue:    검색값
	* parameter ==> memId:          회원ID
	* parameter ==> assort:         포인트구분
	* parameter ==> minDate:        등록일자-최소일자
	* parameter ==> maxDate:        등록일자-최대일자
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchValue = trim($input_data->{'searchValue'});
	$memId       = $input_data->{'memId'};
	$assort      = $input_data->{'assort'};
	$minDate     = $input_data->{'minDate'};
	$maxDate     = $input_data->{'maxDate'};

	$assort = $assort->{'code'};

	if ($page === null) $page = 1;
	if ($rows === null) $rows = 20;

	if ($searchValue !== "") $search_sql = "and (memId like '%$searchValue%' or memName like '%$searchValue%') ";
	else $search_sql = "";

	if ($memId === null || $memId === "") $memId_sql = "";
	else $memId_sql = "and memId = '$memId' ";

	if ($assort === null || $assort === "") $assort_sql = "";
	else $assort_sql = "and assort = '$assort' ";

	if ($maxDate === null || $maxDate === "") $date_sql = "";
	else $date_sql = "and (date_format(wdate, '%Y-%m-%d') >= '$minDate' and date_format(wdate, '%Y-%m-%d') <= '$maxDate') ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM point WHERE idx > 0 $search_sql $assort_sql $memId_sql $date_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT @a:=@a+1 no, idx, memId, memName, assort, descript, point, accurateIdx, wdate 
	        FROM ( select idx, memId, memName, assort, descript, point, accurateIdx, date_format(wdate, '%Y/%m/%d') as wdate 
		           from point 
		           where idx > 0 $search_sql $search_sql $assort_sql $memId_sql $date_sql 
		   		   order by idx desc 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$assortName = selected_object($row[assort], $arrPointAssort);
			
			$data_info = array(
				'no'          => $row[no],
				'idx'         => $row[idx],
			    'memId'       => $row[memId],
				'memName'     => $row[memName],
				'assortName'  => $assortName,
				'descript'    => $row[descript],
				'point'       => number_format($row[point]),
				'accurateIdx' => $row[accurateIdx],
				'wdate'       => $row[wdate],
				'isChecked'   => false,
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
		'result'          => $result,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'assortOptions'   => $arrPointAssort,
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
