<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* ������ ����
	* parameter ==> userId: ���̵�
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

    // db connection �� �ݰų�, connection pool �� �̿����̶�� ����� ������ ��ȯ�մϴ�.
    @mysqli_close($connect);
?>