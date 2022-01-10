<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 회원 > 컨시어지 > 계약목록 > 상세정보
	* parameter
		idx: 신청서에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};
	
	//$idx = 83;

	// 신청자료 검색
    $sql = "SELECT idx, memId, memName, contractName, birthday, hpNo, gender, postNum, addr1, addr2, concern, service, contractDate, 
	               payType, paymentKind, paymentCompany, paymentNumber, payerName, payerNumber, valid, cardPasswd, withdrawHope, date_format(wdate, '%Y-%m-%d') as wdate 
	        FROM conierge_contract 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$genderName = selected_object($row->gender, $arrGender);
		$serviceName = selected_object($row->service, $arrService);
		$payTypeName = selected_object($row->payType, $arrConiergePayType);
		$kindName = selected_object($row->paymentKind, $arrPaymentKind);
		$withdrawHope = selected_object($row->withdrawHope, $arrWithdrawHope);

		if ($row->paymentKind == "CMS") $paymentCompany = selected_object($row->paymentCompany, $arrBankCode);
		else $paymentCompany = selected_object($row->paymentCompany, $arrCardCode);

		if ($row->birthday !== "") $row->birthday = aes_decode($row->birthday);
		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);
		if ($row->paymentNumber !== "") $row->paymentNumber = aes_decode($row->paymentNumber);
		if ($row->payerNumber !== "") $row->payerNumber = aes_decode($row->payerNumber);
		if ($row->valid !== "") $row->valid = aes_decode($row->valid);

		$data = array(
			'idx'            => $row->idx,
			'memId'          => $row->memId,
			'memName'        => $row->memName,
			'contractName'   => $row->contractName,
			'birthday'       => $row->birthday,
			'hpNo'           => $row->hpNo,	
			'gender'         => $genderName,			
			'postNum'		 => $row->postNum,		
			'addr1'			 => $row->addr1,	
			'addr2'			 => $row->addr2,	
			'contractDate'	 => $row->contractDate,	
			'service'        => $serviceName,
			'payType'        => $row->payType,
			'paymentKind'    => $row->paymentKind,
			'kindName'       => $kindName,
			'paymentCompany' => $paymentCompany,
			'payerName'      => $row->payerName,
			'paymentNumber'  => $row->paymentNumber,
			'valid'          => $row->valid,
			'withdrawHope'   => $withdrawHope,
		);

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result_status = "1";
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
		'data'      => $data,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>