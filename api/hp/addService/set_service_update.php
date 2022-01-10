<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 신청 > 부가서비스 > 정보 추가/수정
	* parameter 
		mode:           insert(추가), update(수정)
		idx:            수정할 레코드 id
		serviceCode:    서비스코드
		serviceName:    서비스명
		servicePrice:   서비스요금
		periodAssort:   유지기간 구분
		periodDay:      유지기간 직접입력
		descript:       서비스설명
		telecom:        통신사코드
		useYn:          사용여부
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$mode         = $data_back->{'mode'};
	$idx          = $data_back->{'idx'};
	$serviceCode  = $data_back->{'serviceCode'};
	$serviceName  = $data_back->{'serviceName'};
	$servicePrice = $data_back->{'servicePrice'};
	$periodAssort = $data_back->{'periodAssort'};
	$periodDay    = $data_back->{'periodDay'};
	$descript     = $data_back->{'descript'};
	$telecom      = $data_back->{'telecom'};
	$useYn        = $data_back->{'useYn'};

	//$telecom      = $telecom->{'code'};

	$servicePrice = str_replace(",", "", $servicePrice);

	if ($periodAssort != "D") $periodDay = "";

	if ($mode == "insert") {
		$sql = "SELECT idx FROM hp_add_service WHERE serviceCode = '$serviceCode'";
		$result = $connect->query($sql);

	    if ($result->num_rows == 0) {
			if ($serviceCode == "") {
				$sql = "SELECT ifnull(max(idx),0) + 1 AS maxIdx FROM hp_add_service";
				$result2 = $connect->query($sql);
				$row2 = mysqli_fetch_object($result2);
				$serviceCode = $telecom . "-" . $row2->maxIdx;
			}

			$sql = "INSERT INTO hp_add_service (serviceCode, serviceName, servicePrice, periodAssort, periodDay, descript, telecom, useYn)
							            VALUES ('$serviceCode', '$serviceName', '$servicePrice', '$periodAssort', '$periodDay', '$descript', '$telecom', '$useYn')";
			$connect->query($sql);

			// 성공 결과를 반환합니다.
			$result_status = "0";
			$result_message = "등록하였습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "중복된 '서비스코드'입니다.";
		}

	} else {
		$sql = "UPDATE hp_add_service SET serviceCode = '$serviceCode',
		                                  serviceName = '$serviceName', 
								          servicePrice = '$servicePrice', 
										  periodAssort = '$periodAssort', 
								          periodDay = '$periodDay', 
									      descript = '$descript', 
									      telecom = '$telecom', 
								          useYn = '$useYn' 
						WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "변경하였습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>