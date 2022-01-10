<?
	include "../../inc/common.php";
	include "../../inc/utility.php";
	/*
	* 회원 정보
	* parameter ==> idx: 아이디에 해당하는 idx
	*
	* 테이블 생성 후에 아래와 같이 2개의 자료를 수동으로 등록해준다.
	* memId: dream, agencyName: (주)드림프리덤, recommandId: dream
	* memId: dreamone, agencyName: 남시범, recommandId: dreamone
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, leg, sponsId, recommandId, memId, memName, memPw, hpNo, email, memAssort, contractStatus, cmsStatus, joinPayStatus, memStatus, gajaId, registNo, accountName, accountNo, accountBank, comment, wdate 
	        FROM member 
			WHERE idx = '$idx'";

	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->memPw !== "") $row->memPw = aes_decode($row->memPw);
		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);
		if ($row->email !== "") $row->email = aes_decode($row->email);
		if ($row->registNo !== "") $row->registNo = aes_decode($row->registNo);
		if ($row->accountNo !== "") $row->accountNo = aes_decode($row->accountNo);

		$memAssortName = selected_object($row->memAssort, $arrMemAssort);
		$contractStatusName = selected_object($row->contractStatus, $arrContractStatus);
		$cmsStatusName = selected_object($row->cmsStatus, $arrCmsStatus);
		$joinPayStatusName = selected_object($row->joinPayStatus, $arrJoinPayStatus);
		$memStatusName = selected_object($row->memStatus, $arrMemStatus);

		if ($row->leg == null) $row->leg = "";
		if ($row->gajaId == null) $row->gajaId = "";
		if ($row->registNo == null) $row->registNo = "";
		if ($row->accountName == null) $row->accountName = "";
		if ($row->accountNo == null) $row->accountNo = "";
		if ($row->accountBank == null) $row->accountBank = "";
		if ($row->comment == null) $row->comment = "";

		$sponsName = "";

		if ($row->sponsId != "") {
			// 스폰서 검색 
			$sql = "SELECT memName, hpNo FROM member WHERE memId = '$row->sponsId'";
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
			'email'              => $row->email,
			'memAssort'          => $row->memAssort,
			'memAssortName'      => $memAssortName,
			'assortOptions'      => $arrMemAssort,
			'contractStatus'     => $row->contractStatus,
			'contractStatusName' => $contractStatusName,
			'contractOptions'    => $arrContractStatus,
			'cmsStatus'          => $row->cmsStatus,
			'cmsStatusName'      => $cmsStatusName,
			'cmsOptions'         => $arrCmsStatus,
			'joinPayStatus'     => $row->joinPayStatus,
			'joinPayStatusName' => $joinPayStatusName,
			'joinPayOptions'    => $arrJoinPayStatus,
			'memStatus'          => $row->memStatus,
			'memStatusName'      => $memStatusName,
			'memOptions'         => $arrMemStatus,
			'gajaId'             => $row->gajaId,
			'registNo'           => $row->registNo,
			'accountName'        => $row->accountName,
			'accountNo'          => $row->accountNo,
			'accountBank'        => $row->accountBank,
			'comment'            => $row->comment,
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