<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 현금인출 가능한 정보
	* parameter ==> memId: 회원ID
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId = $input_data->{'memId'};

	//$memId = "a29296478";

	// 현금지급율
    $sql = "SELECT ifnull(content,0) AS cashOutRate FROM setting WHERE CODE = 'cashOutRate'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$exchangeRate = $row->cashOutRate;

	} else {
		$exchangeRate = 0;
	}

	// 보유 포인트
    $sql = "SELECT ifnull(SUM(point),0) as myPoint FROM point WHERE memId = '$memId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$myPoint = $row->myPoint;

	// 현금요청진행중
    $sql = "SELECT ifnull(SUM(point),0) as pausePoint FROM cash_request WHERE memId = '$memId' and (status != '8' and status != '9')";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$pausePoint = $row->pausePoint;

	// 회원 정산 계좌 정보
    $sql = "SELECT registNo, accountName, accountNo, accountBank FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->registNo !== "") $row->registNo = aes_decode($row->registNo);
		if ($row->accountNo !== "") $row->accountNo = aes_decode($row->accountNo);

		$bankName = selected_object($row->accountBank, $arrBankCode);

		$myPoint = $myPoint - $pausePoint;

		$data = array(
			'memId'        => $memId,
			'registNo'     => $row->registNo,
			'accountName'  => $row->accountName,
			'accountNo'    => $row->accountNo,
			'bankName'     => $bankName,
			'myPoint'      => number_format($myPoint),
			'exchangeRate' => $exchangeRate,
			'usableCash'   => number_format($myPoint * ($exchangeRate/100)),
		);

		$result_status = "0";

	} else {
		$data = array();
		$result_status = "1";
	}

	$response = array(
		'result' => $result_status,
		'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
