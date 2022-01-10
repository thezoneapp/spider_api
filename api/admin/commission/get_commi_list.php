<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 포인트관리 > 적립 목록
	* parameter ==> page:         해당페이지
	* parameter ==> rows:         페이지당 행의 갯수
	* parameter ==> searchKey:    검색항목
	* parameter ==> searchValue:  검색값
	* parameter ==> memId:        회원ID
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
	$memId       = $input_data->{'memId'};
	$assort      = $input_data->{'assort'};
	$status      = $input_data->{'status'};
	$minDate     = $input_data->{'minDate'};
	$maxDate     = $input_data->{'maxDate'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 포인트구분
	$assortValue = getCheckedToString($assort);
	// 정산상태
	$statusValue = getCheckedToString($status);

	if ($searchKey == null || $searchKey == "") {
		if ($searchValue !== "") $search_sql = "and (memId like '%$searchValue%' or memName like '%$searchValue%') ";
		else $search_sql = "";
	} else $search_sql = "and $searchKey like '%$searchValue%' ";

	if ($memId == null || $memId == "") $memId_sql = "";
	else $memId_sql = "and sponsId = '$memId' ";

	if ($assortValue == null || $assortValue == "") $assort_sql = "";
	else $assort_sql = "and assort IN ($assortValue) ";

	if ($statusValue == null || $statusValue == "") $status_sql = "";
	else $status_sql = "and accurateStatus IN ($statusValue) ";

	if ($maxDate == null || $maxDate == "") $date_sql = "";
	else $date_sql = "and (date_format(wdate, '%Y-%m-%d') >= '$minDate' and date_format(wdate, '%Y-%m-%d') <= '$maxDate') ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM commission WHERE idx > 0 $search_sql $memId_sql $assort_sql $status_sql $date_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT @a:=@a+1 no, idx, sponsId, sponsName, memId, memName, custId, assort, resultPrice, payPrice, price, accurateStatus, wdate 
	        FROM ( select idx, sponsId, sponsName, memId, memName, custId, assort, resultPrice, payPrice, price, accurateStatus, date_format(wdate, '%Y-%m-%d') as wdate 
		           from commission 
		           where idx > 0 $search_sql $memId_sql $assort_sql $status_sql $date_sql 
		   		   order by idx desc 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$custId = $row[custId];
			$assort = selected_object($row[assort], $arrCommiAssort);
			$status = selected_object($row[accurateStatus], $arrAccurateStatus);

			if ($row[assort] == "MA" || $row[assort] == "MS") {
				$custName = $row[memName];
				$custHpNo = "";

			} else {
				$sql = "SELECT custName, hpNo FROM customer WHERE custId = '$custId'";
				$result2 = $connect->query($sql);

				if ($result2->num_rows > 0) {
					$row2 = mysqli_fetch_object($result2);

					if ($row2->hpNo != "") $row2->hpNo = aes_decode($row2->hpNo);

					$custName = $row2->custName;
					$custHpNo = $row2->hpNo;

				} else {
					$custName = "";
					$custHpNo = "";
				}
			}

			$data_info = array(
				'no'          => $row[no],
				'idx'         => $row[idx],
				'sponsId'     => $row[sponsId],
				'sponsName'   => $row[sponsName],
			    'memId'       => $row[memId],
				'memName'     => $row[memName],
				'custName'    => $custName,
				'custHpNo'    => $custHpNo,
				'assort'      => $assort,
				'resultPrice' => number_format($row[resultPrice]),
				'payPrice'    => number_format($row[payPrice]),
				'price'       => number_format($row[price]),
			    'status'      => $status,
				'wdate'       => $row[wdate]
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result = "1";
	}

	// 엑셀 다운로드 데이타 검색 
	$excelData = array();
    $sql = "SELECT @a:=@a+1 no, idx, sponsId, sponsName, memId, memName, custId, assort, resultPrice, payPrice, price, accurateStatus, wdate 
	        FROM ( select idx, sponsId, sponsName, memId, memName, custId, assort, resultPrice, payPrice, price, accurateStatus, date_format(wdate, '%Y-%m-%d') as wdate 
		           from commission 
		           where idx > 0 $search_sql $memId_sql $assort_sql $clear_sql $status_sql $date_sql 
		   		   order by idx desc 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$custId = $row[custId];
			$assort = selected_object($row[assort], $arrCommiAssort);
			$status = selected_object($row[accurateStatus], $arrAccurateStatus);

			$sql = "SELECT custName, hpNo FROM customer WHERE custId = '$custId'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);

				if ($row2->hpNo != "") $row2->hpNo = aes_decode($row2->hpNo);

				$custName = $row2->custName;
				$custHpNo = $row2->hpNo;

			} else {
				$custName = "";
				$custHpNo = "";
			}

			$data_info = array(
				'no'          => $row[no],
				'idx'         => $row[idx],
				'sponsId'     => $row[sponsId],
				'sponsName'   => $row[sponsName],
			    'memId'       => $row[memId],
				'memName'     => $row[memName],
				'custName'    => $custName,
				'custHpNo'    => $custHpNo,
				'assort'      => $assort,
				'resultPrice' => $row[resultPrice],
				'payPrice'    => $row[payPrice],
				'price'       => $row[price],
			    'status'      => $status,
				'wdate'       => $row[wdate]
			);
			array_push($excelData, $data_info);
		}
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
		'assortOptions'   => array_all_add($arrCommiAssort),
		'assort2Options'  => array_all_add($arrCommiAssort2),
		'statusOptions'   => array_all_add($arrAccurateStatus),
		'data'            => $data,
		'excelData'       => $excelData
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
