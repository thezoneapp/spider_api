<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 회원 > 컨시어지 > 계약목록
	* parameter
		page:           해당페이지
		rows:           페이지당 행의 갯수
		memId:          회원아이디
		searchValue:    검색값
		payType:        납입유형
		paymentKind:    납입방식
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$memId       = $input_data->{'memId'};
	$payType     = $input_data->{'payType'};
	$paymentKind = $input_data->{'paymentKind'};
	$searchValue = trim($input_data->{'searchValue'});

	//$memId = "a27233377";

	$searchHpNo = aes128encrypt($searchValue);

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 납입유형
	$typeValue = getCheckedToString($payType);
	// 납부수단
	$kindValue = getCheckedToString($paymentKind);

	if ($searchValue == null || $searchValue == "") $search_sql = "";
	else $search_sql = "and (contractName like '%$searchValue%' or hpNo like '%$searchHpNo%') ";

	if ($typeValue == null || $typeValue == "") $type_sql = "";
	else $type_sql = "and payType IN ($typeValue) ";

	if ($kindValue == null || $kindValue == "") $kind_sql = "";
	else $kind_sql = "and paymentKind IN ($kindValue) ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM concierge_contract WHERE memId = '$memId' $search_sql $type_sql $kind_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, memId, memName, contractName, hpNo, service, payType, paymentKind, wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, contractName, hpNo, service, payType, paymentKind, date_format(wdate, '%Y-%m-%d') as wdate 
		           from concierge_contract, (select @a:= 0) AS a 
		           where memId = '$memId' $search_sql $type_sql $kind_sql
		         ) t 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$serviceName = selected_object($row[service], $arrService);
			$payTypeName = selected_object($row[payType], $arrConiergePayType);
			$kindName = selected_object($row[paymentKind], $arrPaymentKind);

			if ($row[hpNo] !== "") $row[hpNo] = aes_decode($row[hpNo]);

			$data_info = array(
				'no'           => $row[no],
				'idx'          => $row[idx],
				'contractName' => $row[contractName],
				'hpNo'         => $row[hpNo],
				'service'      => $serviceName,
				'payType'      => $payTypeName,
				'paymentKind'  => $kindName,
				'wdate'        => $row[wdate],
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	$response = array(
		'result'          => $result_status,
		'message'         => $sql,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'payTypeOptions'  => array_all_add($arrConiergePayType),
		'payKindOptions'  => array_all_add($arrPaymentKind),
		'data'            => $data,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
