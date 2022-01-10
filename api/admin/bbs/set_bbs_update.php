<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 게시글 추가/수정
	* parameter ==> mode:        insert(추가), update(수정)
	* parameter ==> bbsCode:     게시판코드
	* parameter ==> assortCode:  대분류코드
	* parameter ==> assort2Code: 소분류코드
	* parameter ==> idx:         게시글 idx
	* parameter ==> userId:      작성자ID
	* parameter ==> userName:    작성자명
	* parameter ==> hpNo:        휴대폰번호
	* parameter ==> email:       이메일
	* parameter ==> subject:     제목
	* parameter ==> content:     내용
	* parameter ==> thumbnail:   썸네일파일명
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$mode        = $input_data->{'mode'};
	$idx         = $input_data->{'idx'};
	$bbsCode     = $input_data->{'bbsCode'};
	$assortCode  = $input_data->{'assortCode'};
	$assort2Code = $input_data->{'assort2Code'};
	$userId      = $input_data->{'userId'};
	$userName    = $input_data->{'userName'};
	$hpNo        = $input_data->{'hpNo'};
	$email       = $input_data->{'email'};
	$subject     = $input_data->{'subject'};
	$content     = $input_data->{'content'};
	$thumbnail   = $input_data->{'thumbnail'};

	$bbsCode     = $bbsCode->{'code'};
	//$assortCode  = $assortCode->{'code'};
	//$assort2Code = $assort2Code->{'code'};

	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
	if ($email != "") $email = aes128encrypt($email);

	$subject = str_replace("'", "＇", $subject);
	$content = str_replace("'", "＇", $content);

	if ($mode == "insert") {
		$sql = "INSERT INTO bbs (bbsCode, assortCode, assort2Code, memId, memName, hpNo, email, subject, content, thumbnail, wdate)
		                 VALUES ('$bbsCode', '$assortCode', '$assort2Code', '$userId', '$userName', '$hpNo', '$email', '$subject', '$content', '$thumbnail', now())";
		$connect->query($sql);

		// 1:1문의글이면 SMS

		$result = "0";
		$result_message = "등록하였습니다.";

	} else {
		$sql = "UPDATE bbs SET assortCode = '$assortCode',
		                       assort2Code = '$assort2Code', 
							   subject = '$subject', 
							   content = '$content', 
							   thumbnail = '$thumbnail'
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