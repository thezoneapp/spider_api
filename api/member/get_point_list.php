<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 포인트 목록
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> memId:          회원ID
	* parameter ==> assort:         포인트구분
	* parameter ==> minDate:        등록일자-최소일자
	* parameter ==> maxDate:        등록일자-최대일자
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$memId       = $input_data->{'memId'};
	$arrAssort   = $input_data->{'assort'};
	$year        = $input_data->{'year'};

	$year        = $year->{'code'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 포인트구분
	$assortValue = "";

	for ($i = 0; $i < count($arrAssort); $i++) {
		$item    = $arrAssort[$i];
		$code    = $item->code;
		$checked = $item->checked;

		if ($checked == true) {
			if ($assortValue != "") $assortValue .= ",";
			$assortValue .= "'" . $code . "'";
		}
	}

	if ($assortValue == null || $assortValue == "") $assort_sql = "";
	else $assort_sql = "and assort in ($assortValue) ";

	if ($year == null || $year == "") $date_sql = "";
	else $date_sql = "and date_format(wdate, '%Y') = '$year' ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM point WHERE memId = '$memId' $assort_sql $date_sql ";
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
		           where memId = '$memId' $search_sql $assort_sql $date_sql 
		   		   order by wdate desc 
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
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	// 검색조건별 합계
    $sql = "SELECT sum(if(point >= 0, point, 0)) AS inPoint, sum(if(point < 0, point, 0)) AS outPoint 
			FROM point 
			WHERE memId = '$memId' $search_sql $assort_sql $date_sql ";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	
	$inPoint  = $row->inPoint;
	$outPoint = $row->outPoint;
	$sumPoint = $inPoint + $outPoint;

	$response = array(
		'result'          => $result_status,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'yearOptions'     => getYearOptions(),
		'assortOptions'   => array_all_add($arrPointAssort),
		'inPoint'         => number_format($inPoint),
		'outPoint'        => number_format($outPoint),
		'sumPoint'        => number_format($sumPoint),
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
