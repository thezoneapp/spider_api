<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	//include "../../../inc/kakaoTalk.php";

	/*
	* 관리자 > 컨시어지 > 계약목록 > 수정
	* parameter 
		idx:            일련번호
		contractName:   계약자명
		birthday:       생년월일
		hpNo:           휴대폰번호
		gender:         성별(M: 남자, F: 여자)
		postNum:        우편번호
		addr1:          기본 주소
		addr2:          나머지 주소
		concern:        관심분야
		service:        서비스
		purpose:        계약목적
		contractDate:   계약일자
		productType:    상품타입구분
		productPayType: 상품결제구분(1: 결합상품, 2: 단품월결제, 3:단품연결제)
		requestStatus:  신청상태(1: 접수완료, 2: 가입완료)
		payType:        납입유형(M: 월납, Y: 연납, C: 제휴)
		paymentKind:    납부수단(CARD: 신용카드, CMS: 자동이체)
		paymentCompany: 은행/카드사 코드
		paymentNumber:  계좌/카드번호
		payerName:      예금주/소유주
		payerNumber:    생년월일/사업자번호
		valid:          유효기간(MM/YY)
		cardPasswd:     카드비밀번호(앞2자리)
		withdrawHope:   출금희망일
		requestStatus:  신청상태
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx            = $input_data->{'idx'};
	$contractName   = trim($input_data->{'contractName'});
	$birthday       = $input_data->{'birthday'};
	$hpNo           = $input_data->{'hpNo'};
	$gender         = $input_data->{'gender'};
	$postNum        = $input_data->{'postNum'};
	$addr1          = $input_data->{'addr1'};
	$addr2          = $input_data->{'addr2'};
	$concern        = $input_data->{'concern'};
	$service        = $input_data->{'service'};
	$purpose        = $input_data->{'purpose'};
	$contractDate   = $input_data->{'contractDate'};
	$payType        = $input_data->{'payType'};
	$paymentKind    = $input_data->{'paymentKind'};
	$paymentCompany = $input_data->{'paymentCompany'};
	$paymentNumber  = $input_data->{'paymentNumber'};
	$payerName      = $input_data->{'payerName'};
	$payerNumber    = $input_data->{'payerNumber'};
	$valid          = $input_data->{'valid'};
	$cardPasswd     = $input_data->{'cardPasswd'};
	$withdrawHope   = $input_data->{'withdrawHope'};
	$requestStatus  = $input_data->{'requestStatus'};

	//$memId          = "a51607340";
	//$memName        = "안예린";
	//$contractName   = "개발테스트";
	//$birthday       = "2222-22-22";
	//$hpNo           = "010-2723-3377";
	//$gender         = "F";
	//$postNum        = "06281";
	//$addr1          = "서울 강남구 남부순환로 2907";
	//$addr2          = "222222";
	//$concern        = "";
	//$service        = "";
	//$purpose        = "";
	//$contractDate   = "2021-11-25";
	//$paymentKind    = "CARD";
	//$paymentCompany = "BC";
	//$paymentNumber  =  "22222222222";
	//$payerName      = "안예린";
	//$payerNumber    = "960512";
	//$valid          = "22/22";
	//$cardPasswd     = "10";
	//$withdrawHope   = "05";

	if ($birthday != "") $birthday = aes128encrypt($birthday);
	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);

	if ($paymentNumber != "") $paymentNumber = aes128encrypt($paymentNumber);
	if ($payerNumber != "") $payerNumber = aes128encrypt($payerNumber);
	if ($valid != "") $valid = aes128encrypt($valid);
	if ($cardPasswd != "") $cardPasswd = aes128encrypt($cardPasswd);

	$concern = implode(',', $concern);

	// 계약정보 저장
	$sql = "UPDATE concierge_contract SET contractName = '$contractName', 
	                                      birthday = '$birthday', 
							 	 		  hpNo = '$hpNo', 
										  gender = '$gender', 
										  postNum = '$postNum', 
										  addr1 = '$addr1', 
										  addr2 = '$addr2', 
										  service = '$service', 
										  contractDate = '$contractDate', 
										  payType = '$payType', 
										  paymentKind = '$paymentKind', 
										  paymentCompany = '$paymentCompany', 
										  paymentNumber = '$paymentNumber', 
										  payerName = '$payerName', 
										  payerNumber = '$payerNumber', 
										  valid = '$valid', 
										  cardPasswd = '$cardPasswd', 
										  withdrawHope = '$withdrawHope', 
										  requestStatus = '$requestStatus' 
				WHERE idx = '$idx'";
	$result = $connect->query($sql);

	if ($result == true) {
		// 성공 결과를 반환합니다.
		$response = array(
			'result'    => "0",
			'message'   => "'저장' 되었습니다."
		);

	} else {
		// 실패 결과를 반환합니다.
		$response = array(
			'result'    => "1",
			'message'   => "'저정오류'가 발생하였습니다."
		);
	}

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
