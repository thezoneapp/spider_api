<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/barobill2.php";
	include '../../../api/barobill/BaroService_TI.php';

	/*
	* 관리자 > 포인트관리 > 세금계산서 > 착오에 의한 이중발급 취소
	* parameter
		mode:			상태모드 (M: 수정발행, C: 취소발행)
		idx:            수정할 발행정보 idx
	*/

	$mode          = "M";
	$idx           = "38";
	$modifyCode    = "6"; // 착오에 의한 이중발급
	
	if ($mode == "" || $idx == "") {
		exit;
	}

	// 수정계산서 발행
	if ($mode == "M") {
		$sql = "SELECT memId, tergetAssort, targetIdx, amountTotal, taxTotal, totalAmount, approvalNo, date_format(wdate, '%Y%m%d') as wdate 
				FROM tax_invoice 
				WHERE idx = '$idx'";
		$result = $connect->query($sql);
		$total = $result->num_rows;

		if ($total > 0) {
			$row = mysqli_fetch_object($result);

			$memId        = $row->memId;
			$tergetAssort = $row->tergetAssort;
			$targetIdx    = $row->targetIdx;
			$approvalNo   = $row->approvalNo;
			$wdate        = $row->wdate;
			//$wdate        = "20211020";

			$totalAmount  = "870000"; // 발급할 금액의 +/-를 반대로한 금액
			$amountTotal = "790909";
            $taxTotal = "79091";

			$params = array(
				'mode'         => "M",
				'idx'          => $idx,
				'memId'        => $memId,
				'tergetAssort' => $tergetAssort,
				'issueType'    => "M",
				'modifyCode'   => $modifyCode,
				'itemName'     => "판매수수료",
				'amountTotal'  => $amountTotal,
				'taxTotal'     => $taxTotal,
				'totalAmount'  => $totalAmount,
				'approvalNo'   => $approvalNo,
				'issueDate'    => $wdate,
			);
//print_r($params);
//exit;
			// 바로빌 > 수정세금계산서
			$response = reverseModifyTaxInvoice($params);
			$response = json_decode($response);

			$result_status  = $response->{'result'};
			$result_message = $response->{'message'};
		}
	}

	// 성공 결과를 반환합니다.
	$response = array(
		'result'    => $result_status,
		'message'   => $result_message
	);

print_r($response);
exit;

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
