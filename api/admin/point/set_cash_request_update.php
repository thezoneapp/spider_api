<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 출금요청 세부내용 수정
	* parameter ==> mode:          insert(추가), update(수정)
	* parameter ==> idx:           수정할 레코드 id
	* parameter ==> memId:         회원ID
	* parameter ==> memName:       회원명
	* parameter ==> point:         사용포인트
	* parameter ==> cashRate:      현금지급율
	* parameter ==> cash:          지급액
	* parameter ==> taxAmount:     소득세
	* parameter ==> accountAmount: 실지급액
	* parameter ==> registNo:      주민번호
	* parameter ==> bankCode:      입금은행코드
	* parameter ==> accountNo:     입금계좌
	* parameter ==> accountName:   예금주명
	* parameter ==> paymentDate:   입금일자
	* parameter ==> status:        요청상태
	* parameter ==> wdate:         등록일자
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$mode          = $input_data->{'mode'};
	$idx           = $input_data->{'idx'};
	$memId         = $input_data->{'memId'};
	$memName       = $input_data->{'memName'};
	$point         = $input_data->{'point'};
	$cashRate      = $input_data->{'cashRate'};
	$cash          = $input_data->{'cash'};
	$taxAmount     = $input_data->{'taxAmount'};
	$accountAmount = $input_data->{'accountAmount'};
	$registNo      = $input_data->{'registNo'};
	$bankCode      = $input_data->{'bankCode'};
	$accountNo     = $input_data->{'accountNo'};
	$accountName   = $input_data->{'accountName'};
	$wdate         = $input_data->{'wdate'};

	$bankCode      = $bankCode->{'code'};

	$point         = str_replace(",", "", $point);
	$cash          = str_replace(",", "", $cash);
	$taxAmount     = str_replace(",", "", $taxAmount);
	$accountAmount = str_replace(",", "", $accountAmount);

	if ($point == "") $point = "0";
	if ($cash == "") $cash = "0";
	if ($registNo != "") $registNo = aes128encrypt($registNo);
	if ($accountNo != "") $accountNo = aes128encrypt($accountNo);
	if ($wdate == "") $wdate = date("Y-m-d His");

	if ($mode == "insert") {
		$sql = "INSERT INTO cash_request (memId, memName, point, cashRate, cash, taxAmount, accountAmount, registNo, bankCode, accountNo, accountName, status, wdate)
						          VALUES ('$memId', '$memName', '$point', '$cashRate', '$cash', '$taxAmount', '$accountAmount', '$registNo', '$bankCode', '$accountNo', '$accountName', '$status', '$wdate')";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_statue = "0";
		$result_message = "등록하였습니다.";

	} else {
		$sql = "UPDATE cash_request SET memId = '$memId', 
								        memName = '$memName', 
								        point = '$point', 
										cashRate = '$cashRate', 
										cash = '$cash', 
										taxAmount = '$taxAmount', 
										accountAmount = '$accountAmount', 
										registNo = '$registNo', 
										bankCode = '$bankCode', 
										accountNo = '$accountNo', 
										accountName = '$accountName', 
								        wdate    = '$wdate'
				WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_statue = "0";
		$result_message = "변경하였습니다.";
	}

	$response = array(
		'result'    => $result_statue,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>