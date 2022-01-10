<?
	// *********************************************************************************************************************************
	// *                                                     회원가입 전송                                                               *
	// *********************************************************************************************************************************
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../utils/utility.php";
	include "./common.php";

	/*
	* parameter ==> memId: 회원 아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId = $input_data->{'memId'};
	//$memId = "a27233377";

	// 회원정보
	$sql = "SELECT memName, hpNo FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$memName = $row->memName;
		if ($row->hpNo !== "") $hpNo = aes_decode($row->hpNo);
	}

	// 토큰 취득
	$getToken = json_decode(get_token($authId, $passwd));
	print_r($getToken);
	//$access_token = $getToken->body->access_token;

	$response = array(
		'result'   => $result,
		'message'  => $message,
		'data'     => $play_url
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>