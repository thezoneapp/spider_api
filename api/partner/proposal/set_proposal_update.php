<?
	// *********************************************************************************************************************************
	// *                                                제휴제안 등록                                                                    *
	// *********************************************************************************************************************************
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/kakaoTalk.php";

	/*
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

	$introduction = str_replace("'", "＇", $introduction);
	$content = str_replace("'", "＇", $content);

	if ($telNo != "") $telNo = aes128encrypt($telNo);
	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
	if ($email != "") $email = aes128encrypt($email);

	if ($mode == "insert") {
		$sql = "INSERT INTO proposal (companyName, postCode, addr1, addr2, telNo, hpNo, email, chargeName, introduction, content, companyDoc, wdate) 
							  VALUES ('$companyName', '$postCode', '$addr1', '$addr2', '$telNo', '$hpNo', '$email', '$chargeName', '$introduction', '$content', '$companyDoc', now())";
		$connect->query($sql);

		// 등록된 제휴서의 일련번호를 구한다.
		$sql = "SELECT idx FROM proposal WHERE companyName = '$companyName' ORDER BY idx DESC LIMIT 1";
		$result = $connect->query($sql);

		$row = mysqli_fetch_object($result);
		$idx = $row->idx;
	
		$custHpNo = preg_replace('/\D+/', '', $hpNo);
		$receiptInfo = array(
			"receiptHpNo" => $custHpNo,
		);
		sendTalk("spider", "P_01_01", $receiptInfo);

		$result_status = "0";
		$result_message = "접수되었습니다.";

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
									companyDoc = '$companyDoc' 
					WHERE idx = '$idx'";
		$connect->query($sql);

		$result_status = "0";
		$result_message = "수정되었습니다.";
	}

	$response = array(
		'result'   => $result_status,
		'message'  => $result_message,
		'idx'      => $idx,
    );

	//print_r($response);
    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>