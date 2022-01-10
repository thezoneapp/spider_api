<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* CMS 출금신청 목록
	* parameter ==> page:         해당페이지
	* parameter ==> rows:         페이지당 행의 갯수
	* parameter ==> searchKey:    검색항목
	* parameter ==> searchName:   검색값
	* parameter ==> cmsStatus:    CMS상태
	* parameter ==> memStatus:    회원상태
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchKey   = $input_data->{'searchKey'};
	$searchValue = trim($input_data->{'searchValue'});
	$memAssort   = $input_data->{'memAssort'};
	$paymentKind = $input_data->{'paymentKind'};
	$payStatus   = $input_data->{'payStatus'};
	$memStatus   = $input_data->{'memStatus'};

	$searchKey   = $searchKey->{'code'};
	$memAssort   = $memAssort->{'code'};
	$paymentKind = $paymentKind->{'code'};
	$payStatus   = $payStatus->{'code'};
	$memStatus   = $memStatus->{'code'};

	if ($page === null) $page = 1;
	if ($rows === null) $rows = 20;

	if ($searchKey === null || $searchKey === "") {
		if ($searchName !== "") $search_sql = "and (cp.memId like '%$searchValue%' or cp.memName like '%$searchValue%') ";
		else $search_sql = "";
	} else $search_sql = "and $searchKey like '%$searchValue%' ";

	if ($memAssort === null || $memAssort=== "") $memAssort_sql = "";
	else $memAssort_sql = "and cp.memAssort = '$memAssort' ";

	if ($paymentKind === null || $paymentKind === "") $paymentKind_sql = "";
	else $paymentKind_sql = "and cp.paymentKind = '$paymentKind' ";

	if ($payStatus === null || $payStatus === "") $payStatus_sql = "";
	else $payStatus_sql = "and cp.payStatus = '$payStatus' ";

	if ($memStatus === null || $memStatus === "") $memStatus_sql = "";
	else $memStatus_sql = "and m.memStatus = '$memStatus' ";

	// 전체 데이타 갯수
    $sql = "SELECT cp.idx 
			FROM cms_pay cp 
				INNER JOIN member m ON cp.memId = m.memId
			WHERE cp.idx > 0 $search_sql $memAssort_sql $paymentKind_sql $payStatus_sql $memStatus_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT @a:=@a+1 no, idx, sponsId, sponsName, memId, memName, hpNo, memAssort, cmsStatus, paymentKind, transactionId, payMonth, payAmount, wdate 
	        FROM ( select cp.idx, cp.sponsId, cp.sponsName, cp.memId, cp.memName, m.hpNo, cp.memAssort, m.cmsStatus, cp.paymentKind, cp.transactionId, cp.payMonth, cp.payAmount, date_format(cp.wdate, '%Y-%m-%d') as wdate 
		           from cms_pay cp 
					    inner join member m ON cp.memId = m.memId 
		           where cp.idx > 0  $search_sql $memAssort_sql $paymentKind_sql $payStatus_sql $memStatus_sql 
		         ) m, (select @a:= 0) as a 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$cmsStatus = selected_object($row[cmsStatus], $arrCmsStatus);
			$memAssort = selected_object($row[memAssort], $arrMemAssort);
			$paymentKind = selected_object($row[paymentKind], $arrPaymentKind);

			$data_info = array(
				'no'             => $row[no],
				'idx'           => $row[idx],
				'sponsId'       => $row[sponsId],
				'sponsName'     => $row[sponsName],
				'memId'         => $row[memId],
				'memName'       => $row[memName],
				'hpNo'          => $row[hpNo],
				'memAssort'     => $memAssort,
				'cmsStatus'     => $cmsStatus,
				'paymentKind'   => $paymentKind,
				'transactionId' => $row[transactionId],
				'payMonth'      => $row[payMonth],
				'payAmount'     => number_format($row[payAmount]),
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

	// 검색항목
	$arrSearchOption = array(
		['code' => 'memId',         'name' => '회원ID'],
		['code' => 'memName',       'name' => '회원명'],
		['code' => 'sponsId',       'name' => '스폰서ID'],
		['code' => 'sponsName',     'name' => '스폰서명'],
		['code' => 'transactionId', 'name' => '거래번호'],
	);

	$response = array(
		'result'          => $result,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'searchOptions'   => $arrSearchOption,
		'assortOptions'   => $arrMemAssort,
		'kindOptions'     => $arrPaymentKind,
		'payStatus'       => $arrPayStatus,
		'memOptions'      => $arrMemStatus,
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>