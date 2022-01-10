<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 관리자 목록
	* parameter ==> page:         해당페이지
	* parameter ==> rows:         페이지당 행의 갯수
	* parameter ==> sponsId:      스폰서ID
	* parameter ==> searchKey:    검색항목
	* parameter ==> searchValue:  검색값
	* parameter ==> assort:       수수료 구분
	* parameter ==> status:       정산상태
	* parameter ==> minDate:      등록일자-최소일자
	* parameter ==> maxDate:      등록일자-최대일자
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchKey   = $input_data->{'searchKey'};
	$searchValue = trim($input_data->{'searchValue'});
	$sponsId     = $input_data->{'sponsId'};
	$arrAssort   = $input_data->{'assort'};
	$arrStatus   = $input_data->{'status'};
	$minDate     = $input_data->{'minDate'};
	$maxDate     = $input_data->{'maxDate'};

	$searchKey = $searchKey->{'code'};

	// 포인트구분
	$assortValue = "";

	for ($i = 0; $i < count($arrAssort); $i++) {
		$item    = $arrAssort[$i];
		$code    = $item->code;
		$checked = $item->checked;

		if ($checked == true) {
			if ($assortValue != "") $assortValue .= ",";

			if ($code == "R0") $assortValue .= "'R1','R2'";
			else if ($code == "M0") $assortValue .= "'MA','MS'";
			else $assortValue .= "'" . $code . "'";
		}
	}

	// 정산상태
	$statusValue = "";

	for ($i = 0; $i < count($arrStatus); $i++) {
		$item    = $arrStatus[$i];
		$code    = $item->code;
		$checked = $item->checked;

		if ($checked == true) {
			if ($statusValue != "") $statusValue .= ",";
			$statusValue .= "'" . $code . "'";
		}
	}

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchKey == null || $searchKey == "") {
		if ($searchValue !== "") $search_sql = "and (sponsId = '$searchValue' or sponsName = '$searchValue' or memId = '$searchValue' or memName = '$searchValue') ";
		else $search_sql = "";
	} else $search_sql = "and $searchKey = '$searchValue' ";

	if ($assortValue == null || $assortValue == "") $assort_sql = "";
	else $assort_sql = "and assort in ($assortValue) ";

	if ($statusValue == null || $statusValue == "") $status_sql = "";
	else $status_sql = "and accurateStatus in ($statusValue) ";

	if ($maxDate == null || $maxDate == "") $date_sql = "";
	else $date_sql = "and (date_format(wdate, '%Y-%m-%d') >= '$minDate' and date_format(wdate, '%Y-%m-%d') <= '$maxDate') ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM commission WHERE sponsId = '$sponsId' $search_sql $assort_sql $status_sql $date_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT @a:=@a+1 no, idx, sponsId, sponsName, memId, memName, assort, custName, price, accurateStatus, wdate 
	        FROM ( select idx, sponsId, sponsName, memId, memName, assort, custName, price, accurateStatus, date_format(wdate, '%Y-%m-%d') as wdate 
		           from commission 
		           where sponsId = '$sponsId' $search_sql $assort_sql $status_sql $date_sql 
		   		   order by idx desc 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0 && $sponsId != null) {
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
				'custName'   => $row[custName],
			    'price'     => number_format($row[price]),
				'status'    => $status,
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

	// 검색조건별 합계
    $sql = "SELECT ifnull(sum(price),0) AS sumCommission FROM commission WHERE sponsId = '$sponsId' $search_sql $assort_sql $status_sql $date_sql";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	
	$sumCommission = $row->sumCommission;

	// 검색항목
	$arrSearchOption = array(
		['code' => 'memId',     'name' => '회원ID'],
		['code' => 'memName',   'name' => '회원명'],
		['code' => 'sponsId',   'name' => '스폰서ID'],
		['code' => 'sponsName', 'name' => '스폰서명'],
	);

	$response = array(
		'result'          => $result_status,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'sumCommission'   => number_format($sumCommission),
		'searchOptions'   => $arrSearchOption,
		'assortOptions'   => array_all_add($arrCommiAssort),
		'statusOptions'   => array_all_add($arrAccurateStatus),
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
