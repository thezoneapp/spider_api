<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 휴대폰신청 수정
	* parameter ==> idx  :         신청서 일련번호
	* parameter ==> memId:         회원ID
	* parameter ==> custName:      고객명
	* parameter ==> telecom:       통신사
	* parameter ==> hpNo:          휴대폰번호
	* parameter ==> modelName:     모델명
	* parameter ==> callingPlan:   요금제
	* parameter ==> requestStatus: 진행상태
	* parameter ==> commission:    수수료
	* parameter ==> comment:       기타메모
	* parameter ==> statusMemo:    진행상황메모
	* parameter ==> adminMemo:     관리자메모
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$idx           = $input_data->{'idx'};
	$memId         = $input_data->{'memId'};
	$custName      = $input_data->{'custName'};
	$telecom       = $input_data->{'telecom'};
	$hpNo          = $input_data->{'hpNo'};
	$modelName     = $input_data->{'modelName'};
	$callingPlan   = $input_data->{'callingPlan'};
	$requestStatus = $input_data->{'requestStatus'};
	$commission    = $input_data->{'commission'};
	$comment       = $input_data->{'comment'};
	$statusMemo    = $input_data->{'statusMemo'};
	$adminMemo     = $input_data->{'adminMemo'};

	$telecom       = $telecom->{'code'};
	$requestStatus = $requestStatus->{'code'};

	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);

	$sql = "UPDATE hp_request SET custName = '$custName', 
	                              telecom = '$telecom', 
								  hpNo = '$hpNo', 
								  modelName = '$modelName', 
								  callingPlan = '$callingPlan', 
								  requestStatus = '$requestStatus', 
								  commission = '$commission', 
								  comment = '$comment', 
								  statusMemo = '$statusMemo', 
								  adminMemo = '$adminMemo'
			WHERE idx = '$idx'";
	$connect->query($sql);

	$result_ok = "0";
	$result_message = "저장되었습니다.";

	$response = array(
		'result'  => $result_ok,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>