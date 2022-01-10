<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰신청 목록
	* parameter
		page:            해당페이지
		rows:            페이지당 행의 갯수
		searchValue:     검색값
		requestStatus:   신청상태
		deliveryStatus:  배송상태
		writeStatus:     신청서작성상태
		agencyId:        업체ID
		dateAssort:      일자구분코드
		minDate:         일자범위-최소일자
		maxDate:         일자범위-최대일자
	*/
	$input_data     = json_decode(file_get_contents('php://input'));
	$page           = $input_data->{'page'};
	$rows           = $input_data->{'rows'};
	$searchValue    = $input_data->{'searchValue'};
	$requestStatus  = $input_data->{'requestStatus'};
	$deliveryStatus = $input_data->{'deliveryStatus'};
	$writeStatus    = $input_data->{'writeStatus'};
	$agencyId       = $input_data->{'agencyId'};
	$dateAssort     = $input_data->{'dateAssort'};
	$minDate        = $input_data->{'minDate'};
	$maxDate        = $input_data->{'maxDate'};

	$searchValue    = trim($searchValue);
	$searchHpNo     = aes128encrypt($searchValue);
	$minDate        = str_replace(".", "-", $minDate);
	$maxDate        = str_replace(".", "-", $maxDate);

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

	if ($searchValue != "") $search_sql = "and (memName like '%$searchValue%' or custName like '%$searchValue%' or hpNo like '%$searchHpNo%') ";
	else $search_sql = "";

	if ($requestValue == null || $requestValue == "") $request_sql = "";
	else $request_sql = "and requestStatus IN ($requestValue) ";

	if ($deliveryStatus == null || $deliveryStatus == "") $delivery_sql = "";
	else $delivery_sql = "and deliveryStatus = '$deliveryStatus' ";

	if ($writeStatus == null || $writeStatus == "") $write_sql = "";
	else $write_sql = "and writeStatus = '$writeStatus' ";

	if ($agencyId == null || $agencyId == "") $agency_sql = "";
	else $agency_sql = "and agencyId = '$agencyId' ";

	if ($dateAssort == null || $dateAssort == "") $date_sql = "";
	else {
		if ($dateAssort == "wdate") $date_sql = "and (date_format(wdate, '%Y-%m-%d') >= '$minDate' and date_format(wdate, '%Y-%m-%d') <= '$maxDate') ";
		else $date_sql = "and (openingDate >= '$minDate' and openingDate <= '$maxDate') ";
	}

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM hp_request WHERE idx > 0 $search_sql $request_sql $delivery_sql $write_sql $agency_sql $date_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, memId, memName, memHpNo, custName, hpNo, requestAssort, changeTelecom, modelName, colorName, capacityName, 
	               installment, discountType, commission, requestStatus, wdate, 
				   agencyId, deliveryStatus, writeStatus, openingDate, barCode, deliveryCompany, deliveryNo 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, memHpNo, custName, hpNo, requestAssort, changeTelecom, modelName, colorName, capacityName, 
			              installment, discountType, commission, requestStatus, date_format(wdate, '%Y-%m-%d') as wdate, 
						  agencyId, deliveryStatus, writeStatus, date_format(openingDate, '%Y-%m-%d') as openingDate, barCode, deliveryCompany, deliveryNo 
		           from hp_request, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $request_sql $delivery_sql $write_sql $agency_sql $date_sql 
		         ) t 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$agencyId = $row[agencyId];
			$deliveryCompany = $row[deliveryCompany];
			$telecomName = selected_object($row[changeTelecom], $arrTelecomAssort);
			$assortName = selected_object($row[requestAssort], $arrRequestAssort);
			$optionName = $row[colorName]. "/" . $row[capacityName];
			$discountTypeName = selected_object($row[discountType], $arrDiscountType3);
			$requestStatus = selected_object($row[requestStatus], $arrRequestStatus);
			$deliveryStatus = selected_object($row[deliveryStatus], $arrDeliveryStatus);
			$writeStatus = selected_object($row[writeStatus], $arrWriteStatus);

			if ($row[memHpNo] !== "") $row[memHpNo] = aes_decode($row[memHpNo]);
			if ($row[hpNo] !== "") $row[hpNo] = aes_decode($row[hpNo]);

			// 단말기 발송업체
			$sql = "SELECT agencyName FROM hp_agency WHERE agencyId = '$agencyId'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);
				$agencyName = $row2->agencyName;
			} else $agencyName = "";

			// 운송업체
			$sql = "SELECT companyName FROM delivery_company WHERE companyCode = '$deliveryCompany'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);
				$deliveryName = $row2->companyName;
			} else $deliveryName = "";

			$data_info = array(
				'no'              => $row[no],
				'idx'             => $row[idx],
				'memId'           => $row[memId],
				'memName'         => $row[memName],
				'memHpNo'         => $row[memHpNo],
				'custName'        => $row[custName],
				'hpNo'            => $row[hpNo],
				'assortName'      => $assortName,
				'telecomName'     => $telecomName,
				'modelName'       => $row[modelName],
				'optionName'      => $optionName,
				'discountType'    => $discountTypeName,
				'installment'     => $row[installment],
				'commission'      => number_format($row[commission]),
				'requestStatus'   => $requestStatus,
				'openingDate'     => $row[openingDate],
				'agencyName'      => $agencyName,
				'deliveryStatus'  => $deliveryStatus,
				'deliveryCompany' => $row[deliveryCompany],
				'deliveryName'    => $deliveryName,
				'writeStatus'     => $writeStatus,
				'barCode'         => $row[barCode],
				'deliveryNo'      => $row[deliveryNo],
				'wdate'           => $row[wdate],
				'isChecked'       => false,
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	// 엑셀 다운로드 데이타 검색 
	$excelData = array();
    $sql = "SELECT no, idx, memId, memName, memHpNo, custName, hpNo, requestAssort, changeTelecom, modelName, colorName, capacityName, 
	               installment, discountType, agencyPrice, commiPrice, payPrice, commission, requestStatus, wdate, 
				   agencyId, deliveryStatus, writeStatus, openingDate, barCode, deliveryCompany, deliveryNo 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, memHpNo, custName, hpNo, requestAssort, changeTelecom, modelName, colorName, capacityName, 
			              installment, discountType, agencyPrice, commiPrice, payPrice, commission, requestStatus, date_format(wdate, '%Y-%m-%d') as wdate, 
						  agencyId, deliveryStatus, writeStatus, date_format(openingDate, '%Y-%m-%d') as openingDate, barCode, deliveryCompany, deliveryNo 
		           from hp_request, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $request_sql $delivery_sql $write_sql $agency_sql $date_sql 
		         ) t 
			ORDER BY no DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$agencyId = $row[agencyId];
			$deliveryCompany = $row[deliveryCompany];
			$telecomName = selected_object($row[changeTelecom], $arrTelecomAssort);
			$assortName = selected_object($row[requestAssort], $arrRequestAssort);
			$optionName = $row[colorName]. "/" . $row[capacityName];
			$discountTypeName = selected_object($row[discountType], $arrDiscountType3);
			$requestStatus = selected_object($row[requestStatus], $arrRequestStatus);
			$deliveryStatus = selected_object($row[deliveryStatus], $arrDeliveryStatus);
			$writeStatus = selected_object($row[writeStatus], $arrWriteStatus);

			if ($row[memHpNo] !== "") $row[memHpNo] = aes_decode($row[memHpNo]);
			if ($row[hpNo] !== "") $row[hpNo] = aes_decode($row[hpNo]);

			// 단말기 발송업체
			$sql = "SELECT agencyName FROM hp_agency WHERE agencyId = '$agencyId'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);
				$agencyName = $row2->agencyName;
			} else $agencyName = "";

			// 운송업체
			$sql = "SELECT companyName FROM delivery_company WHERE companyCode = '$deliveryCompany'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);
				$deliveryName = $row2->companyName;
			} else $deliveryName = "";

			$data_info = array(
				'no'              => $row[no],
				'idx'             => $row[idx],
				'memId'           => $row[memId],
				'memName'         => $row[memName],
				'memHpNo'         => $row[memHpNo],
				'custName'        => $row[custName],
				'hpNo'            => $row[hpNo],
				'assortName'      => $assortName,
				'telecomName'     => $telecomName,
				'modelName'       => $row[modelName],
				'optionName'      => $optionName,
				'discountType'    => $discountTypeName,
				'installment'     => $row[installment],
				'agencyPrice'     => number_format($row[agencyPrice]),
				'commiPrice'      => number_format($row[commiPrice]),
				'payPrice'        => number_format($row[payPrice]),
				'commission'      => number_format($row[commission]),
				'requestStatus'   => $requestStatus,
				'openingDate'     => $row[openingDate],
				'agencyName'      => $agencyName,
				'deliveryStatus'  => $deliveryStatus,
				'deliveryCompany' => $row[deliveryCompany],
				'deliveryName'    => $deliveryName,
				'writeStatus'     => $writeStatus,
				'barCode'         => $row[barCode],
				'deliveryNo'      => $row[deliveryNo],
				'wdate'           => $row[wdate],
			);
			array_push($excelData, $data_info);
		}
	}

	// 단말기업체정보 
	$agencyOptions = array();
	$sql = "SELECT agencyId, agencyName FROM hp_agency ORDER BY agencyName ASC";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$dta_info = array(
				'code' => $row[agencyId],
				'name' => $row[agencyName],
			);
			array_push($agencyOptions, $dta_info);
		}
	}

	// 택배조회 API 키값
	$sql = "SELECT apiKey FROM api_key WHERE useYn = 'Y' and assortCode = 'ST'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$deliveryKey = $row->apiKey;
	}

	// 검색일자 키
	$dateOptions = array(
		['code' => 'wdate',       'name' => '신청일자'],
		['code' => 'openingDate', 'name' => '개통일자'],
	);

	$response = array(
		'result'           => $result_status,
		'rowTotal'         => $total,
		'pageCount'        => $pageCount,
		'deliveryKey'      => $deliveryKey,
		'dateOptions'      => array_all_add($dateOptions),
		'requestOptions'   => array_all_add($arrRequestStatus),
		'deliveryOptions'  => array_all_add($arrDeliveryStatus),
		'writeOptions'     => array_all_add($arrWriteStatus),
		'agencyOptions'    => array_all_add($agencyOptions),
		'data'             => $data,
		'excelData'        => $excelData
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
