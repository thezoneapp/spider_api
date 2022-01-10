<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    
	/*
	* 프로필 사진 변경
	* parameter ==> userId: 회원ID
	* parameter ==> photo: 비밀번호
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$userId = trim($data_back->{'userId'});
	$photo  = trim($data_back->{'photo'});

	$sql = "UPDATE member SET photo = '$photo' WHERE memId = '$userId'";
	$connect->query($sql);

	// 결과를 반환합니다.
	$result_status = "0";
	$result_message = "변경완료";

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
