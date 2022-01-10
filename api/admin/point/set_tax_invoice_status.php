<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/barobill.php";
	include '../../../api/barobill/BaroService_TI.php';

	/*
	* 관리자 > 포인트관리 > 세금계산서 > 상태변경
	* parameter
		idx:            수정할 발행 idx
		modifyCode:		수정코드 (1: 기재사항착오, 6: 착오에 의한 이중발급)
		modifyAmount:   수정발행할 합계금액
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$idx           = $input_data->{'idx'};
	$modifyCode    = $input_data->{'modifyCode'};
	$modifyAmount  = $input_data->{'modifyAmount'};

	//$modifyCode    = "1";
	//$idx           = "34";
	//$modifyAmount  = "150000";

	if ($modifyCode == "" || $idx == "") {
		exit;
	}

	if ($modifyAmount != "") $modifyAmount = str_replace(",", "", $modifyAmount);

	// 수정계산서 발행
	if ($modifyCode == "1" || $modifyCode == "6") {
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
			$amountTotal  = $row->amountTotal;
			$taxTotal     = $row->taxTotal;
			$totalAmount  = $row->totalAmount;
			$approvalNo   = $row->approvalNo;
			$wdate        = $row->wdate;

			$params = array(
				'idx'          => $idx,
				'memId'        => $memId,
				'tergetAssort' => $tergetAssort,
				'issueType'    => "M",
				'modifyCode'   => $modifyCode,
				'itemName'     => "판매수수료",
				'amountTotal'  => 0 - $amountTotal,
				'taxTotal'     => 0 - $taxTotal,
				'totalAmount'  => 0 - $totalAmount,
				'approvalNo'   => $approvalNo,
				'issueDate'    => $wdate,
			);

			// 바로빌 > 수정세금계산서 > 마이너스 계산서 발행 api호출
			$response = reverseModifyTaxInvoice($params);
			$response = json_decode($response);

			$result_status  = $response->{'result'};
			$result_message = $response->{'message'};

			// 기재사항착오인 경우
			if ($result_status == "0" && $modifyCode == "1") {
				$amountTotal = round($modifyAmount / 1.1);   // 세액
				$taxTotal = $modifyAmount - $amountTotal;    // 공급가액

				$params = array(
					'idx'          => $idx,
					'memId'        => $memId,
					'tergetAssort' => $tergetAssort,
					'issueType'    => "M",
					'modifyCode'   => "1", // 기재사항의 착오 정정
					'itemName'     => "판매수수료",
					'amountTotal'  => $amountTotal,
					'taxTotal'     => $taxTotal,
					'totalAmount'  => $modifyAmount,
					'approvalNo'   => $approvalNo,
					'issueDate'    => $wdate,
				);

				// 바로빌 > 수정세금계산서 api호출
				$response = reverseModifyTaxInvoice($params);
				$response = json_decode($response);

				$result_status  = $response->{'result'};
				$result_message = $response->{'message'};
			}
		}
	}

	// 성공 결과를 반환합니다.
	$response = array(
		'result'    => $result_status,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>