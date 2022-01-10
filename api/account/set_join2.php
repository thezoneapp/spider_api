<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/memberStatusUpdate.php";
	include "../../inc/utility.php";
	include "../../inc/kakaoTalk.php";

	/*
	* 회원 가입
	* parameter
		recommendId:    추천인 아이디
		organizeCode:   회원구성코드
		memName:        이름
		hpNo:           휴대폰번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$recommendId    = trim($input_data->{'recommendId'});
	$organizeCode   = $input_data->{'organizeCode'};
	$memName        = trim($input_data->{'memName'});
	$hpNo           = $input_data->{'hpNo'};

	//$recommendId    = "a11111111";
	//$organizeCode   = "13";
	//$memName        = "이해영";
	//$hpNo           = "010-5564-5136";

	// 휴대폰 번호를 이용한 ID 및 비밀번호 생성
	$arrHpNo = explode("-", $hpNo);
	$memId   = "a" . trim($arrHpNo[1]) . trim($arrHpNo[2]);
	$memPw   = $memId . "!";

	/* ****************************************************************************************
	* 1. 동일한 회원 아이디가 있나 체크
	***************************************************************************************** */
	$sql = "SELECT memId
			FROM ( select id as memId from admin
				   union 
				   select memId from member 
				 ) m 
			WHERE memId = '$memId'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		$result_status  = "0";
		$result_message = "정상";

	} else {
		$result_status  = "1";
		$result_message = "이미 존재하는 '휴대폰번호'입니다.";
	}

	// 1-1. 추천인 > 소속그룹 정보 조회
	if ($result_status == "0") {
		$sql = "SELECT groupCode FROM member WHERE memAssort = 'M' and memStatus = '9' and memId = '$recommendId'";
		$result = $connect->query($sql);

		if ($result->num_rows == 1) {
			$row = mysqli_fetch_object($result);
			$groupCode = $row->groupCode;

			$result_status  = "0";
			$result_message = "정상";

		} else {
			// 실패 결과를 반환합니다.
			$result_status  = "1";
			$result_message = "존재하지 않거나 승인되지 않은 '추천인 아이디'입니다.";
		}
	}

	// 아이디와 추천인 정보가 정상이면...
	if ($result_status == "0") {
		/* ************************************************************************************
		* 2. 회원정보 등록
		************************************************************************************* */
		if ($memPw != "") $memPw = aes128encrypt($memPw);
		if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
		
		$memStatus      = "0"; // 회원상태: 접수중
		$agreeStatus    = "0"; // 동의상태: 접수중
		$payType        = "C"; // 납부방식: 이용수수료
		$cmsStatus      = "0"; // CMS상태: 접수중

		// 가입비 납부상태
		if ($memAssort == "M") $joinPayStatus = "1";
		else $joinPayStatus = "0";

		$sql = "INSERT INTO member (groupCode, organizeCode, recommendId, memId, memName, memPw, memAssort, hpNo, payType, agreeStatus, cmsStatus, joinPayStatus, memStatus, firstLogin, cmsId, gajaId, wdate)
						    VALUES ('$groupCode', '$organizeCode', '$recommendId', '$memId', '$memName', '$memPw', '$memAssort', '$hpNo', '$payType', '$agreeStatus', '$cmsStatus', '$joinPayStatus', '$memStatus', '0', '$memId', '$memId', now())";
		$connect->query($sql);

		/* ************************************************************************************
		* 3. 회원 LOSMAP 구성
		************************************************************************************* */
		$sql = "SELECT idx FROM member WHERE memId = '$memId'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		memberApproval($row->idx, "9");
	}

	/* ************************************************************************************
	* 4. 최종결과 리턴
	************************************************************************************* */
	$response = array(
		'memId'   => $memId,
		'result'  => $result_status,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>