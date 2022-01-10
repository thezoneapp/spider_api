<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 출금요청 세부 정보
	* parameter ==> idx: 정산서 idx
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT memId, memName, point, cashRate, cash, taxAssort, taxAmount, accountAmount, registNo, bankCode, accountNo, accountName, paymentDate, status, 
	               wdate, taxAssort, issueCode, issueStatus, issueMessage, cancelMessage 
	        FROM cash_request 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$paymentDate = $row->paymentDate;
		$statusName = selected_object($row->status, $arrCashRequestStatus);
		$issueName = selected_object($row->issueStatus, $arrErrorYn);
		$bankName = selected_object($row->bankCode, $arrBankCode);
		$taxName = selected_object($row->taxAssort, $arrBusinessAssort);

		if ($row->paymentDate == null) $row->paymentDate = "";
		if ($row->registNo !== "") $row->registNo = aes_decode($row->registNo);
		if ($row->accountNo !== "") $row->accountNo = aes_decode($row->accountNo);

		$data = array(
			'idx'           => $row->idx,
			'memId'         => $row->memId,
			'memName'       => $row->memName,
			'point'         => number_format($row->point),
			'cashRate'      => $row->cashRate,
			'cash'          => number_format($row->cash),
			'taxAmount'     => number_format($row->taxAmount),
			'accountAmount' => number_format($row->accountAmount),
			'registNo'      => $row->registNo,
			'bankCode'      => $row->bankCode,
			'bankName'      => $bankName,
			'accountNo'     => $row->accountNo,
			'accountName'   => $row->accountName,
			'paymentDate'   => $row->paymentDate,
			'status'        => $row->status,
			'statusName'    => $statusName,
			'wdate'         => $row->wdate,
			'taxAssort'     => $taxName,
			'issueCode'     => $row->issueCode,
			'issueStatus'   => $row->issueStatus,
			'issueName'     => $issueName,
			'issueMessage'  => $row->issueMessage,
			'cancelMessage' => $row->cancelMessage,
		);

	} else {
		$data = array(
			'idx'           => "",
			'memId'         => "",
			'memName'       => "",
			'point'         => "",
			'cashRate'      => "",
			'cash'          => "",
			'taxAmount'     => "",
			'accountAmount' => "",
			'registNo'      => "",
			'bankName'      => "",
			'accountNo'     => "",
			'accountName'   => "",
			'paymentDate'   => "",
			'status'        => "",
			'statusName'    => "",
			'wdate'         => "",
			'issueCode'     => "",
			'issueStatus'   => "",
			'issueMessage'  => "",
			'cancelMessage' => "",
		);
	}

	// 세금계산서 발행 데이타 검색 
	$taxData = array();
    $sql = "SELECT corpName, amountTotal, taxTotal, totalAmount, wdate 
	        FROM tax_invoice 
		    WHERE tergetAssort = 'C' and targetIdx = '$idx' 
		    ORDER BY wdate DESC ";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$data_info = array(
				'corpName'    => $row[corpName],
				'amountTotal' => number_format($row[amountTotal]),
				'taxTotal'    => number_format($row[taxTotal]),
				'totalAmount' => number_format($row[totalAmount]),
				'wdate'       => $row[wdate]
			);
			array_push($taxData, $data_info);
		}
	}

	if ($paymentDate == "") {
		$arrCashRequestStatus2 = array(
			['code' => '0', 'name' => '대기중'],
			['code' => '1', 'name' => '처리중'],
			['code' => '2', 'name' => '보류중'],
			['code' => '7', 'name' => '취소요청'],
			['code' => '8', 'name' => '취소완료'],
		);

	} else {
		$arrCashRequestStatus2 = array(
			['code' => '0', 'name' => '대기중'],
			['code' => '1', 'name' => '처리중'],
			['code' => '2', 'name' => '보류중'],
			['code' => '7', 'name' => '취소요청'],
		);
	}

	$response = array(
		'result'        => "0",
		'bankOptions'   => $arrBankCode,
		'statusOptions' => $arrCashRequestStatus2,
		'data'          => $data,
		'taxData'       => $taxData,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
