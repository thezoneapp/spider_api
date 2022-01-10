<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 게시글 추가/수정
	* parameter 
		companyName:    회사명
		postCode:       우편번호
		addr1:          기본주소
		addr2:			상세주소
		telNo:          회사-대표번호
		hpNo:			담당자-휴대폰번호
		email:			담당자-이메일
		chargeName:     담당자-이름
		introduction:	회사소개
		content:		제휴내용
		companyDoc:		회사소개파일
		answerYn:       답변여부
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$mode         = $input_data->{'mode'};
	$idx          = $input_data->{'idx'};
	$companyName  = $input_data->{'companyName'};
	$postCode     = $input_data->{'postCode'};
	$addr1        = $input_data->{'addr1'};
	$addr2        = $input_data->{'addr2'};
	$telNo        = $input_data->{'telNo'};
	$hpNo         = $input_data->{'hpNo'};
	$email        = $input_data->{'email'};
	$chargeName   = $input_data->{'chargeName'};
	$introduction = $input_data->{'introduction'};
	$content      = $input_data->{'content'};
	$companyDoc   = $input_data->{'companyDoc'};
	$answerYn     = $input_data->{'answerYn'};
	$adminMemo    = $input_data->{'adminMemo'};

	$introduction = str_replace("'", "＇", $introduction);
	$content = str_replace("'", "＇", $content);
	$adminMemo = str_replace("'", "＇", $adminMemo);

	if ($telNo != "") $telNo = aes128encrypt($telNo);
	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
	if ($email != "") $email = aes128encrypt($email);

	if ($mode == "insert") {
		$sql = "INSERT INTO proposal (companyName, postCode, addr1, addr2, telNo, hpNo, email, chargeName, introduction, content, companyDoc, answerYn, adminMemo, wdate) 
							  VALUES ('$companyName', '$postCode', '$addr1', '$addr2', '$telNo', '$hpNo', '$email', '$chargeName', '$introduction', '$content', '$companyDoc', '$answerYn', '$adminMemo', now())";
		$connect->query($sql);

		$result = "0";
		$result_message = "등록하였습니다.";

	} else {
		$sql = "UPDATE proposal SET companyName = '$companyName', 
		                            postCode = '$postCode', 
									addr1 = '$addr1', 
									addr2 = '$addr2', 
									telNo = '$telNo', 
									hpNo = '$hpNo', 
									email = '$email', 
									chargeName = '$chargeName', 
									introduction = '$introduction', 
									content = '$content', 
									companyDoc = '$companyDoc', 
									answerYn = '$answerYn', 
									adminMemo = '$adminMemo' 
					WHERE idx = '$idx'";
		$connect->query($sql);

		// 성공 결과를 반환합니다.
		$result = "0";
		$result_message = "변경하였습니다.";
	}

	$response = array(
		'result'    => $result,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>