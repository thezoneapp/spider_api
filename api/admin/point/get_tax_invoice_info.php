<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 포인트관리 > 세금계산서목록 > 세부 정보
	* parameter ==> idx: 정산서 idx
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT memId, memName, taxAssort, issueType, corpNum, corpName, ceoName, amountTotal, taxTotal, totalAmount, mgtNum, approvalNo, tergetAssort, targetIdx, wdate 
	        FROM tax_invoice 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$taxAssort = selected_object($row->taxAssort, $arrTaxIssueAssort);
		$issueType = selected_object($row->issueType, $arrTaxIssueType);

		if ($row->tergetAssort == "C") $tergetAssort = "현금인출";
		else $tergetAssort = "";

		$data = array(
			'idx'           => $idx,
			'memId'         => $row->memId,
			'memName'       => $row->memName,
			'taxAssort'     => $taxAssort,
			'issueType'     => $issueType,
			'corpNum'       => $row->corpNum,
			'corpName'      => $row->corpName,
			'ceoName'       => $row->ceoName,
			'amountTotal'   => number_format($row->amountTotal),
			'taxTotal'      => number_format($row->taxTotal),
			'totalAmount'   => number_format($row->totalAmount),
			'mgtNum'        => $row->mgtNum,
			'approvalNo'    => $row->approvalNo,
			'tergetAssort'  => $tergetAssort,
			'targetIdx'     => $row->targetIdx,
			'wdate'         => $row->wdate,
		);
	}

	$response = array(
		'result'        => "0",
		'modifyOptions' => $arrModifyAssort,
		'data'          => $data,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
