<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 내정보 수정
	* parameter ==> userId:         아이디
	* parameter ==> userPw:         비밀번호
	* parameter ==> userName:       이름
	* parameter ==> hpNo:           휴대폰번호
	* parameter ==> email:          이메일
	* parameter ==> gajaId:         가자렌탈ID
	* parameter ==> registNo:       주민번호
	* parameter ==> accountName:    예금주
	* parameter ==> accountNo:      계좌번호
	* parameter ==> accountBank:    은행명
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId   = $input_data->{'userId'};
	$userPw   = $input_data->{'userPw'};
    $userName = $input_data->{'userName'};
    $hpNo     = $input_data->{'hpNo'};
	$email    = $input_data->{'email'};
	$gajaId   = $input_data->{'gajaId'};
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