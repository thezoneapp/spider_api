<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 현금인출요청 등록
	* parameter ==> memId:       회원ID
	* parameter ==> memName:     회원명
	* parameter ==> outCash:     출금요청금액
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$memId   = $input_data->{'memId'};
	$memName = $input_data->{'memName'};
	$cash    = $input_data->{'outCash'};

	//$memId = "a34935267";
	//$memName = "권오현";
	//$cash = "50000";

	// 현금지급율
    $sql = "SELECT ifnull(content,0) AS cashOutRate FROM setting WHERE CODE = 'cashOutRate'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$cashRate = $row->cashOutRate;

	} else {
		$cashRate = 0;
	}

	// 사용포인트 구함
	$addRate = 100 - $cashRate;
	$addPoint = ($cash * $addRate) / $cashRate;
	$point = $cash + $addPoint;

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

	if ($point > ($myPoint - $pausePoint)) {
		$result_status = "1";
		$result_message = "보유중인 포인트를 초과하였습니다.";

	} else {
		// 회원정보
		$sql = "SELECT registNo, accountName, accountNo, accountBank, taxAssort FROM member WHERE memId = '$memId'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		$registNo    = $row->registNo;
		$accountName = $row->accountName;
		$accountNo   = $row->accountNo;
		$bankCode    = $row->accountBank;
		$taxAssort   = $row->taxAssort;

		// 사업자등록 정보
		if ($taxAssort == "T") {
			$sql = "SELECT certifyStatus FROM tax_member WHERE memId = '$memId'";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);

			if ($row2->certifyStatus != "Y") $taxAssort = "P";
		}

		if ($taxAssort != "T") { // 사업소득세
			$withHoldTax = ($cash * 0.03) / 10;
			$withHoldTax = (int) $withHoldTax * 10; // 원천세
			$localTax = ($withHoldTax * 0.1) / 10;
			$localTax = (int) $localTax * 10;       // 지방세
			$taxAmount = $withHoldTax + $localTax;  // 사업소득세

			if ($taxAmount <= 1190) $taxAmount = 0;

			$accountAmount = $cash - $taxAmount;    // 실지급금액

		} else { // 부가가치세
			$taxAmount = 0;
			$accountAmount = $cash;    // 실지급금액
		}

		// 출금요청 목록에 추가
		$pointStatus = "0";
		$sql = "INSERT INTO cash_request (memId, memName, point, cashRate, cash, taxAmount, accountAmount, taxAssort, registNo, bankCode, accountNo, accountName, status, wdate)
								  VALUES ('$memId', '$memName', '$point', '$cashRate', '$cash', '$taxAmount', '$accountAmount', '$taxAssort', '$registNo', '$bankCode', '$accountNo', '$accountName', '$pointStatus', now())";
		$connect->query($sql);

		$result_status = "0";
		$result_message = "'신청완료'되었습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>