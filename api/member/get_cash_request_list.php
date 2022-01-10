<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 출금요청 목록
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> memId:          회원ID
	* parameter ==> taxAssort:      세무상태
	* parameter ==> status:         요청상태
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$memId       = $input_data->{'memId'};
	$taxAssort   = $input_data->{'taxAssort'};
	$arrStatus   = $input_data->{'status'};
	$year        = $input_data->{'year'};

	$year        = $year->{'code'};

	// 요청상태
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

	if ($taxAssort == null || $taxAssort == "") $tax_sql = "";
	else $tax_sql = "and taxAssort = '$taxAssort' ";

	if ($statusValue == null || $statusValue == "") $status_sql = "";
	else $status_sql = "and status in ($statusValue) ";

	if ($year == null || $year == "") $date_sql = "";
	else $date_sql = "and date_format(wdate, '%Y') = '$year' ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM cash_request WHERE memId = '$memId' $tax_sql $status_sql $date_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT @a:=@a+1 no, idx, point, cashRate, cash, taxAssort, taxAmount, accountAmount, registNo, bankName, accountNo, accountName, paymentDate, status, wdate 
	        FROM ( select idx, point, cashRate, cash, taxAssort, taxAmount, accountAmount, registNo, bankName, accountNo, accountName, date_format(paymentDate, '%Y/%m/%d') as paymentDate, status, date_format(wdate, '%Y/%m/%d') as wdate 
		           from cash_request 
		           where memId = '$memId' $tax_sql $status_sql $date_sql 
		   		   order by idx desc 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$taxName = selected_object($row[taxAssort], $arrTaxAssort);
			$statusName = selected_object($row[status], $arrCashRequestStatus);

			if ($row[status] == "9") $paymentDate = $row[paymentDate];
			else $paymentDate = selected_object($row[status], $arrCashRequestStatus);

			$data_info = array(
				'no'            => $row[no],
				'idx'           => $row[idx],
				'point'         => number_format($row[point]),
				'cashRate'      => $row[cashRate],
				'cash'          => number_format($row[cash]),
				'taxAmount'     => number_format($row[taxAmount]),
				'accountAmount' => number_format($row[accountAmount]),
				'paymentDate'   => $paymentDate,
				'taxAssort'     => $taxName,
				'statusName'    => $statusName,
				'wdate'         => $row[wdate],
				'isChecked'     => false,
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
		'taxOptions'      => $arrTaxAssort,
		'yearOptions'     => getYearOptions(),
		'statusOptions'   => array_all_add($arrCashRequestStatus),
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
