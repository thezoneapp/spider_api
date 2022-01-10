<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 다이렉트보험 > 신청목록
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
	$agreeStatus    = $input_data->{'agreeStatus'};
	$requestStatus  = $input_data->{'requestStatus'};

	//$memId = "a51907770";
	//$requestStatus = "2";

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 동의상태
	$agreeStatusValue = "";

	for ($i = 0; $i < count($agreeStatus); $i++) {
		$item    = $agreeStatus[$i];
		$code    = $item->code;
		$checked = $item->checked;

		if ($checked == true) {
			if ($agreeStatusValue != "") $agreeStatusValue .= ",";
			$agreeStatusValue .= "'" . $code . "'";
		}
	}

	// 신청상태
	$requestStatusValue = "";

	for ($i = 0; $i < count($requestStatus); $i++) {
		$item    = $requestStatus[$i];
		$code    = $item->code;
		$checked = $item->checked;

		if ($checked == true) {
			if ($requestStatusValue != "") $requestStatusValue .= ",";
			$requestStatusValue .= "'" . $code . "'";
		}
	}

	if ($searchValue !== "") $search_sql = "and (memName like '%$searchValue%' or custName like '%$searchValue%') ";
	else $search_sql = "";

	if ($agreeStatusValue == null || $agreeStatusValue == "") $agreeStatus_sql = "";
	else $agreeStatus_sql = "and marketingAgree IN ($agreeStatusValue) ";

	if ($requestStatusValue == null || $requestStatusValue == "") $requestStatus_sql = "";
	else $requestStatus_sql = "and requestStatus IN ($requestStatusValue) ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM insu_request WHERE  memId = '$memId' $search_sql $agreeStatus_sql $requestStatus_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, memId, memName, custName, hpNo, carNoType, carNo, expiredDate, custRegion, marketingAgree, insurFee, commission, requestStatus, wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, custName, hpNo, carNoType, carNo, expiredDate, custRegion, marketingAgree, insurFee, commission, requestStatus, date_format(wdate, '%Y-%m-%d') as wdate 
		           from insu_request, (select @a:= 0) AS a 
		           where memId = '$memId' $search_sql $agreeStatus_sql $requestStatus_sql 
		         ) t 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$carNoType = selected_object($row[carNoType], $arrCarNoType);
			$expiredDate = selected_object($row[expiredDate], $arrExpiredDate);
			$custRegion = selected_object($row[custRegion], $arrCustRegion);
			$marketingAgree = selected_object($row[marketingAgree], $arrYesNo);
			$requestStatus = selected_object($row[requestStatus], $arrInsuStatus);

			if ($row[hpNo] !== "") $row[hpNo] = aes_decode($row[hpNo]);

	
			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'seqNo'          => $row[seqNo],
				'custName'       => $row[custName],
				'hpNo'           => $row[hpNo],
				'carNoType'      => $carNoType,
				'carNo'          => $row[carNo],
				'expiredDate'    => $expiredDate,
				'custRegion'     => $custRegion,
				'marketingAgree' => $marketingAgree,
				'insurFee'       => number_format($row[insurFee]),
				'commission'     => number_format($row[commission]),
				'requestStatus'  => $requestStatus,
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

	$response = array(
		'result'          => $result_ok,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'pageCount'       => $pageCount,
		'agreeOptions'    => array_all_add($arrYesNo),
		'statusOptions'   => array_all_add($arrInsuStatus),
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
