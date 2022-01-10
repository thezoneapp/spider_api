<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* CMS 출금신청 로그
	* parameter ==> page:         해당페이지
	* parameter ==> rows:         페이지당 행의 갯수
	* parameter ==> searchKey:    검색항목
	* parameter ==> searchName:   검색값
	* parameter ==> cmsStatus:    회원구분
	* parameter ==> status:       신청결과
	* parameter ==> payMonth:     해당년월
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchKey   = $input_data->{'searchKey'};
	$searchValue = trim($input_data->{'searchValue'});
	$status      = $input_data->{'status'};
	$payMonth    = $input_data->{'payMonth'};

	$searchKey   = $searchKey->{'code'};
	$paymentKind = $paymentKind->{'code'};
	$status      = $status->{'code'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchKey === null || $searchKey === "") {
		if ($searchName !== "") $search_sql = "and (memId like '%$searchValue%' or memName like '%$searchValue%') ";
		else $search_sql = "";
	} else $search_sql = "and $searchKey like '%$searchValue%' ";

	if ($payMonth === null || $payMonth === "") $payMonth_sql = "";
	else $payMonth_sql = "and payMonth = '$payMonth' ";

	if ($status === null || $status === "") $status_sql = "";
	else $status_sql = "and status = '$status' ";

	// 전체 데이타 갯수
    $sql = "SELECT idx 
			FROM cms_pay_log 
			WHERE idx > 0 $search_sql $status_sql $payMonth_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT @a:=@a+1 no, idx, memId, memName, payMonth, paymentKind, payAmount, message, status, adminId, adminName, wdate 
	        FROM ( select idx, memId, memName, payMonth, paymentKind, payAmount, message, status, adminId, adminName, date_format(wdate, '%Y-%m-%d') as wdate 
		           from cms_pay_log 
		           where idx > 0  $search_sql $status_sql $payMonth_sql 
		         ) m, (select @a:= 0) as a 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$paymentKind = selected_object($row[paymentKind], $arrPaymentKind);
			$status = selected_object($row[status], $arrErrorYn);

			$data_info = array(
				'no'          => $row[no],
				'idx'         => $row[idx],
				'memId'       => $row[memId],
				'memName'     => $row[memName],
				'payMonth'    => $row[payMonth],
				'paymentKind' => $paymentKind,
				'payAmount'   => number_format($row[payAmount]),
				'status'      => $status,
				'message'     => $row[message],
				'adminId'     => $row[adminId],
				'adminName'   => $row[adminName],
				'wdate'       => $row[wdate],
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
		['code' => 'memId',         'name' => '회원ID'],
		['code' => 'memName',       'name' => '회원명'],
		['code' => 'hpNo',          'name' => '휴대폰번호'],
		['code' => 'sponsId',       'name' => '스폰서아이디'],
		['code' => 'transactionId', 'name' => '거래번호'],
	);

	$response = array(
		'result'          => $result,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'searchOptions'   => $arrSearchOption,
		'assortOptions'   => $arrMemAssort,
		'kindOptions'     => $arrPaymentKind,
		'statusOptions'   => $arrErrorYn,
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>