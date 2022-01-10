<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 내정보 > 사업자전환 신청
	* parameter ==> userId:      회원ID
	* parameter ==> companyName: 상호
	* parameter ==> ownerName:   대표자명
	* parameter ==> taxNo:       사업자번호
	* parameter ==> email:       이메일
	* parameter ==> taxDoc:      사업자등록증
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId      = $input_data->{'userId'};
    $userName    = $input_data->{'userName'};
    $hpNo        = $input_data->{'hpNo'};
	$email       = $input_data->{'email'};
	$gajaId      = $input_data->{'gajaId'};
	$registNo    = $input_data->{'registNo'};
	$accountName = $input_data->{'accountName'};
	$accountNo   = $input_data->{'accountNo'};
	$accountBank = $input_data->{'accountBank'};

	if ($userPw != "") $userPw = aes128encrypt($userPw);
	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
	if ($email != "") $email = aes128encrypt($email);
	if ($registNo != "") $registNo = aes128encrypt($registNo);
	if ($accountNo != "") $accountNo = aes128encrypt($accountNo);

	$sql = "UPDATE member SET memPw = '$userPw', 
							  memName = '$userName', 
							  hpNo = '$hpNo', 
							  email = '$email',
							  gajaId = '$gajaId',
							  registNo     = '$registNo', 
							  accountName  = '$accountName', 
							  accountNo    = '$accountNo', 
							  accountBank  = '$accountBank' 
			WHERE memId = '$userId'";
	$result = $connect->query($sql);

	// 성공 결과를 반환합니다.
	$result = "0";
	$result_message = "변경하였습니다.";

	$response = array(
		'result'    => $result,
		'message'   => $result_message,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>