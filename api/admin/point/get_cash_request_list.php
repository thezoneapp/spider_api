<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 출금요청 목록
	* parameter
		page:           해당페이지
		rows:           페이지당 행의 갯수
		searchKey:      검색항목
		searchValue:    검색값
		memId:          회원ID
		year:           등록일자-년도
		month:          등록일자-월
		status:         요청상태
		taxAssort:      세금구분
		issueStatus:    발행상태
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchValue = trim($input_data->{'searchValue'});
	$memId       = $input_data->{'memId'};
	$year        = $input_data->{'year'};
	$month       = $input_data->{'month'};
	$status      = $input_data->{'status'};
	$taxAssort   = $input_data->{'taxAssort'};
	$issueStatus = $input_data->{'issueStatus'};

	// 요청상태
	$requestValue = getCheckedToString($status);
	// 세금구분
	$taxValue = getCheckedToString($taxAssort);
	// 발행상태
	$issueValue = getCheckedToString($issueStatus);

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchValue !== "") $search_sql = "and (memId like '%$searchValue%' or memName like '%$searchValue%') ";
	else $search_sql = "";

	if ($year == null || $year == "") $date_sql = "";
	else {
		if ($month == null || $month == "") $date_sql = "and date_format(wdate, '%Y') = '$year'";
		else $date_sql = "and date_format(wdate, '%Y-%m') = '$year-$month'";
	}

	if ($requestValue == null || $requestValue == "") $requestStatus_sql = "";
	else $requestStatus_sql = "and status IN ($requestValue) ";

	if ($taxValue == null || $taxValue == "") $taxAssort_sql = "";
	else $taxAssort_sql = "and taxAssort IN ($taxValue) ";

	if ($issueValue == null || $issueValue == "") $issueStatus_sql = "";
	else $issueStatus_sql = "and ifnull(issueStatus, '0') IN ($issueValue) ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM cash_request WHERE idx > 0 $search_sql $date_sql $requestStatus_sql $taxAssort_sql $issueStatus_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT @a:=@a+1 no, idx, memId, memName, point, cashRate, cash, taxAssort, taxAmount, accountAmount, registNo, bankName, accountNo, accountName, paymentDate, status, wdate 
	        FROM ( select idx, memId, memName, point, cashRate, cash, taxAssort, taxAmount, accountAmount, registNo, bankName, accountNo, accountName, date_format(paymentDate, '%Y/%m/%d') as paymentDate, status, date_format(wdate, '%Y/%m/%d') as wdate 
		           from cash_request 
		           where idx > 0 $search_sql $date_sql $requestStatus_sql $taxAssort_sql $issueStatus_sql 
		   		   order by idx desc 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$statusName = selected_object($row[status], $arrCashRequestStatus);
			$taxAssortName = selected_object($row[taxAssort], $arrTaxAssort);

			$data_info = array(
				'no'            => $row[no],
				'idx'           => $row[idx],
			    'memId'         => $row[memId],
				'memName'       => $row[memName],
				'point'         => number_format($row[point]),
				'cashRate'      => $row[cashRate],
				'cash'          => number_format($row[cash]),
				'taxAssort'     => $taxAssortName,
				'taxAmount'     => number_format($row[taxAmount]),
				'accountAmount' => number_format($row[accountAmount]),
				'paymentDate'   => $row[paymentDate],
				'statusName'    => $statusName,
				'wdate'         => $row[wdate],
				'isChecked'     => false,
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

	// 엑셀 다운로드 데이타 검색 
	$excelData = array();
    $sql = "SELECT @a:=@a+1 no, idx, memId, memName, point, cashRate, cash, taxAmount, accountAmount, registNo, bankCode, accountNo, accountName, paymentDate, status, taxAssort, wdate 
	        FROM ( select idx, memId, memName, point, cashRate, cash, taxAmount, accountAmount, registNo, bankCode, accountNo, accountName, taxAssort, date_format(paymentDate, '%Y/%m/%d') as paymentDate, status, date_format(wdate, '%Y/%m/%d') as wdate 
		           from cash_request 
		           where idx > 0 $search_sql $date_sql $requestStatus_sql $taxAssort_sql $issueStatus_sql 
		   		   order by idx desc 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC ";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$statusName = selected_object($row[status], $arrCashRequestStatus);
			$bankName = selected_object($row[bankCode], $arrBankCode);

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
				'bankName'      => $bankName,
				'accountNo'     => $row[accountNo],
				'accountName'   => $row[accountName],
				'paymentDate'   => $row[paymentDate],
				'statusName'    => $statusName,
				'taxAssort'     => $row[taxAssort],
				'wdate'         => $row[wdate],
			);
			array_push($excelData, $data_info);
		}
	}

	$response = array(
		'result'          => $result_status,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'yearOptions'     => getYearOptions(),
		'monthOptions'    => getMonthOptions(),
		'statusOptions'   => array_all_add($arrCashRequestStatus),
		'taxOptions'      => array_all_add($arrTaxAssort),
		'issueOptions'    => array_all_add($arrErrorYn),
		'data'            => $data,
		'excelData'       => $excelData
    );
//print_r($response);
//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
