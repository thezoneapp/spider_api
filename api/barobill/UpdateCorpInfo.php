<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/barobill.php";
	include './BaroService_TI.php';

	/*
	* 바로빌 > 회원사 정보 수정
	* parameter :
		memId:      회원ID
		corpNum:    바로빌 회원 사업자번호 ('-' 제외, 10자리)
		corpName:   회사명
		ceoName:    대표자명
		bizType:    업태
		bizClass:   업종
		postNum:    우편번호
		addr1:      주소1 (ex. 서울특별시 양천구 목1동)
		addr2:      주소2 (ex. SBS방송센터 920)
		email:       이메일
	*/

	$data_back = json_decode(file_get_contents('php://input'));
	$memId      = $data_back->{'memId'};
	$corpNum    = $data_back->{'corpNum'};
	$corpName   = $data_back->{'corpName'};
	$ceoName    = $data_back->{'ceoName'};
	$bizType    = $data_back->{'bizType'};
	$bizClass   = $data_back->{'bizClass'};
	$postNum    = $data_back->{'postNum'};
	$addr1      = $data_back->{'addr1'};
	$addr2      = $data_back->{'addr2'};
	$email      = $data_back->{'email'};

	//$memId = 'a51607340';
	//$corpNum = '1168118750';
	//$corpName = '더존공인중개사사무소';
	//$ceoName = '박태수';
	//$bizType = '부동산업';
	//$bizClass = '부동산중개,컨설팅';
	//$postNum = '13480';
	//$addr1 = '경기도시흥시 매화로 97';
	//$addr2 = '제일빌딩102호';

    $corpNum = str_replace("-", "", $corpNum);

	if ($email != "") $email = aes128encrypt($email);

	$params = array(
		'corpNum'		=> $corpNum,
		'corpName'		=> $corpName,
		'ceoName'		=> $ceoName,
		'bizType'		=> $bizType,
		'bizClass'		=> $bizClass,
		'postNum'		=> $postNum,
		'addr1'			=> $addr1,
		'addr2'			=> $addr2
	);

	// 바로빌 api호출
	$resultCode = updateCorp($params);

	if ($resultCode == "1") {
		// 바로빌 사업자회원 테이블에 저장
		$sql = "UPDATE tax_member SET corpNum = '$corpNum', 
		                              corpName = '$corpName', 
								      ceoName = '$ceoName', 
									  bizType = '$bizType', 
									  bizClass = '$bizClass', 
									  postNum = '$postNum', 
									  addr1 = '$addr1', 
									  addr2 = '$addr2', 
									  email = '$email' 
					WHERE memId = '$memId'";
		$connect->query($sql);

		$result_status = "0";
		$result_message = "변경이 완료되었습니다.";

	} else {
		// 바로빌 에러 코드 정보
		$errorCode = str_replace("-", "", $resultCode);
		$sql = "SELECT errorMessage FROM error_code WHERE errorCode = '$errorCode'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);
			$errorMessage = $row->errorMessage;
		}

		$result_status = "1";
		$result_message = $errorMessage;
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
