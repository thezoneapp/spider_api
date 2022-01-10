<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 신청서 상태값 업데이트 취득
	* parameter ==> comId:      차봇ID
	* parameter ==> fieldData:  데이타배열
	                - seqNo:    애드인슈 접수번호
	                - status:   상태코드
	                - insurFee: 보험료
	*/
	$remoteIp   = $_SERVER['REMOTE_ADDR'];
	$headers    = getallheaders();
	$data       = file_get_contents('php://input'); 
	$input_data = json_decode($data); 
	$comId      = $input_data->comId;
	$fieldData  = $input_data->fieldData;
	$token      = $headers['OPERA-TOKEN'];

	// 기초정보 테이블의 다이렉트보험 수수료를 가져온다.
	$commiRate = 0;
	$sql = "SELECT content FROM setting WHERE code in('insuRate')";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$commiRate = $row->content;

		if ($commiRate > 0) {
			$commiRate = $commiRate / 100;
		}
	}

	// api 로그
	$log_date = date("ymdHis");
	$log_file = "/home/spiderfla/upload/log/insu/" . $log_date . ".log";
	error_log ("input: " . $data . ", token: " . $token, 3, $log_file);

	// IP 체크
	$sql = "SELECT idx FROM approval_ip WHERE comId = '$comId' and ip = '$remoteIp' and status = '1'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		// 토큰 체크
		$sql = "SELECT idx FROM token WHERE comId = '$comId' and token = '$token' and expiredDate >= now()";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$seqNo       = $fieldData->seqNo;
			$status      = $fieldData->status;
			$insurFee    = $fieldData->insurFee;
			$expiredDate = $fieldData->expiredDate;

			// 상담완료, 계약취소 또는 계약완료
			if ($status == "8" || $status == "9") {
				// 신청서 정보
				$sql = "SELECT idx, memId, memName, memAssort, custId, custName, commission FROM insu_request WHERE seqNo = '$seqNo'";
				$result3 = $connect->query($sql);

				if ($result3->num_rows > 0) {
					$row = mysqli_fetch_object($result3);
					$insuIdx    = $row->idx;
					$memId      = $row->memId;
					$memName    = $row->memName;
					$memAssort  = $row->memAssort;
					$custId     = $row->custId;
					$custName   = $row->custName;
					$commission = $row->commission;

					$complete_sql = "";

					if ($status == "8" || $status == "9") {
						// 계약취소
						if ($status == "8") {
							$complete_sql = "";
							$remarks = $custName . "(계약취소)";
							$price = 0 - $commission;

						// 계약완료
						} else if ($status == "9") {
							$remarks = $custName;
							$price = $insurFee * $commiRate;
							$price = $price / 100;
							$price = (int) $price * 100;

							$complete_sql = ", contractStatus = 'Y', contractDate = curdate(), expiredDate = '$expiredDate', insurFee = '$insurFee', commission = '$price' ";
						}

						// 회원 수수료 등록
						$sql = "INSERT INTO commission (insuIdx, sponsId, sponsName, memId, memName, memAssort, custId, custName, assort, price, remarks, wdate) 
												VALUES ('$insuIdx', '$memId', '$memName', '$memId', '$memName', '$memAssort', '$custId', '$custName', 'A1', '$price', '$remarks', now())";
						$connect->query($sql);
					}

					// 신청서 상태변경
					$sql = "UPDATE insu_request SET requestStatus = '$status' $complete_sql WHERE seqNo = '$seqNo'";
					$connect->query($sql);
				}

			} else {
				// 신청서 상태변경
				if ($status == "7") { // 상담완료
					$sql = "UPDATE insu_request SET counselStatus = 'Y', counselDate = curdate(), requestStatus = '$status' WHERE seqNo = '$seqNo'";
					$connect->query($sql);
				} else {
					$sql = "UPDATE insu_request SET requestStatus = '$status' WHERE seqNo = '$seqNo'";
					$connect->query($sql);
				}
			}

			// 로그등록
			$sql = "INSERT INTO insu_request_log (insuIdx, memId, memName, status, wdate) 
									      VALUES ('$seqNo', 'chabot', '차봇', '$status', now())";
			$connect->query($sql);

			$result_status  = "200";
			$result_message = "성공";

		} else {
			$result_status  = "503";
			$result_message = "토큰값 오류입니다.";
		}

	} else {
		$result_status  = "501";
		$result_message = "승인되지 않은 접속입니다.";
	}

	$response = array(
		'resultCode' => $result_status,
		'message'    => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>