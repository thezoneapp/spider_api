<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 회원 정보
	* parameter ==> idx: 아이디에 해당하는 idx
	*
	* 테이블 생성 후에 아래와 같이 2개의 자료를 수동으로 등록해준다.
	* memId: dream, agencyName: (주)드림프리덤, recommendId: dream
	* memId: dreamone, agencyName: 남시범, recommendId: dreamone
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, sponsId, recommendId, memId, memName, memPw, hpNo, email, memAssort, contractStatus, cmsStatus, joinPayStatus, clearStatus, memStatus, 
	               cmsId, gajaId, insuId, conciergeId, registNo, accountName, accountNo, accountBank, comment, approvalDate, wdate 
	        FROM member 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->memPw != "") $row->memPw = aes_decode($row->memPw);
		if ($row->hpNo != "") $row->hpNo = aes_decode($row->hpNo);
		if ($row->email != "") $row->email = aes_decode($row->email);
		if ($row->registNo != "") $row->registNo = aes_decode($row->registNo);
		if ($row->accountNo != "") $row->accountNo = aes_decode($row->accountNo);

		$memId = $row->memId;
		$memAssortName = selected_object($row->memAssort, $arrMemAssort);
		$contractStatusName = selected_object($row->contractStatus, $arrContractStatus);
		$cmsStatusName = selected_object($row->cmsStatus, $arrCmsStatus);
		$joinPayStatusName = selected_object($row->joinPayStatus, $arrJoinPayStatus);
		$clearStatusName = selected_object($row->clearStatus, $arrClearStatus);
		$memStatusName = selected_object($row->memStatus, $arrMemStatus);
		$bankName = selected_object($row->accountBank, $arrBankCode);

		if ($row->gajaId == null) $row->gajaId = "";
		if ($row->registNo == null) $row->registNo = "";
		if ($row->accountName == null) $row->accountName = "";
		if ($row->accountNo == null) $row->accountNo = "";
		if ($row->accountBank == null) $row->accountBank = "";
		if ($row->comment == null) $row->comment = "";
		if ($row->approvalDate == null) $row->approvalDate = "";

		// 추천인 검색 
		$recommendName = "";

		if ($row->recommendId != "") {
			$sql = "SELECT memName FROM member WHERE memId = '$row->recommendId'";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);

			$recommendName = $row2->memName . " / " . $row->recommendId;
		}

		// 스폰서 검색 
		$sponsName = "";

		if ($row->sponsId != "") {
			$sql = "SELECT memName FROM member WHERE memId = '$row->sponsId'";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);

			$sponsName = $row2->memName . " / " . $row->sponsId;
		}

		// 포인트 정보
		$totalPoint = 0;       // 포인트합계
		$havePoint = 0;        // 포인트잔액
		$pointOutPause = 0;    // 인출대기포인트
		$pointOutComplete = 0; // 인출완료포인트

		$sql = "SELECT assort, point 
				FROM (
					SELECT 'SP' as assort, ifnull(SUM(price),0) AS point
					FROM commission
					WHERE sponsId = '$memId' AND accurateStatus != '9'
					union
					SELECT 'SA' as assort, ifnull(SUM(point),0) AS point
					FROM point
					WHERE memId = '$memId' AND assort = 'OA' 
					union
					SELECT 'SO' as assort, ifnull(SUM(point),0) AS point
					FROM point
					WHERE memId = '$memId' AND assort IN ('IJ','N1','N2')
					union
					SELECT 'OC' as assort, ifnull(SUM(point),0) AS point
					FROM point
					WHERE memId = '$memId' AND assort = 'OC' 
					union
					SELECT 'OP' as assort, 0 - ifnull(SUM(point),0) AS point
					FROM cash_request
					WHERE memId = '$memId' AND (status != '8' AND status != '9')
					union		
					SELECT 'OB' as assort, ifnull(SUM(point),0) AS point 
					FROM point
					WHERE memId = '$memId'
				 ) t";	
		$result2 = $connect->query($sql);

		while($row2 = mysqli_fetch_array($result2)) {
			if ($row2[assort] == "SP") { // 적립대기
				$totalPoint += $row2[point];
			} else if ($row2[assort] == "SA") { // 적립완료
				$totalPoint += $row2[point];
			} else if ($row2[assort] == "SO") { // 기타적립
				$totalPoint += $row2[point];
			} else if ($row2[assort] == "OC") { // 출금완료
				$outComplete = abs($row2[point]);
			} else if ($row2[assort] == "OP") { // 출금대기
				$outPause = abs($row2[point]);
			} else if ($row2[assort] == "OB") { // 포인트잔액
				$havePoint = $row2[point];
			}
		}

		$pointData = array(
			'totalPoint'  => number_format($totalPoint),
			'havePoint'   => number_format($havePoint),
			'outPause'    => number_format($outPause),
			'outComplete' => number_format($outComplete),
		);

		// 컨시어지 ID 발급상태
		$sql = "SELECT requestStatus FROM concierge_request WHERE memId = '$memId'";
		$result2 = $connect->query($sql);

	    if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);
			$conciergeStatus = selected_object($row2->requestStatus, $arrIdRequestStatus);

		} else {
			$conciergeStatus = "";
		}

		// cms 정보
		$sql = "SELECT paymentKind FROM cms WHERE memId = '$memId'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);
			$paymentKind = selected_object($row2->paymentKind, $arrPaymentKind);

		} else {
			$paymentKind = "";
		}

		$data = array(
			'idx'                => $row->idx,
		    'sponsName'          => $sponsName,
			'recommendName'      => $recommendName,
			'memId'              => $row->memId,
			'memName'            => $row->memName,
			'memPw'              => $row->memPw,
			'hpNo'               => $row->hpNo,
			'email'              => $row->email,
			'memAssort'          => $row->memAssort,
			'memAssortName'      => $memAssortName,
			'assortOptions'      => $arrMemAssort,
			'contractStatus'     => $row->contractStatus,
			'contractStatusName' => $contractStatusName,
			'paymentKind'        => $paymentKind,
			'contractOptions'    => $arrContractStatus,
			'cmsStatus'          => $row->cmsStatus,
			'cmsStatusName'      => $cmsStatusName,
			'cmsOptions'         => $arrCmsStatus,
			'joinPayStatus'      => $row->joinPayStatus,
			'joinPayStatusName'  => $joinPayStatusName,
			'joinPayOptions'     => $arrJoinPayStatus,
			'clearStatus'        => $row->clearStatus,
			'clearStatusName'    => $clearStatusName,
			'clearOptions'       => $arrClearStatus,
			'memStatus'          => $row->memStatus,
			'memStatusName'      => $memStatusName,
			'memOptions'         => $arrMemStatus,
			'cmsId'              => $row->cmsId,
			'gajaId'             => $row->gajaId,
			'conciergeId'        => $row->conciergeId,
			'conciergeStatus'    => $conciergeStatus,
			'registNo'           => $row->registNo,
			'accountName'        => $row->accountName,
			'accountNo'          => $row->accountNo,
			'accountBank'        => $row->accountBank,
			'accountBankName'    => $bankName,
			'bankOptions'        => $arrBankCode,
			'comment'            => $row->comment,
			'approvalDate'       => $row->approvalDate,
			'wdate'              => $row->wdate,
			'pointData'          => $pointData,
		);

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result = "1";
	}

	// 진행 로그
	//logAssort: 로그구분 => C: CMS, E: 계약, A: 회원상태
	$logData = array();
    $sql = "SELECT adminId, adminName, logAssort, status, wdate 
	        FROM member_log
			WHERE memIdx = '$idx' 
			ORDER BY idx DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$logAssort = selected_object($row[logAssort], $arrLogAssort);

			if ($row[logAssort] == "M") $status = selected_object($row[status], $arrMemAssort2);
			else if ($row[logAssort] == "E") $status = selected_object($row[status], $arrContractStatus);
			else if ($row[logAssort] == "C") $status = selected_object($row[status], $arrCmsStatus);
			else if ($row[logAssort] == "P") $status = selected_object($row[status], $arrJoinPayStatus);
			else if ($row[logAssort] == "D") $status = selected_object($row[status], $arrClearStatus);
			else if ($row[logAssort] == "A") $status = selected_object($row[status], $arrMemStatus);

			$data_info = array(
				'adminId'   => $row[adminId],
				'adminName' => $row[adminName],
				'logAssort' => $logAssort,
				'status'    => $status,
				'wdate'     => $row[wdate],
			);
			array_push($logData, $data_info);
		}
	}

	// 관리자 메모
	$memoData = array();
    $sql = "SELECT idx, adminId, adminName, adminMemo, wdate FROM member_memo WHERE memIdx = '$idx' ORDER BY idx DESC";
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

	$response = array(
		'result'    => $result,
		'message'   => $result_message,
		'data'      => $data,
		'logData'   => $logData,
		'memoData'  => $memoData,
    );
//print_r($response);
//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>