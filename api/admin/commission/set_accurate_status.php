<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 정산서 상태변경
	* parameter ==> idx:    정산서 idx
	* parameter ==> status: 변경될 상태값 (0: 정산대기, 2:정산보류, 9:정산완료)
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$arrIdx = $input_data->{'idx'};
	$status = $input_data->{'status'};

	$idx = "";

	for ($i = 0; count($arrIdx) > $i; $i++) {
		if ($idx != "") $idx .= ",";

		$idx .= "'" . $arrIdx[$i] . "'";
	}

	// 정산완료
	if ($status == "9") {
		// 수수료목록의 정산상태를 "정산완료"로 변경
		$sql = "UPDATE commission SET accurateStatus = '$status' WHERE accurateIdx IN ($idx)";
		$connect->query($sql);

		// 정산서의 정산상태를 "정산완료"로 변경
		$sql = "UPDATE commi_accurate SET accurateStatus = '$status' WHERE idx IN ($idx)";
		$connect->query($sql);

		// 포인트 목록의 동일한 정산번호를 삭제
		$sql = "DELETE FROM point WHERE accurateIdx = '$idx'";
		$connect->query($sql);

		// 출금요청 목록의 동일한 정산번호를 삭제
		$sql = "DELETE FROM cash_request WHERE accurateIdx = '$idx'";
		$connect->query($sql);

		// 현금지급율 정보
		$cashRate = "0";
		$sql = "SELECT code, ifnull(content,0) as cashRate FROM setting WHERE code in ('cashOutRate')";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			while($row = mysqli_fetch_array($result)) {
				if ($row[code] == "cashOutRate") $cashRate = $row[cashRate]; // 현금지급율
			}
		}

		// 정산정보
		$assort = "OA";
		$pointStatus = "0";
		$sql = "SELECT ca.idx, ca.memId, ca.memName, ca.totalAmount, date_format(ca.minDate, '%Y/%m/%d') as minDate, date_format(ca.maxDate, '%Y/%m/%d') as maxDate, m.joinPayStatus 
                FROM commi_accurate ca 
	                 INNER JOIN member m ON ca.memId = m.memId
				WHERE ca.idx IN ($idx)";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			while($row = mysqli_fetch_array($result)) {
				$accurateIdx = $row[idx];
				$memId         = $row[memId];
				$memName       = $row[memName];
				$point         = $row[totalAmount];
				$descript      = $row[minDate] . " ~ " . $row[maxDate];
				$joinPayStatus = $row[joinPayStatus];

				// 포인트 목록에 정산대금으로 추가
				$sql = "INSERT INTO point (memId, memName, assort, descript, point, accurateIdx, wdate)
								   VALUES ('$memId', '$memName', '$assort', '$descript', '$point', '$accurateIdx', now())";
				$connect->query($sql);

				/*
				// ************************** 강제로 출금요청 처리
				// 회원 정산 계좌 정보
				$sql = "SELECT registNo, accountName, accountNo, accountBank FROM member WHERE memId = '$memId'";
				$result2 = $connect->query($sql);
				$row2 = mysqli_fetch_object($result2);

				$registNo    = $row2->registNo;
				$accountName = $row2->accountName;
				$accountNo   = $row2->accountNo;
				$bankName    = $row2->accountBank;

				// 사업소득세 계산할 것
				$cash = $point * 0.8;
				$withHoldTax = ($cash * 0.03) / 10;
				$withHoldTax = (int) $withHoldTax * 10; // 원천세
				$localTax = ($withHoldTax * 0.1) / 10;
				$localTax = (int) $localTax * 10;       // 지방세
				$taxAmount = $withHoldTax + $localTax;  // 사업소득세

				if ($taxAmount <= 1190) $taxAmount = 0;

				$accountAmount = $cash - $taxAmount;    // 실지급금액

				// 출금요청 목록에 추가
				$pointStatus = "0";
				$cashRate = "80";
				$sql = "INSERT INTO cash_request (memId, memName, point, cashRate, cash, taxAmount, accountAmount, registNo, bankName, accountNo, accountName, status, wdate)
										  VALUES ('$memId', '$memName', '$point', '$cashRate', '$cash', '$taxAmount', '$accountAmount', '$registNo', '$bankName', '$accountNo', '$accountName', '$pointStatus', now())";
				$connect->query($sql);
				*/
			}
		}

	} else {
		// 정산대기
		if ($status == "0") {
			// 정산서 세부내용 삭제
			$sql = "DELETE FROM commi_accurate_detail WHERE accurateIdx IN ($idx)";
			$connect->query($sql);

			// 정산서 삭제
			$sql = "DELETE FROM commi_accurate WHERE idx IN ($idx)";
			$connect->query($sql);

			// 수수료목록의 정산상태 변경
			$sql = "UPDATE commission SET accurateIdx = null, accurateStatus = '$status' WHERE accurateIdx IN ($idx)";
			$connect->query($sql);

			// 수수료 목록의 MD유치 보너스 삭제
			$sql = "DELETE FROM commission WHERE assort = 'CS' and accurateIdx IN ($idx)";
			$connect->query($sql);

			// 포인트정보 삭제
			$sql = "DELETE FROM point WHERE accurateIdx IN ($idx)";
			$connect->query($sql);

		} else {
			// 수수료목록의 정산상태 변경
			$sql = "UPDATE commission SET accurateStatus = '$status' WHERE accurateIdx IN ($idx)";
			$connect->query($sql);
		}

		// 정산서의 정산상태 변경
		$sql = "UPDATE commi_accurate SET accurateStatus = '$status' WHERE idx IN ($idx)";
		$connect->query($sql);

		// 포인트 목록의 해당 정산대금을 삭제
		$sql = "DELETE FROM point WHERE accurateIdx IN ($idx)";
		$connect->query($sql);

		// 출금요청 목록의 동일한 정산번호를 삭제
		$sql = "DELETE FROM cash_request WHERE accurateIdx IN ($idx)";
		$connect->query($sql);
	}

	$response = array(
		'idx' => $idx,
		'message' => selected_object($status, $arrAccurateStatus) . "'으로 변경되었습니다."
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>