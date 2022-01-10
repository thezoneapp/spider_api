<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자페이지 > 사업자관리 > 목록 > 상세페이지 > 수정
	* parameter 
		mode:      insert(추가), update(수정)
		idx:       수정할 레코드 id
		corpName:  상호
		corpNum:   사업자번호
		ceoName:   대표자명
		juminNum:  주민번호
		staffName: 담당자명
		bizType:   업태
		bizClass:  업종
		addr1:     기본주소
		addr2:     상세주소
		postNum:   우편번호
		hpNo:      휴대폰번호
		email:     이메일
		baroId:    바로빌ID
	*/

	$input_data = json_decode(file_get_contents('php://input'));
	$mode      = $input_data->{'mode'};
	$idx       = $input_data->{'idx'};
	$corpName  = $input_data->{'corpName'};
	$corpNum   = $input_data->{'corpNum'};
	$ceoName   = $input_data->{'ceoName'};
	$juminNum  = $input_data->{'juminNum'};
	$staffName = $input_data->{'staffName'};
	$bizType   = $input_data->{'bizType'};
	$bizClass  = $input_data->{'bizClass'};
	$addr1     = $input_data->{'addr1'};
	$addr2     = $input_data->{'addr2'};
	$postNum   = $input_data->{'postNum'};
	$hpNo      = $input_data->{'hpNo'};
	$email     = $input_data->{'email'};
	$baroId    = $input_data->{'baroId'};

	if ($juminNum != "") $juminNum = aes128encrypt($juminNum);
	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
	if ($email != "") $email = aes128encrypt($email);

	$sql = "UPDATE tax_member SET corpName = '$corpName',
								  corpNum = '$corpNum',
								  ceoName = '$ceoName',
								  juminNum = '$juminNum',
								  staffName = '$staffName',
								  bizType = '$bizType',
								  bizClass = '$bizClass',
								  addr1 = '$addr1',
								  addr2 = '$addr2',
								  postNum = '$postNum',
								  hpNo = '$hpNo',
								  email = '$email',
								  baroId = '$baroId' 
				WHERE idx = '$idx'";
	$connect->query($sql);

	// 성공 결과를 반환합니다.
	$result_statue = "0";
	$result_message = "변경하였습니다.";

	$response = array(
		'result'    => $result_statue,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>