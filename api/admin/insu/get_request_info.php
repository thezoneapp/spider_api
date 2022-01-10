<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 다이렉트보험 > 신청 정보
	* parameter ==> idx: 신청서에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

	// 신청자료 검색
    $sql = "SELECT memId, memName, seqNo, custName, hpNo, carNoType, carNo, expiredDate, custRegion, marketingAgree, marketingFlag, 
				counselDate, contractDate, insuAssort, insurFee, commission, requestStatus, wdate 
	        FROM insu_request 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

		if ($row->insuAssort == "N") $insuAssortName = "신규";
		else $insuAssortName = "갱신";

		$carNoTypeName = selected_object($row->carNoType, $arrCarNoType);
		$expiredName = selected_object($row->expiredDate, $arrExpiredDate);
		$regionName = selected_object($row->custRegion, $arrCustRegion);
		$statusName = selected_object($row->requestStatus, $arrInsuStatus);

		$data = array(
			'idx'              => $row->idx,
			'memId'            => $row->memId,
			'memName'          => $row->memName,
			'seqNo'            => $row->seqNo,
			'custName'         => $row->custName,
			'hpNo'             => $row->hpNo,
			'carNoType'        => $row->carNoType,
			'carNoTypeName'    => $carNoTypeName,
			'carNoOptions'     => $arrCarNoType,
			'carNo'            => $row->carNo,
			'expiredDate'      => $row->expiredDate,
			'expiredName'      => $expiredName,
			'expiredOptions'   => $arrExpiredDate,
			'custRegion'       => $row->custRegion,
			'regionName'       => $regionName,
			'regionOptions'    => $arrCustRegion,
			'marketingAgree'   => $row->marketingAgree,
			'marketingFlag'    => $row->marketingFlag,
			'marketingOptions' => $arrYesNo2,
			'insuAssortName'   => $insuAssortName,
			'insurFee'         => number_format($row->insurFee),
			'commission'       => number_format($row->commission),
			'requestStatus'    => $row->requestStatus,
			'statusName'       => $statusName,
			'statusOptions'    => $arrInsuStatus,
			'counselDate'      => $row->counselDate,
			'contractDate'     => $row->contractDate,
			'wdate'            => $row->wdate
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result_status = "1";
	}

	// 관리자 메모
	$memoData = array();
    $sql = "SELECT idx, adminId, adminName, adminMemo, wdate FROM insu_request_memo WHERE insuIdx = '$idx' ORDER BY idx DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'idx'       => $row[idx],
				'adminId'   => $row[adminId],
				'adminName' => $row[adminName],
				'adminMemo' => $row[adminMemo],
				'wdate'     => $row[wdate],
			);
			array_push($memoData, $data_info);
		}
	}

	// 로그 정보
	$logData = array();
    $sql = "SELECT memId, memName, status, wdate FROM insu_request_log WHERE insuIdx = '$idx' ORDER BY idx DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$statusName = selected_object($row[status], $arrInsuStatus);

			$data_info = array(
				'memId'   => $row[memId],
				'memName' => $row[memName],
				'status'  => $statusName,
				'wdate'   => $row[wdate],
			);
			array_push($logData, $data_info);
		}
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
		'data'      => $data,
		'logData'   => $logData,
		'memoData'  => $memoData,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>