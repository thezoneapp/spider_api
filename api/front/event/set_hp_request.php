<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/kakaoTalk.php";
	include "../../../inc/customer.php";

	/*
	* 이벤트 > 휴대폰신청 등록
	* parameter
	    mode:          insert
		channel:       유입채널
		custName:      고객명
		hpNo:          휴대폰번호
		useTelecom:    현재통신사
		modelCode:     모델코드
		modelName:     모델명
	*/

	$back_data  = json_decode(file_get_contents('php://input'));
	$mode       = $back_data->{'mode'};
	$channelIdx = $back_data->{'channel'};
	$custName   = $back_data->{'custName'};
	$hpNo       = $back_data->{'hpNo'};
	$useTelecom = $back_data->{'useTelecom'};
	$modelCode  = $back_data->{'modelCode'};
	$modelName  = $back_data->{'modelName'};

	//$mode = "insert";
	//$channelIdx    = "1";
	//$custName      = "박하민";
	//$hpNo          = "010-6649-2082";
	//$useTelecom    = "S";
	//$modelCode     = "N98";
	//$modelName     = "캘럭시 Z 플립 3";

	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);

	if ($mode == "insert") {
		// 신청서 등록
		$sql = "INSERT INTO hp_event_request (channelIdx, custName, hpNo, useTelecom, modelCode, modelName, requestStatus, wdate)
						        VALUES ('$channelIdx', '$custName', '$hpNo', '$useTelecom', '$modelCode', '$modelName', '0', now())";
		$result = $connect->query($sql);

		if ($result == 1) {
			$result_status = "0";
			$result_message = "신청이 완료되었습니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result_status = "1";
			$result_message = "오류가 발생하였습니다.";
		}
	}

	$response = array(
		'result'     => $result_status,
		'message'    => $result_message
	);
//print_r($response);
//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>