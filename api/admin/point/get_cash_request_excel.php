<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 출금요청 목록 엑셀다운로드
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> searchKey:      검색항목
	* parameter ==> searchValue:    검색값
	* parameter ==> memId:          회원ID
	* parameter ==> minDate:        등록일자-최소일자
	* parameter ==> maxDate:        등록일자-최대일자
	* parameter ==> status:         상태코드
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchValue = trim($input_data->{'searchValue'});
	$memId       = $input_data->{'memId'};
	$minDate     = $input_data->{'minDate'};
	$maxDate     = $input_data->{'maxDate'};
	$status      = $input_data->{'status'};

	$status = $status->{'code'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchKey == null || $searchKey == "") {
		if ($searchValue !== "") $search_sql = "and (memName like '%$searchValue%' or memId like '%$searchValue%' or sponsId like '%$searchValue%') ";
		else $search_sql = "";
	} else $search_sql = "and $searchKey like '%$searchValue%' ";

	if ($status == null || $status == "") $status_sql = "";
	else $status_sql = "and status = '$status' ";

	if ($memId == null || $memId == "") $memId_sql = "";
	else $memId_sql = "and memId = '$memId' ";

	if ($maxDate == null || $maxDate == "") $date_sql = "";
	else $date_sql = "and (date_format(wdate, '%Y-%m-%d') >= '$minDate' and date_format(wdate, '%Y-%m-%d') <= '$maxDate') ";

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT @a:=@a+1 no, idx, memId, memName, point, cashRate, cash, taxAmount, accountAmount, registNo, bankName, accountNo, accountName, paymentDate, status, wdate 
	        FROM ( select idx, memId, memName, point, cashRate, cash, taxAmount, accountAmount, registNo, bankName, accountNo, accountName, date_format(paymentDate, '%Y/%m/%d') as paymentDate, status, date_format(wdate, '%Y/%m/%d') as wdate 
		           from cash_request 
		           where idx > 0 $search_sql $search_sql $status_sql $memId_sql $date_sql 
		   		   order by idx desc 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC ";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$statusName = selected_object($row[status], $arrCashRequestStatus);
			
			if ($row[registNo] !== "") $row[registNo] = aes_decode($row[registNo]);
			if ($row[accountNo] !== "") $row[accountNo] = aes_decode($row[accountNo]);

			$data_info = array(
				'no'            => $row[no],
				'idx'           => $row[idx],
			    'memId'         => $row[memId],
				'memName'       => $row[memName],
				'point'         => number_format($row[point]),
				'cashRate'      => $row[cashRate],
				'cash'          => number_format($row[cash]),
				'taxAmount'     => number_format($row[taxAmount]),
				'accountAmount' => number_format($row[accountAmount]),
				'registNo'      => $row[registNo],
				'bankName'      => $row[bankName],
				'accountNo'     => $row[accountNo],
				'accountName'   => $row[accountName],
				'paymentDate'   => $row[paymentDate],
				'statusName'    => $statusName,
				'wdate'         => $row[wdate],
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result = "1";
	}

	$response = array(
		'result' => "0",
		'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
