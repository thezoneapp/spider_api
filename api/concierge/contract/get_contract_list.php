<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 컨시어지 > 계약목록
	* parameter
		page:           해당페이지
		rows:           페이지당 행의 갯수
		memId:          회원아이디
		searchValue:    검색값
		payType:        납입유형
		paymentKind:    납입방식
		minDate:        기간최소일자
		maxDate:        기간최대일자
		requestStatus:  신청상태
	*/
	$input_data    = json_decode(file_get_contents('php://input'));
	$page          = $input_data->{'page'};
	$rows          = $input_data->{'rows'};
	$memId         = $input_data->{'memId'};
	$searchValue   = trim($input_data->{'searchValue'});
	$payType       = $input_data->{'payType'};
	$paymentKind   = $input_data->{'paymentKind'};
	$minDate       = $input_data->{'minDate'};
	$maxDate       = $input_data->{'maxDate'};
	$requestStatus = $input_data->{'requestStatus'};

	//$memId = "a27233377";

	$searchHpNo = aes128encrypt($searchValue);

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 납입유형
	$typeValue = getCheckedToString($payType);
	// 납부수단
	$kindValue = getCheckedToString($paymentKind);
	// 신청상태
	$statusValue = getCheckedToString($requestStatus);

	if ($searchValue == null || $searchValue == "") $search_sql = "";
	else $search_sql = "and (contractName like '%$searchValue%' or hpNo like '%$searchHpNo%') ";

	if ($memId == null || $memId == "") $memId_sql = "";
	else $memId_sql = "and memId = '$memId' ";

	if ($typeValue == null || $typeValue == "") $type_sql = "";
	else $type_sql = "and payType IN ($typeValue) ";

	if ($kindValue == null || $kindValue == "") $kind_sql = "";
	else $kind_sql = "and paymentKind IN ($kindValue) ";

	if ($statusValue == null || $statusValue == "") $status_sql = "";
	else $status_sql = "and requestStatus IN ($statusValue) ";

	if ($maxDate == "") $date_sql = "";
	else $date_sql = "and (date_format(wdate, '%Y-%m-%d') >= '$minDate' and date_format(wdate, '%Y-%m-%d') <= '$maxDate') ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM concierge_contract WHERE idx > 0 $memId_sql $search_sql $type_sql $kind_sql $status_sql $date_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, memId, memName, contractName, hpNo, service, payType, paymentKind, requestStatus, wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, contractName, hpNo, service, payType, paymentKind, requestStatus, date_format(wdate, '%Y-%m-%d') as wdate 
		           from concierge_contract, (select @a:= 0) AS a 
		           where idx > 0 $memId_sql $search_sql $type_sql $kind_sql $status_sql $date_sql 
		         ) t 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$serviceName = selected_object($row[service], $arrService);
			$payTypeName = selected_object($row[payType], $arrConiergePayType);
			$kindName = selected_object($row[paymentKind], $arrPaymentKind);
			$statusName = selected_object($row[requestStatus], $arrConiergeStatus);

			if ($row[hpNo] != "") $row[hpNo] = aes_decode($row[hpNo]);

			// 회원정보
			$sql = "SELECT hpNo FROM member WHERE memId='" . $row[service] . "'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);

				if ($row2->hpNo != "") $memHpNo = aes_decode($row2->hpNo);
				else $memHpNo = "";

			} else {
				$memHpNo = "";
			}

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'memHpNo'        => $row[memHpNo],
				'contractName'   => $row[contractName],
				'hpNo'           => $row[hpNo],
				'service'        => $serviceName,
				'payType'        => $payTypeName,
				'paymentKind'    => $kindName,
				'requestStatus'  => $statusName,
				'wdate'          => $row[wdate],
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	// 엑셀다운로드 데이타 검색 
	$excelData = array();
    $sql = "SELECT no, idx, memId, memName, contractName, birthday, gender, hpNo, addr1, addr2, service,
	               payType, paymentKind, paymentCompany, paymentNumber, payerNumber, valid, withdrawHope, requestStatus, wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, contractName, birthday, gender, hpNo, addr1, addr2, service, 
			       payType, paymentKind, paymentCompany, paymentNumber, payerNumber, valid, withdrawHope, requestStatus, date_format(wdate, '%Y-%m-%d') as wdate 
		           from concierge_contract, (select @a:= 0) AS a 
		           where idx > 0 $memId_sql $search_sql $type_sql $kind_sql $status_sql $date_sql 
		         ) t 
			ORDER BY no DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[birthday] != "") $row[birthday] = aes_decode($row[birthday]);
			if ($row[hpNo] != "") $row[hpNo] = aes_decode($row[hpNo]);
			if ($row[paymentNumber] != "") $row[paymentNumber] = aes_decode($row[paymentNumber]);
			if ($row[payerNumber] != "") $row[payerNumber] = aes_decode($row[payerNumber]);
			if ($row[valid] != "") $row[valid] = aes_decode($row[valid]);

			$genderName = selected_object($row[gender], $arrGender);
			$serviceName = selected_object($row[service], $arrService);
			$payTypeName = selected_object($row[payType], $arrConiergePayType);
			$kindName = selected_object($row[paymentKind], $arrPaymentKind);
			$statusName = selected_object($row[requestStatus], $arrConiergeStatus);
			$withdrawName = selected_object($row[withdrawHope], $arrWithdrawHope);

			if ($row[paymentKind] == "CMS") $payBank = selected_object($row[paymentCompany], $arrBankCode);
			else $payBank = selected_object($row[paymentCompany], $arrCardCode);

			if ($row[paymentKind] == "CARD") {
				if (strpos($row[valid], "/") !== false) {
					$arrVaild = explode("/", $row[valid]);
					$validMonth = $arrVaild[0];
					$validYear  = $arrVaild[1];

				} else {
					$validMonth = "";
					$validYear  = "";
				}
			} else {
				$validMonth = "";
				$validYear  = "";
			}

			// 회원정보
			$sql = "SELECT hpNo, conciergeId FROM member WHERE memId='" . $row[memId] . "'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);

				if ($row2->hpNo != "") $memHpNo = aes_decode($row2->hpNo);
				else $memHpNo = "";

				$conciergeId = $row2->conciergeId;

			} else {
				$memHpNo = "";
				$conciergeId = "";
			}

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
				'receiptNo'      => "",
				'memId'          => $row[memId],
				'memName'        => $row[memName],
				'memHpNo'        => $row[memHpNo],
				'conciergeId'    => $conciergeId,
				'contractName'   => $row[contractName],
				'birthday'       => $row[birthday],
				'gender'         => $genderName,
				'hpNo'           => $row[hpNo],
				'address'        => $row[addr1] . " " . $row[addr2],
				'service'        => $serviceName,
				'payType'        => $payTypeName,
				'paymentKind'    => $kindName,
				'payBank'        => $payBank,
				'paymentNumber'  => $row[paymentNumber],
				'payerNumber'    => $row[payerNumber],
				'validYear'      => $validYear,
				'validMonth'     => $validMonth,
				'withdrawHope'   => $withdrawName,
				'requestStatus'  => $statusName,
				'joinPath'       => "기타",
				'docDelivery'    => "우편",
				'wdate'          => $row[wdate],
			);
			array_push($excelData, $data_info);
		}
	}

	$response = array(
		'result'          => $result_status,
		'message'         => $sql,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'payTypeOptions'  => array_all_add($arrConiergePayType),
		'payKindOptions'  => array_all_add($arrPaymentKind),
		'statusOptions'   => array_all_add($arrConiergeStatus),
		'data'            => $data,
		'excelData'       => $excelData,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
