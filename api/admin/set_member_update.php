<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 관리자 추가/수정
	* parameter ==> mode:           insert(추가), update(수정)
	* parameter ==> idx:            수정할 레코드 id
	* parameter ==> recommandId:    추천인 아이디
	* parameter ==> memName:        이름
	* parameter ==> memPw:          비밀번호
	* parameter ==> hpNo:           휴대폰번호
	* parameter ==> email:          이메일
	* parameter ==> gajaId:         가자렌탈ID
	* parameter ==> registNo:       주민번호
	* parameter ==> accountName:    예금주
	* parameter ==> accountNo:      계좌번호
	* parameter ==> accountBank:    은행명
	* parameter ==> comment:        관리자메모

	* UPDATE member SET leg = 1, sponsId = 'dream', recommandId = 'dream', memStatus = '9' WHERE idx = 1
	*/

	$input_data  = json_decode(file_get_contents('php://input'));
	$idx         = $input_data->{'idx'};
	$recommandId = $input_data->{'recommandId'};
	$memName     = $input_data->{'memName'};
	$memPw       = $input_data->{'memPw'};
	$hpNo        = $input_data->{'hpNo'};
	$email       = $input_data->{'email'};
	$gajaId      = $input_data->{'gajaId'};
	$registNo    = $input_data->{'registNo'};
	$accountName = $input_data->{'accountName'};
	$accountNo   = $input_data->{'accountNo'};
	$accountBank = $input_data->{'accountBank'};
	$comment     = $input_data->{'comment'};

	if ($memPw != "") $memPw = aes128encrypt($memPw);
	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
	if ($email != "") $email = aes128encrypt($email);
	if ($registNo != "") $registNo = aes128encrypt($registNo);
	if ($accountNo != "") $accountNo = aes128encrypt($accountNo);

	$sql = "UPDATE member SET recommandId  = '$recommandId', 
							  memName      = '$memName', 
							  memPw        = '$memPw', 
							  hpNo         = '$hpNo', 
							  email        = '$email', 
							  gajaId       = '$gajaId', 
							  registNo     = '$registNo', 
							  accountName  = '$accountName', 
							  accountNo    = '$accountNo', 
							  accountBank  = '$accountBank', 
							  comment      = '$comment'
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

	// 성공 결과를 반환합니다.
	$result = "0";
	$result_message = "변경하였습니다.";

	$response = array(
		'result'    => $result,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>