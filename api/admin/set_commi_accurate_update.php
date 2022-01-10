<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 정산서 세부내용 수정
	* parameter ==> mode:          insert(추가), update(수정)
	* parameter ==> idx:           수정할 레코드 id
	* parameter ==> commission:    수수료
	* parameter ==> otherDescript: 가감내용
	* parameter ==> otherAmount:   가감금액
	* parameter ==> taxAssort:     과세구분
	*/

	$input_data    = json_decode(file_get_contents('php://input'));
	$idx           = $input_data->{'idx'};
	$commission    = $input_data->{'commission'};
	$otherDescript = $input_data->{'otherDescript'};
	$otherAmount   = $input_data->{'otherAmount'};
	$taxAssort     = $input_data->{'taxAssort'};

	$taxAssort     = $taxAssort->{'code'};

	if ($otherAmount == "") $otherAmount = "0";

	$commission = str_replace(",", "", $commission);
	$totalAmount = $commission + $otherAmount;

	if ($totalAmount >= 100000) $taxAssort = "T";
	else $taxAssort = "P";

	if ($taxAssort == "T") $taxAmount = 0;
	else {
		if ($totalAmount < 33000) $taxAmount = 0;
		else $taxAmount = 0 - ($totalAmount * 0.033);
	}

	$accurateAmount = $totalAmount + $tax_amount;

	$sql = "UPDATE commi_accurate SET otherDescript  = '$otherDescript', 
							          otherAmount    = '$otherAmount', 
							          totalAmount    = '$totalAmount', 
							          taxAssort      = '$taxAssort', 
							          taxAmount      = '$taxAmount', 
							          accurateAmount = '$accurateAmount' 
			WHERE idx = '$idx'";
	$connect->query($sql);

	// 성공 결과를 반환합니다.
	$result = "0";
	$result_message = "변경하였습니다.";

	$response = array(
		'result'    => $result,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>