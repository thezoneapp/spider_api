<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 내정보 > 사업자전환신청 > 사업자정보
	* parameter ==> memId: 회원아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memId      = $input_data->{'memId'};
	
	//$memId = "a51907770";

	// 회원정보 검색
	$sql = "SELECT corpNum, corpName, ceoName, juminNum, bizType, bizClass, postNum, addr1, addr2, 
	               staffName, grade, telNo, hpNo, email, taxDoc, certifyStatus 
	        FROM tax_member 
	        WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->juminNum !== "") $row->juminNum = aes_decode($row->juminNum);
		if ($row->telNo !== "") $row->telNo = aes_decode($row->telNo);
		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);
		if ($row->email !== "") $row->email = aes_decode($row->email);

		$data = array(
			'corpNum'       => $row->corpNum,
			'corpName'      => $row->corpName,
			'ceoName'       => $row->ceoName,
			'juminNum'      => $row->juminNum,
			'bizType'       => $row->bizType,
			'bizClass'      => $row->bizClass,
			'postNum'       => $row->postNum,
			'addr1'         => $row->addr1,
			'addr2'         => $row->addr2,
			'staffName'     => $row->staffName,
			'grade'         => $row->grade,
			'telNo'         => $row->telNo,
			'hpNo'          => $row->hpNo,
			'email'         => $row->email,
			'taxDoc'        => $row->taxDoc,
			'certifyStatus' => $row->certifyStatus
		);

		$result_status  = "0";
		$result_message = "정상";

	} else {
		$data = array();
		$result_status = "1";
		$result_message = "등록되어 있지 않는 사업자입니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
		'data'    => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>