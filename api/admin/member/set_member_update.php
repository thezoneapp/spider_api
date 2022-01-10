<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 추가/수정
	* parameter
		mode:           insert(추가), update(수정)
		idx:            수정할 레코드 id
		recommandId:    추천인 아이디
		memName:        이름
		memPw:          비밀번호
		hpNo:           휴대폰번호
		email:          이메일
		cmsId:          CMSID
		gajaId:         가자렌탈ID
		conciergeId:    컨시어지ID
		registNo1:      주민번호
		registNo2:      주민번호
		registNo3:      주민번호
		accountName:    예금주
		accountNo:      계좌번호
		accountBank:    은행명
		comment:        관리자메모

	* UPDATE member SET leg = 1, sponsId = 'dream', recommandId = 'dream', memStatus = '9' WHERE idx = 1
	*/

	$input_data  = json_decode(file_get_contents('php://input'));
	$idx         = $input_data->{'idx'};
	$recommandId = $input_data->{'recommandId'};
	$memName     = $input_data->{'memName'};
	$memPw       = $input_data->{'memPw'};
	$hpNo        = $input_data->{'hpNo'};
	$email       = $input_data->{'email'};
	$cmsId       = $input_data->{'cmsId'};
	$gajaId      = $input_data->{'gajaId'};
	$conciergeId = $input_data->{'conciergeId'};
	$registNo1   = $input_data->{'registNo1'};
	$registNo2   = $input_data->{'registNo2'};
	$registNo3   = $input_data->{'registNo3'};
	$accountName = $input_data->{'accountName'};
	$accountNo   = $input_data->{'accountNo'};
	$accountBank = $input_data->{'accountBank'};
	$comment     = $input_data->{'comment'};

	$registNo = $registNo1 . "-". $registNo2 . $registNo3;

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
							  cmsId        = '$cmsId', 
							  gajaId       = '$gajaId', 
							  conciergeId  = '$conciergeId', 
							  registNo     = '$registNo', 
							  accountName  = '$accountName', 
							  accountNo    = '$accountNo', 
							  accountBank  = '$accountBank', 
							  comment      = '$comment'
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

	// 성공 결과를 반환합니다.
	$result_status = "0";
	$result_message = "변경하였습니다.";

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>