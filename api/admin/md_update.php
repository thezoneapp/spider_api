<?
	include "../../../inc/common.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 추가/수정
	* parameter ==> mode:         insert(추가), update(수정)
	* parameter ==> idx:          수정할 레코드 id
	* parameter ==> recommandId:  추천인 아이디
	* parameter ==> memId:     대리점 아이디
	* parameter ==> memName:   이름
	* parameter ==> memPw:     비밀번호
	* parameter ==> memAssort: 대리점 구분
	* parameter ==> registNo:     주민등록번호
	* parameter ==> registDoc:    주민등록증사본
	* parameter ==> telNo:        일반전화번호
	* parameter ==> hpNo:         휴대폰번호
	* parameter ==> email:        이메일
	* parameter ==> taxName:      상호
	* parameter ==> taxNo:        사업자번호
	* parameter ==> taxKind:      업종
	* parameter ==> taxClass:     업태
	* parameter ==> taxAddress:   사업장주소
	* parameter ==> taxAssort:    과세구분
	* parameter ==> taxDoc:       사업장 주소
	* parameter ==> cmsAccName:   CMS 예금주
	* parameter ==> cmsAccNo:     CMS 계좌번호
	* parameter ==> cmsBankName:  CMS 은행명
	* parameter ==> cmsDoc:       CMS 신청서
	* parameter ==> cmsStatus:    CMS 상태
	* parameter ==> contractStatus: 계약상태 상태
	* parameter ==> memStatus:   대리점 상태

	* UPDATE member SET leg = 1, sponsId = 'dream', recommandId = 'dream', memStatus = '9' WHERE idx = 1
	*/

	$error = "0";

	// 주민등록증 사본
	if ($_FILES['registDoc'][name] !== '') {
		$response = fileUpload('../../../upload/doc/', 'registDoc');

		if ($response['result'] === '0') $registDoc = $response['message'];
		else $error = "1";
	}

	// 사업자등록증 사본
	if ($_FILES['taxDoc'][name] !== '') {
		$response = fileUpload('../../../upload/doc/', 'taxDoc');

		if ($response['result'] === '0') $taxDoc = $response['message'];
		else $error = "1";
	}

	// CMS신청서 사본
	if ($_FILES['cmsDoc'][name] !== '') {
		$response = fileUpload('../../../upload/doc/', 'cmsDoc');

		if ($response['result'] === '0') $cmsDoc = $response['message'];
		else $error = "1";
	}

	if ($error === "0") {
		$mode         = $_POST['mode'];
		$idx          = $_POST['idx'];
		$recommandId  = $_POST['recommandId'];
		$memId        = $_POST['memId'];
		$memName      = $_POST['memName'];
		$memPw        = $_POST['memPw'];
		$memAssort    = $_POST['memAssort'];
		$registNo     = $_POST['registNo'];
		$registDocOld = $_POST['registDocOld'];
		$telNo        = $_POST['telNo'];
		$hpNo         = $_POST['hpNo'];
		$email        = $_POST['email'];
		$address      = $_POST['address'];
		$taxName      = $_POST['taxName'];
		$taxNo        = $_POST['taxNo'];
		$taxKind      = $_POST['taxKind'];
		$taxClass     = $_POST['taxClass'];
		$taxAssort    = $_POST['taxAssort'];
		$taxDocOld    = $_POST['taxDocOld'];
		$cmsAccName   = $_POST['cmsAccName'];
		$cmsAccNo     = $_POST['cmsAccNo'];
		$cmsBankName  = $_POST['cmsBankName'];
		$cmsStatus    = $_POST['cmsStatus'];
		$cmsDocOld    = $_POST['cmsDocOld'];

		if ($memPw != "") $memPw = aes128encrypt($memPw);
		if ($registNo != "") $registNo = aes128encrypt($registNo);
		if ($telNo != "") $telNo = aes128encrypt($telNo);
		if ($hpNo != "") $hpNo = aes128encrypt($hpNo);
		if ($email != "") $email = aes128encrypt($email);
		if ($cmsAccNo != "") $cmsAccNo = aes128encrypt($cmsAccNo);

		if ($mode == "insert") {
			// 동일한 대리점 아이디가 있나 체크
			$sql = "SELECT memId
					FROM ( select id as memId from admin
						   union 
						   select memId from member 
						 ) member 
					WHERE memId = '$memId'";
			$result = $connect->query($sql);

			if ($result->num_rows == 0) {
				// 스폰서 아이디가 있나 체크
				$sql = "SELECT sponsId FROM member WHERE memAssort = 'A' and memId = '$recommandId'";
				$result = $connect->query($sql);

				if ($result->num_rows === 1) {
					$cmsStatus = "0";
					$contractStatus = "0";
					$memStatus = "0";
					$sql = "INSERT INTO member (recommandId, memId, memName, memPw, memAssort, registNo, registDoc, telNo, hpNo, email, address, 
												taxName, taxNo, taxKind, taxClass, taxAssort, taxDoc,
												cmsAccName, cmsAccNo, cmsBankName, cmsDoc, cmsStatus, contractStatus, memStatus, wdate)
									   VALUES ('$recommandId', '$memId', '$memName', '$memPw', '$memAssort', '$registNo', '$registDoc', '$telNo', '$hpNo', '$email', '$address', 
											   '$taxName', '$taxNo', '$taxKind', '$taxClass', '$taxAssort', '$taxDoc', 
											   '$cmsAccName', '$cmsAccNo', '$cmsBankName', '$cmsDoc', '$cmsStatus', '$contractStatus', '$memStatus', now())";
					$result = $connect->query($sql);

					// 성공 결과를 반환합니다.
					$result = "0";
					$result_message = "신청서가 접수되었습니다.";
				
				} else {
					// 실패 결과를 반환합니다.
					$result = "1";
					$result_message = "스폰서 아이디가 존재하지 않습니다.";
				}

			} else {
				// 실패 결과를 반환합니다.
				$result = "1";
				$result_message = "이미 존재하는 아이디입니다.";
			}

		} else {
			if ($registDoc == "" && $registDocOld != "") $registDoc = $registDocOld;
			if ($taxDoc == "" && $taxDocOld != "") $taxDoc = $taxDocOld;
			if ($cmsDoc == "" && $cmsDocOld != "") $cmsDoc = $cmsDocOld;

			$sql = "UPDATE member SET recommandId  = '$recommandId', 
									  memId        = '$memId', 
									  memName      = '$memName', 
									  memPw        = '$memPw', 
									  memAssort    = '$memAssort', 
									  registNo     = '$registNo', 
									  registDoc    = '$registDoc', 
									  telNo        = '$telNo', 
									  hpNo         = '$hpNo', 
									  email        = '$email', 
									  address      = '$address', 
									  taxName      = '$taxName', 
									  taxNo        = '$taxNo', 
									  taxKind      = '$taxKind', 
									  taxClass     = '$taxClass', 
									  taxAssort    = '$taxAssort', 
									  taxDoc       = '$taxDoc', 
									  cmsAccNo     = '$cmsAccNo', 
									  cmsAccName   = '$cmsAccName', 
									  cmsBankName  = '$cmsBankName', 
									  cmsDoc       = '$cmsDoc'
							WHERE idx = '$idx'";
			$result = $connect->query($sql);

			// 성공 결과를 반환합니다.
			$result = "0";
			$result_message = "변경하였습니다.";
		}

		$response = array(
			'result'    => $result,
			'message'   => $result_message
		);
	}

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>