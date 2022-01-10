<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 미지급금 입금 정보
	* parameter ==> memId: 회원ID
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId = $input_data->{'memId'};

    $sql = "SELECT memId, memName, plusAmount, minusAmount, balanceAmount 
            FROM ( select memId, memName, sum(plusAmount) AS plusAmount, sum(minusAmount) AS minusAmount, SUM(balanceAmount) AS balanceAmount 
                   from ( select memId, memName, plusAmount, minusAmount, (plusAmount + minusAmount) balanceAmount
                          from ( select memId, memName, if(assort = 'A', accountAmount, 0) AS plusAmount, if(assort = 'P', 0 - accountAmount, 0) AS minusAmount 
                                 from commi_account
								 where memId = '$memId' 
                               ) t1
                        ) t2
                   group by memId
                 ) t3";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$sql = "SELECT accountName, accountNo, accountBank FROM member WHERE memId = '$memId'";
		$result2 = $connect->query($sql);
		$row2 = mysqli_fetch_object($result2);

		if ($row->accountName == null) $row->accountName = "";
		if ($row->accountNo == null) $row->accountNo = "";
		if ($row->accountBank == null) $row->accountBank = "";

		$data = array(
			'memId'          => $row->memId,
			'memName'        => $row->memName,
			'plusAmount'     => number_format($row->plusAmount),
			'minusAmount'    => number_format($row->minusAmount),
			'balanceAmount'  => number_format($row->balanceAmount),
			'accountAmount'  => $row->balanceAmount,
			'accountName'    => $row2->accountName,
			'accountNo'      => $row2->accountNo,
			'bankName'       => $row2->accountBank,
			'accountDate'    => date("Y-m-d")
		);

		// 업데이트 모드로 결과를 반환합니다.
		$result_ok = "0";

	} else {
		$data = array(
			'memId'          => '',
			'memName'        => '',
			'plusAmount'     => '',
			'minusAmount'    => '',
			'balanceAmount'  => '',
			'accountAmount'  => '',
			'accountName'    => '',
			'accountNo'      => '',
			'bankName'       => '',
			'accountDate'    => '',
		);

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
