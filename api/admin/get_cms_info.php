<?
	include "../../inc/common.php";
	include "../../inc/utility.php";
	/*
	* 회원 정보
	* parameter ==> idx: 아이디에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT c.idx, m.sponsId, m.recommandId, c.memId, m.memName, m.hpNo, m.memAssort, m.contractDoc, m.agreeStatus, m.cmsStatus, m.memStatus, 
	               c.paymentKind, c.wdate 
	        FROM cms c
			     inner join member m on c.memId = m.memId 
			WHERE c.idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

		$memId = $row->memId;
		$paymentKind = selected_object($row->paymentKind, $arrPaymentKind);
		$memAssortName = selected_object($row->memAssort, $arrMemAssort2);
		$cmsStatusName = selected_object($row->cmsStatus, $arrCmsStatus);
		$agreeStatusName = selected_object($row->agreeStatus, $arrAgreeStatus);
		$memStatusName = selected_object($row->memStatus, $arrMemStatus);

		if ($row->cmsMessage == null) $row->cmsMessage = "";
		if ($row->comment == null) $row->comment = "";

		$sponsName = "";

		if ($row->sponsId != "") {
			// 스폰서 검색 
			$sql = "SELECT memName FROM member WHERE memId = '$row->sponsId'";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);

			$sponsName = $row->sponsId . " / " . $row2->memName;
		}

		$data = array(
			'idx'                => $row->idx,
			'leg'                => $row->leg,
		    'sponsId'            => $sponsName,
			'recommandId'        => $row->recommandId,
			'memId'              => $row->memId,
			'memName'            => $row->memName,
			'memPw'              => $row->memPw,
			'hpNo'               => $row->hpNo,
			'contractDoc'        => $row->contractDoc,
			'paymentKind'        => $paymentKind,
			'memAssort'          => $memAssortName,
			'memStatus'          => $memStatusName,
			'agreeStatus'        => $agreeStatusName,
			'cmsStatus'          => $cmsStatusName,
			'wdate'              => $row->wdate
		);

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result = "1";
	}

	// 진행 로그
	$logData = array();
    $sql = "SELECT adminId, adminName, assort, message, wdate 
	        FROM cms_log
			WHERE memId = '$memId' 
			ORDER BY idx DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$assort = selected_object($row[assort], $arrCmsLogAssort);

			if ($row[adminId] == null) $row[adminId] = "";
			if ($row[adminName] == null) $row[adminName] = "";
			if ($row[message] == null) $row[message] = "";

			$data_info = array(
				'adminId'    => $row[adminId],
				'adminName'  => $row[adminName],
				'logAssort'  => $assort,
				'logMessage' => $row[message],
				'wdate'      => $row[wdate],
			);
			array_push($logData, $data_info);
		}
	}

	$response = array(
		'result'    => $result,
		'message'   => $result_message,
		'data'      => $data,
		'logData'   => $logData,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>