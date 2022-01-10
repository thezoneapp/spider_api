<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자페이지 > 세금계산서 목록
	* parameter
		page:           해당페이지
		rows:           페이지당 행의 갯수
		searchKey:      검색항목
		searchValue:    검색값
		memId:          회원ID
		taxAssort:      매입매출구분
		minDate:        등록일자-최소일자
		maxDate:        등록일자-최대일자
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchValue = trim($input_data->{'searchValue'});
	$memId       = $input_data->{'memId'};
	$taxAssort   = $input_data->{'taxAssort'};
	$minDate     = $input_data->{'minDate'};
	$maxDate     = $input_data->{'maxDate'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	$assortValue = getCheckedToString($taxAssort);

	if ($searchValue !== "") $search_sql = "and (memId like '%$searchValue%' or memName like '%$searchValue%' or corpName like '%$searchValue%' or mgtNum like '%$searchValue%') ";
	else $search_sql = "";

	if ($assortValue == null || $assortValue == "") $assort_sql = "";
	else $assort_sql = "and taxAssort IN ($assortValue) ";

	if ($memId == null || $memId == "") $memId_sql = "";
	else $memId_sql = "and memId = '$memId' ";

	if ($maxDate == null || $maxDate == "") $date_sql = "";
	else $date_sql = "and (date_format(wdate, '%Y-%m-%d') >= '$minDate' and date_format(wdate, '%Y-%m-%d') <= '$maxDate') ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM tax_invoice WHERE idx > 0 $search_sql $assort_sql $memId_sql $date_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT @a:=@a+1 no, idx, memId, memName, taxAssort, corpNum, corpName, ceoName, amountTotal, taxTotal, totalAmount, wdate 
	        FROM ( select idx, memId, memName, taxAssort, corpNum, corpName, ceoName, amountTotal, taxTotal, totalAmount, date_format(wdate, '%Y/%m/%d') as wdate 
		           from tax_invoice 
		           where idx > 0 $search_sql $assort_sql $memId_sql $date_sql 
		   		   order by idx desc 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$assortName = selected_object($row[taxAssort], $arrTaxIssueAssort);
			
			$data_info = array(
				'no'          => $row[no],
				'idx'         => $row[idx],
			    'memId'       => $row[memId],
				'memName'     => $row[memName],
				'assortName'  => $assortName,
				'corpNum'     => $row[corpNum],
				'corpName'    => $row[corpName],
				'ceoName'     => $row[ceoName],
				'amountTotal' => number_format($row[amountTotal]),
				'taxTotal'    => number_format($row[taxTotal]),
				'totalAmount' => number_format($row[totalAmount]),
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

	$response = array(
		'result'          => $result_status,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'assortOptions'   => array_all_add($arrTaxIssueAssort),
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
