<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰신청 목록
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> searchValue:    검색값
	* parameter ==> searchValue:    검색값
	* parameter ==> memId:          회원아이디
	* parameter ==> requestStatus:  신청상태
	* parameter ==> minDate:        신청일자-최소일자
	* parameter ==> maxDate:        신청일자-최대일자
	*/
	$input_data     = json_decode(file_get_contents('php://input'));
	$page           = $input_data->{'page'};
	$rows           = $input_data->{'rows'};
	$memId          = $input_data->{'memId'};
	$searchValue    = trim($input_data->{'searchValue'});
	$requestStatus  = $input_data->{'requestStatus'};

	//$memId = "a51907770";
	//$requestStatus = "2";

	$searchHpNo = aes128encrypt($searchValue);

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 신청상태
	$requestValue = "";

	for ($i = 0; $i < count($requestStatus); $i++) {
		$item    = $requestStatus[$i];
		$code    = $item->code;
		$checked = $item->checked;

		if ($checked == true) {
			if ($requestValue != "") $requestValue .= ",";
			$requestValue .= "'" . $code . "'";
		}
	}

	if ($searchValue !== "") $search_sql = "and (memName like '%$searchValue%' or custName like '%$searchValue%' or hpNo like '%$searchHpNo%') ";
	else $search_sql = "";

	if ($requestValue == null || $requestValue == "") $requestStatus_sql = "";
	else $requestStatus_sql = "and requestStatus IN ($requestValue) ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM hp_request WHERE  memId = '$memId' $search_sql $requestStatus_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, memId, memName, custName, hpNo, telecom, modelName, colorName, capacityName, 
	               writeStatus, deliveryStatus, openingDate, commission, requestStatus, wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, custName, hpNo, changeTelecom as telecom, modelName, colorName, capacityName, 
			              writeStatus, deliveryStatus, date_format(openingDate, '%Y-%m-%d') as openingDate, commission, requestStatus, date_format(wdate, '%Y-%m-%d') as wdate 
		           from hp_request, (select @a:= 0) AS a 
		           where memId = '$memId' $search_sql $requestStatus_sql 
		         ) t 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecom = selected_object($row[telecom], $arrTelecomAssort);
			$optionName = $row[colorName]. "/" . $row[capacityName];
			$deliveryStatus = selected_object($row[deliveryStatus], $arrDeliveryStatus);
			$writeStatus = selected_object($row[writeStatus], $arrWriteStatus);
			$requestStatus = selected_object($row[requestStatus], $arrRequestStatus);

			if ($row[hpNo] !== "") $row[hpNo] = aes_decode($row[hpNo]);

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'custName'       => $row[custName],
				'telecom'        => $telecom,
				'hpNo'           => $row[hpNo],
				'modelName'      => $row[modelName],
				'optionName'     => $optionName,
				'commission'     => number_format($row[commission]),
				'writeStatus'    => $writeStatus,
				'deliveryStatus' => $deliveryStatus,
			    'requestStatus'  => $requestStatus,
				'openingDate'    => $row[openingDate],
				'wdate'          => $row[wdate],
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_ok = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_ok = "1";
	}

	// 신청상태별 건수
	$count_0 = 0;
	$count_1 = 0;
	$count_5 = 0;
	$count_8 = 0;
	$count_9 = 0;
	$sql = "SELECT requestStatus, count(idx) requestCount 
			FROM hp_request
			WHERE memId = '$memId'
			GROUP BY requestStatus";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[requestStatus] == "0") $count_0 = $row[requestCount];
			else if ($row[requestStatus] == "1") $count_1 = $row[requestCount];
			else if ($row[requestStatus] == "5") $count_5 = $row[requestCount];
			else if ($row[requestStatus] == "8") $count_8 = $row[requestCount];
			else if ($row[requestStatus] == "9") $count_9 = $row[requestCount];
		}
	}

	$statusData = array(
		'count_'    => $count_0 + $count_1 + $count_5 + $count_8 + $count_9,
		'count_0'   => $count_0,
		'count_1'   => $count_1,
		'count_5'   => $count_5,
		'count_8'   => $count_8,
		'count_9'   => $count_9,
    );

	// 검색항목
	$arrSearchOption = array(
		['code' => 'memName', 'name' => '회원명'],
		['code' => 'hpNo',    'name' => '휴대폰번호'],
	);

	$response = array(
		'result'          => $result_ok,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'searchOptions'   => $arrSearchOption,
		'statusOptions'   => array_all_add($arrRequestStatus),
		'data'            => $data,
		'statusData'      => $statusData,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
