<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰신청 Excel 목록
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> searchValue:    검색값
	* parameter ==> searchValue:    검색값
	* parameter ==> memId:          회원아이디
	* parameter ==> requestStatus:  신청상태
	* parameter ==> accountStatus:  입금상태
	* parameter ==> minDate:        신청최소일자
	* parameter ==> maxDate:        신청최대일자
	*/
	$input_data     = json_decode(file_get_contents('php://input'));
	$page           = $input_data->{'page'};
	$rows           = $input_data->{'rows'};
	$searchKey      = $input_data->{'searchKey'};
	$searchValue    = trim($input_data->{'searchValue'});
	$memId          = trim($input_data->{'memId'});
	$requestStatus  = $input_data->{'requestStatus'};
	$accountStatus  = $input_data->{'accountStatus'};

	$searchKey      = $searchKey->{'code'};
	$requestStatus  = $requestStatus->{'code'};
	$accountStatus  = $accountStatus->{'code'};

	$minDate        = str_replace(".", "-", $minDate);
	$maxDate        = str_replace(".", "-", $maxDate);

	if ($searchKey == null || $searchKey == "") {
		if ($searchValue !== "") $search_sql = "and (memName like '%$searchValue%' or custName like '%$searchValue%') ";
		else $search_sql = "";
	} else $search_sql = "and $searchKey like '%$searchValue%' ";

	if ($memId == null || $memId == "") $memId_sql = "";
	else $memId_sql = "and memId = '$memId' ";

	if ($requestStatus === null || $requestStatus=== "") $requestStatus_sql = "";
	else $requestStatus_sql = "and requestStatus = '$requestStatus' ";

	if ($accountStatus === null || $accountStatus=== "") $accountStatus_sql = "";
	else $accountStatus_sql = "and accountStatus = '$accountStatus' ";

	if ($maxDate === null || $maxDate=== "") $requestDate_sql = "";
	else $requestDate_sql = "and (wdate >= '$minDate' and wdate <= '$maxDate 23:59:59') ";

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT no, idx, memId, memName, custName, hpNo, useTelecom, changeTelecom, modelCode, modelName, colorCode, colorName, capacityCode, capacityName, planCode, 
	               requestStatus, commission, bankName, accountNo, accountName, accountDate, accountStatus, comment, statusMemo, adminMemo, date_format(wdate, '%Y-%m-%d') as wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, custName, hpNo, useTelecom, changeTelecom, modelCode, modelName, colorCode, colorName, capacityCode, capacityName, chargeName, 
	                      requestStatus, commission, bankName, accountNo, accountName, accountDate, accountStatus, comment, statusMemo, adminMemo, date_format(wdate, '%Y-%m-%d') as wdate 
		           from hp_request, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $memId_sql $requestStatus_sql $accountStatus_sql $requestDate_sql 
				   order by idx asc 
		         ) t 
			ORDER BY no ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$optionName = $row[colorName]. "/" . $row[capacityName];
			$useTelecomName = selected_object($row[useTelecom], $arrTelecomAssort);
			$changeTelecomName = selected_object($row[changeTelecom], $arrTelecomAssort);
			$requestStatus = selected_object($row[requestStatus], $arrRequestStatus);
			$accountStatus = selected_object($row[accountStatus], $arrAccountStatus);

			if ($row[hpNo] !== "") $row[hpNo] = aes_decode($row[hpNo]);
			if ($row[accountNo] !== "") $row[accountNo] = aes_decode($row[accountNo]);

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'hpNo'           => $row[hpNo],
				'custName'       => $row[custName],
				'useTelecom'     => $useTelecomName,
				'changeTelecom'  => $changeTelecomName,
				'modelName'      => $row[modelName],
				'colorName'      => $row[colorName],
				'capacityName'   => $row[capacityName],
				'chargeName'     => $row[chargeName],
				'requestStatus'  => $requestStatus,

				'commission'     => $row[commission],
				'bankName'       => $row[bankName],
				'accountNo'      => $row[accountNo],
				'accountName'    => $row[accountName],
				'accountDate'    => $row[accountDate],
				'accountStatus'  => $accountStatus,

				'comment'        => $row[comment],
				'adminMemo'      => $row[adminMemo],
				'wdate'          => $row[wdate]
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
		'result' => $result_ok,
		'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
