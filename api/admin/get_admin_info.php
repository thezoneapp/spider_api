<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 관리자 정보
	* parameter ==> idx: 아이디에 해당하는 idx
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx        = $input_data->{'idx'};

    $sql = "SELECT idx, id, passwd, name, phone, email, auth, use_yn FROM admin WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$authName = selected_object($row->auth, $arrAdminAuth);

		if ($row->phone == null) $row->phone = "";
		if ($row->email == null) $row->email = "";

		if ($row->passwd !== "") $row->passwd = aes_decode($row->passwd);
		if ($row->phone !== "") $row->phone = aes_decode($row->phone);
		if ($row->email !== "") $row->email = aes_decode($row->email);

		$data = array(
			'idx'          => $row->idx,
			'adminId'      => $row->id,
			'adminPw'      => $row->passwd,
			'adminName'    => $row->name,
			'phone'        => $row->phone,
			'email'        => $row->email,
			'auth'         => $row->auth,
			'authName'     => $authName,
			'authOptions'  => $arrAdminAuth,
			'useYn'        => $row->use_yn,
			'useYnOptions' => $arrUseYn2,
		);

		// 업데이트모드로 결과를 반환합니다.
		$result = "0";

    } else {
		$data = array(
			'idx'          => '',
			'adminId'      => '',
			'adminPw'      => '',
			'adminName'    => '',
			'phone'        => '',
			'email'        => '',
			'auth'         => '',
			'authName'     => '',
			'authOptions'  => $arrAdminAuth,
			'useYn'        => '',
			'useYnOptions' => $arrUseYn2,
		);

		// 추가모드로 결과를 반환합니다.
		$result = "1";
	}

	$response = array(
		'error' => $result,
		'data'  => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>