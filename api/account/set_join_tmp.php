<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* 회원가입
	* parameter ==> memAssort:    회원구분(A: MD, S:판매점)
	* parameter ==> memName:      이름
	* parameter ==> hpNo:         휴대폰번호
	* parameter ==> recommandId:  추천인 아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$memAssort   = $input_data->{'memAssort'};
	$memName     = trim($input_data->{'memName'});
	$hpNo        = $input_data->{'hpNo'};
	$recommandId = trim($input_data->{'recommandId'});

	$arrHpNo = explode("-", $hpNo);
	$memId = "a" . trim($arrHpNo[1]) . trim($arrHpNo[2]);
	$memPw = $memId . "!";

	if ($memPw != "") $memPw = aes128encrypt($memPw);
	if ($hpNo != "") $hpNo = aes128encrypt($hpNo);

	// 1. 동일한 회원 아이디가 있나 체크
	$sql = "SELECT memId
			FROM ( select id as memId from admin
				   union 
				   select memId from member 
				 ) m 
			WHERE memId = '$memId'";
	$result = $connect->query($sql);

	if ($result->num_rows == 0) {
		// 2. 추천인 아이디가 있나 체크
		$sql = "SELECT recommandId FROM member WHERE memAssort = 'A' and memStatus = '9' and memId = '$recommandId'";
		$result = $connect->query($sql);

		if ($result->num_rows === 1) {
			if ($memAssort == "A") $joinPayStatus = "1";
			else $joinPayStatus = "0";

			$cmsStatus = "0";
			$contractStatus = "0";
			$memStatus = "0";
			$sql = "INSERT INTO member (recommandId, memId, memName, memPw, memAssort, hpNo, cmsStatus, contractStatus, joinPayStatus, memStatus, cmsId, gajaId, wdate)
							    VALUES ('$recommandId', '$memId', '$memName', '$memPw', '$memAssort', '$hpNo', '$cmsStatus', '$contractStatus', '$joinPayStatus', '$memStatus', '$memId', '$memId', now())";
			$result = $connect->query($sql);

			$result = "0";
			$result_message = "이어서 '가맹점계약'이 진행됩니다.";

		} else {
			// 실패 결과를 반환합니다.
			$result = "1";
			$result_message = "존재 또는 승인되지 않은 추천인 아이디입니다.";
		}

	} else {
		// 실패 결과를 반환합니다.
		$result = "1";
		$result_message = "이미 존재하는 '휴대폰번호'입니다.";
	}

	$response = array(
		'result'  => $result,
		'memId'   => $memId,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>