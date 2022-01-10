<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
	include "../../inc/barobill.php";
	include './BaroService_TI.php';

	/*
	* 바로빌 > 회원사 등록
	* parameter :
		memId:      회원ID
		corpAssort: 사업자구분(G: 개인, L: 법인)
		corpNum:    바로빌 회원 사업자번호 ('-' 제외, 10자리)
		corpName:   회사명
		ceoName:    대표자명
		juminNum:   주민등록번호 ('-' 제외, 13자리)
		bizType:    업태
		bizClass:   업종
		postNum:    우편번호
		addr1:      주소1 (ex. 서울특별시 양천구 목1동)
		addr2:      주소2 (ex. SBS방송센터 920)
		staffName:  담당자 성명
		grade:      직급
		telNo:      전화번호
		hpNo:       휴대폰번호
		email:      이메일
		taxDoc:     사업자등록증사본
	*/

	$data_back = json_decode(file_get_contents('php://input'));
	$memId      = $data_back->{'memId'};
	//$corpAssort = $data_back->{'corpAssort'};
	$corpNum    = $data_back->{'corpNum'};
	$corpName   = $data_back->{'corpName'};
	$ceoName    = $data_back->{'ceoName'};
	$bizType    = $data_back->{'bizType'};
	$bizClass   = $data_back->{'bizClass'};
	$postNum    = $data_back->{'postNum'};
	$addr1      = $data_back->{'addr1'};
	$addr2      = $data_back->{'addr2'};
	//$staffName  = $data_back->{'staffName'};
	$juminNum   = $data_back->{'juminNum'};
	//$telNo      = $data_back->{'telNo'};
	//$hpNo       = $data_back->{'hpNo'};
	$email      = $data_back->{'email'};
	//$taxDoc     = $data_back->{'taxDoc'};

	//$memId = 'a92854323';
	//$corpNum = '847-45-00619';
	//$corpName = '펜서모바일';
	//$ceoName = '이병우';
	//$bizType = '도매및소매업';
	//$bizClass = '통신기기소매업';
	//$postNum = '13636';
	//$addr1 = '경기 성남시 분당구 탄천상로151번길20';
	//$addr2 = 'C동 B01층 11호(핸드폰나라)';
	//$staffName = '박태수';
	//$juminNum = '980402-1830713';
	//$grade = '';
	//$telNo = '0313139222';
	//$hpNo = '010-2723-3377';
	//$email = 'pre24418@naver.com';

	$staffName = $ceoName;
    $corpNum = str_replace("-", "", $corpNum);

	// 회원 정보
    $sql = "SELECT memPw, hpNo FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$baroId = $memId;
		$baroPw = aes_decode($row->memPw);
		$hpNo = aes_decode($row->hpNo);
		$telNo = $hpNo;
	}

	$params = array(
		'corpNum'		=> $corpNum,
		'corpName'		=> $corpName,
		'ceoName'		=> $ceoName,
		'bizType'		=> $bizType,
		'bizClass'		=> $bizClass,
		'postNum'		=> $postNum,
		'addr1'			=> $addr1,
		'addr2'			=> $addr2,
		'memberName'	=> $staffName,
		'juminNum'		=> $juminNum,
		'baroId'		=> $baroId,
		'baroPw'		=> $baroPw,
		'grade'			=> $grade,
		'telNo'			=> $telNo,
		'hpNo'			=> $hpNo,
		'email'			=> $email
	);

	// 바로빌 api호출
	$resultCode = registCorp($params);

	if ($resultCode == "1" || $resultCode == "-32000") {
		if ($baroPw != "") $baroPw = aes128encrypt($baroPw);
		if ($juminNum != "") $juminNum = aes128encrypt($juminNum);
		if ($telNo != "") $telNo = aes128encrypt($telNo);
		if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
		if ($email != "") $email = aes128encrypt($email);

		if ($resultCode == "-32000") {
			$sql = "SELECT idx FROM tax_member WHERE memid = '$memId'";
			$result = $connect->query($sql);

			if ($result->num_rows == 0) $insert = true;
			else $insert = false;

		} else {
			$insert = true;
		}

		if ($insert) {
			// 바로빌 사업자회원 테이블에 저장
			$sql = "INSERT INTO tax_member (memId, baroId, baroPw, corpNum, corpName, ceoName, juminNum,
											bizType, bizClass, postNum, addr1, addr2, staffName, grade, telNo, hpNo, email, wdate) 
									VALUES ('$memId', '$baroId', '$baroPw', '$corpNum', '$corpName', '$ceoName', '$juminNum',
											'$bizType', '$bizClass', '$postNum', '$addr1', '$addr2', '$staffName', '$grade', '$telNo', '$hpNo', '$email', now())";
			$connect->query($sql);

			// 회원정보 테이블에 사업자구분 = 사업자
			$sql = "UPDATE member SET businessAssort = 'T', taxAssort = 'T' WHERE memId = '$memId'";
			$connect->query($sql);
		}

		if ($resultCode == "1") {
			$result_status = "0";
			$result_message = "등록이 완료되었습니다.";

		} else {
			$result_status = "1";
			$result_message = "이미 가입된 사업자입니다.\n관리자에게 문의하세요.";
		}

	} else {
		// 바로빌 에러 코드 정보
		$errorCode = str_replace("-", "", $resultCode);
		$sql = "SELECT errorMessage FROM error_code WHERE errorCode = '$errorCode'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);
			$errorMessage = $row->errorMessage;

		} else {
			$errorMessage = $resultCode;
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
