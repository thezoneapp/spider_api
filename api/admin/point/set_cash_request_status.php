<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/barobill.php";
	include '../../../api/barobill/BaroService_TI.php';

	/*
	* 관리자 > 출금요청 > 세부내용 > 상태변경
	* parameter
		mode:			상태모드: 요청상태(S), 입금정보(P), 계산서수정발행(T)
		idx:            수정할 요청서 idx
		status:         요청상태값
		paymentDate:    입금일자
		reasonAssort:   수정발행 사유코드: 공급가변경(2), 계약해제(4)
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$mode          = $input_data->{'mode'};
	$idx           = $input_data->{'idx'};
	$status        = $input_data->{'status'};
	$paymentDate   = $input_data->{'paymentDate'};
	$cancelMessage = $input_data->{'cancelMessage'};

	$status        = $status->{'code'};

	//$mode          = "C";
	//$idx           = "510";

	// 요청상태변경
	if ($mode == "S") {
		$sql = "UPDATE cash_request SET status = '$status' WHERE idx = '$idx'";
		$connect->query($sql);

	// 입금정보 등록
	} else if ($mode == "P") {
		$sql = "SELECT idx FROM point WHERE cashIdx = '$idx'";
		$result = $connect->query($sql);
		$total = $result->num_rows;

		if ($total == 0) {
			// 현금인출요청정보
			$sql = "SELECT memId, memName, point, cash, taxAssort, wdate FROM cash_request WHERE idx = '$idx'";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);

			$memId     = $row->memId;
			$memName   = $row->memName;
			$point     = $row->point;
			$cash      = $row->cash;
			$taxAssort = $row->taxAssort;
			$wdate     = $row->wdate;

			// 포인트 테이블 등록
			$tergetAssort = "C"; // 발행대상 -> 현금인출
			$assort = "OC";
			$descript = "신청일자: " . $wdate;
			$point = 0 - $point;

			$sql = "INSERT INTO point (memId, memName, assort, descript, point, cashIdx, wdate)
							   VALUES ('$memId', '$memName', '$assort', '$descript', '$point', '$idx', now())";
			$connect->query($sql);

			if ($taxAssort == "T") { // 사업자회원이면... 세금계산서 역발행
				$params = array(
					'tergetAssort' => $tergetAssort,
					'idx'          => $idx,
					'memId'        => $memId,
					'itemName'     => "판매수수료",
					'totalAmount'  => $cash,
				);

				// 바로빌 > 세금계산서발행 api호출
				reverseIssueTaxInvoice($params);
			}

			// 입금완료 변경
			$sql = "UPDATE cash_request SET paymentDate = '$paymentDate', status = '9' WHERE idx = '$idx'";
			$connect->query($sql);
		}

	// 취소계산서발행
	} else if ($mode == "C") {
		$sql = "SELECT idx FROM point WHERE cashIdx = '$idx'";
		$result = $connect->query($sql);
		$total = $result->num_rows;

		if ($total > 0) {
			// 현금인출요청정보
			$sql = "SELECT memId, memName, point, cash, taxAssort, paymentDate FROM cash_request WHERE idx = '$idx'";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);

			$memId       = $row->memId;
			$memName     = $row->memName;
			$point       = $row->point;
			$cash        = $row->cash;
			$taxAssort   = $row->taxAssort;
			$paymentDate = $row->paymentDate;

			// 포인트 테이블 등록
			$tergetAssort = "C"; // 발행대상 -> 현금인출
			$modifyCode = "4";   // 수정사유 -> 계약해제
			$assort = "OC";

			$sql = "INSERT INTO point (memId, memName, assort, descript, point, cashIdx, wdate)
							   VALUES ('$memId', '$memName', '$assort', '$cancelMessage', '$point', '$idx', now())";
			$connect->query($sql);

			if ($taxAssort == "T") { // 사업자회원이면... 세금계산서 역발행
				$sql = "SELECT ifnull(max(idx),0) as maxIdx FROM tax_invoice";
				$result2 = $connect->query($sql);
				$row2 = mysqli_fetch_object($result2);
				$maxIdx = $row2->maxIdx;

				$timestamp = date("ymdHis");
				$mgtNum = $timestamp . "-" . ($maxIdx + 1);

				$params = array(
					'tergetAssort' => $tergetAssort,
					'issueType'    => "C",
					'modifyCode'   => $modifyCode,
					'mgtNum'       => $mgtNum,
					'idx'          => $idx,
					'memId'        => $memId,
					'itemName'     => "판매수수료",
					'totalAmount'  => $totalAmount,
				);

				// 바로빌 > 수정세금계산서 api호출
				reverseModifyTaxInvoice($params);
			}

			// 취소완료
			$sql = "UPDATE cash_request SET cancelMessage = '$cancelMessage', status = '8' WHERE idx = '$idx'";
			$connect->query($sql);
		}
	}

	// 성공 결과를 반환합니다.
	$result_statue = "0";
	$result_message = "변경하였습니다.";

	$response = array(
		'result'    => $result_statue,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>