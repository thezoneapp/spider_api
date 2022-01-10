<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 신청서 상태값 업데이트 취득
	* parameter ==> comId:      애드인슈ID
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

	// api 로그 저장
	$log_date = date("ymdHis");
	$log_file = "/home/spiderfla/upload/log/insu/" . $log_date . ".log";
	error_log ($data, 3, $log_file);
	//error_log ($remoteI, 3, $log_file);

	$commiRate = "0.065";

	// IP 체크
	$sql = "SELECT idx FROM approval_ip WHERE comId = '$comId' and ip = '$remoteIp' and status = '1'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$token = $headers['OPERA-TOKEN'];

		// 토큰 체크
		$sql = "SELECT idx FROM token WHERE comId = '$comId' and token = '$token' and expiredDate >= now()";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			for ($i = 0; $i < count($fieldData); $i++) {
				$field    = $fieldData[$i];
				$seqNo    = $field->seqNo;
				$status   = $field->status;
				$insurFee = $field->insurFee;

				// 계약취소 또는 계약완료
				if ($status == "8" || $status == "9") {
					// 신청서 정보
					$sql = "SELECT idx, memId, memName, custName, commission FROM insu_request WHERE seqNo = '$seqNo'";
					$result3 = $connect->query($sql);

					if ($result3->num_rows > 0) {
						$row = mysqli_fetch_object($result3);
						$insuIdx    = $row->idx;
						$memId      = $row->memId;
						$memName    = $row->memName;
						$custName   = $row->custName;
						$commission = $row->commission;

						// 계약취소
						if ($status == "8") {
							$complete_sql = "";
							$remarks = $custName . "(계약취소)";
							$price = 0 - $commission;

						// 계약완료
						} else if ($status == "9") {
							$remarks = $custName;
							$price = $insurFee * $commiRate;
							$complete_sql = ", insurFee = '$insurFee', commission = '$price' ";
						}

						// 신청서 상태변경
						$sql = "UPDATE insu_request SET requestStatus = '$status' $complete_sql WHERE seqNo = '$seqNo'";
						$connect->query($sql);

						// 회원 수수료 등록
						$sql = "INSERT INTO commission (insuIdx, sponsId, sponsName, memId, memName, assort, price, clearStatus, remarks, wdate) 
												VALUES ('$insuIdx', '$memId', '$memName', '$memId', '$memName', 'A1', '$price', 'N', '$remarks', now())";
						$connect->query($sql);
					}

				} else {
					// 신청서 상태변경
					$sql = "UPDATE insu_request SET requestStatus = '$status' WHERE seqNo = '$seqNo'";
					$connect->query($sql);
				}
			}

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