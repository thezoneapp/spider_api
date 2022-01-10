<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 휴대폰신청 > 배송지 정보
	* parameter ==> addHpNo:    추가연락처
	* parameter ==> requestIdx: 신청서Idx
	* parameter ==> postCode:   우편번호
	* parameter ==> addr1:      기본주소
	* parameter ==> addr2:      상세주소
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$addHpNo    = $input_data->{'addHpNo'};
	$requestIdx = $input_data->{'requestIdx'};
	$postCode   = $input_data->{'postCode'};
	$addr1      = $input_data->{'addr1'};
	$addr2      = $input_data->{'addr2'};

	//$postCode = "11111";
	//$addr1 = "경기도 시흥시";
	//$addr2 = "제일빌딩 402호";
	//$addHpNo = "010-2723-3377";
	//$requestIdx = "1";

	if ($addHpNo != "") $addHpNo = aes128encrypt($addHpNo);

	$sql = "INSERT INTO hp_delivery (requestIdx, addHpNo, postCode, addr1, addr2)
	                         VALUES ('$requestIdx', '$addHpNo', '$postCode', '$addr1', '$addr2')";
	$result = $connect->query($sql);

	if ($result == "1") {
		// 성공 결과를 반환합니다.
		$result_status = "0";
		$result_message = "등록하였습니다.";

	} else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "오류가 발생하였습니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
	);
	
	//print_r($response);
	//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>