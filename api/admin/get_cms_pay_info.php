<?
	include "../../inc/common.php";
	include "../../inc/utility.php";
	/*
	* 출금신청 정보
	* parameter ==> idx: 아이디에 해당하는 idx
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

	$memId = "";
    $sql = "SELECT idx, memId, memName, memAssort, paymentKind, transactionId, payMonth, payAmount, date_format(wdate, '%Y-%m-%d') as wdate 
	        FROM cms_pay 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$memId = $row->memId;

		$paymentKind = selected_object($row->paymentKind, $arrPaymentKind);
		$memAssortName = selected_object($row->memAssort, $arrMemAssort);

		$data = array(
			'idx'           => $row->idx,
			'memId'         => $row->memId,
			'memName'       => $row->memName,
			'memAssort'     => $memAssortName,
			'paymentKind'   => $paymentKind,
			'transactionId' => $row->transactionId,
			'payMonth'      => $row->payMonth,
			'payAmount'     => $row->payAmount,
			'wdate'         => $row->wdate
		);

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$data = array();
		$result = "1";
	}

	// 출금신청 로그
	$logData = array();
    $sql = "SELECT payMonth, payAmount, message, status, adminId, adminName, date_format(wdate, '%Y-%m-%d') as wdate 
	        FROM cms_pay_log
			WHERE memId = '$memId' 
			ORDER BY idx DESC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$status = selected_object($row[status], $arrErrorYn);

			$data_info = array(
				'adminId'   => $row[adminId],
				'adminName' => $row[adminName],
				'payMonth'  => $row[payMonth],
				'payAmount' => number_format($row[payAmount]),
				'message'   => $row[message],
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