<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 내정보 정보
	* parameter ==> userId: 아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId     = $input_data->{'userId'};

	$data = array();
    $sql = "SELECT passwd, name, phone, email FROM admin WHERE id = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->phone == null) $row->phone = "";
		if ($row->email == null) $row->email = "";

		if ($row->passwd !== "") $row->passwd = aes_decode($row->passwd);
		if ($row->phone !== "") $row->phone = aes_decode($row->phone);
		if ($row->email !== "") $row->email = aes_decode($row->email);

		$data = array(
			'userPw'   => $row->passwd,
			'userName' => $row->name,
			'phone'    => $row->phone,
			'email'    => $row->email,
		);

		$result = "0";

    } else {
		$result = "1";
	}

	$response = array(
		'result' => $result,
		'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>