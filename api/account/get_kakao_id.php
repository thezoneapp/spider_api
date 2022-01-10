<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    
	/*
	* 관리자정보
	* parameter ==> user_id: 회원ID
	* parameter ==> user_pw: 회원PW
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$userId = trim($data_back->{'userId'});
	$userPw = trim($data_back->{'userPw'});

	//$userId = "a27233377";
	//$userPw = "a27233377!";

	if ($userPw != "") $userPw = aes128encrypt($userPw);

	// 실행할 쿼리를 작성합니다.
	$data = Array();
    $sql = "SELECT idx, id, name, passwd, auth, groupCode, organizeCode, payType, cmsStatus, clearStatus, memStatus, memAssort,
	               registNo, accountName, accountNo, accountBank, insuId, conciergeId, firstLogin  
			FROM ( SELECT idx, id, name, passwd, auth, '' AS groupCode, '' AS organizeCode, '' AS payType, '9' AS cmsStatus, '0' as clearStatus, if(use_yn = 'Y', '9', '8') AS memStatus, 'A' as memAssort,
			              '' as registNo, '' as accountName, '' as accountNo, '' as accountBank, '' as insuId, '' as conciergeId, '1' as firstLogin 
                   FROM admin
                   union 
                   SELECT idx, memId AS id, memName AS name, memPw AS passwd, memAssort AS auth, groupCode, organizeCode, payType, cmsStatus, clearStatus, memStatus, 'M' as memAssort, 
				          registNo, accountName, accountNo, accountBank, insuId, conciergeId, firstLogin 
                   FROM member
				 ) m 
			WHERE id = '$userId' and passwd = '$userPw'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$groupCode    = $row->groupCode;
		$organizeCode = $row->organizeCode;

		// 토큰값 생성
		if ($row->memAssort == "M") {
			$token = $row->idx . date("ymdHis");

			$sql = "UPDATE member SET token = '$token' WHERE memId = '$userId'";
			$connect->query($sql);
		}

		// 회원구성정보
		$sql = "SELECT organizeName, memAssort FROM group_organize WHERE groupCode = '$groupCode' and organizeCode = '$organizeCode'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);
			$organizeName = $row2->organizeName;

			if ($row2->memAssort == "M") $mdName = $row2->organizeName;
		}

		if ($row->memStatus == "8") {
			// 결과를 반환합니다.
			$result_status = "1";
			$result_message = "'사용중지'된 아이디입니다.";

		} else if ($row->memStatus == "2") {
			$result_status = "0";
			$infoStatus = "2";
			$result_message = "현재 회원님은 '승인보류' 상태입니다.";

		} else if ($row->memStatus == "9") {
			// 성공 결과를 반환합니다.
			$result_status = "0";

			if ($row->clearStatus != "0") {
				$infoStatus = "2";
				$result_message = "현재 회원님은 '구독료미납' 상태입니다.";

			} else if ($row->cmsStatus == "0") {
				$infoStatus = "3";
				$result_message = "'CMS 계약'이 체결되지 않았습니다.";

			} else if ($row->firstLogin != "1") {
				$infoStatus = "4";
				$result_message = "'비밀번호'를 변경해주세요.";

			} else {
				$infoStatus = "9";
				$result_message = "로그인에 성공하였습니다.";
			}

			$data = array(
				'userId'         => $row->id,
				'userName'       => $row->name,
				'userAuth'       => $row->auth,
				'groupCode'      => $row->groupCode,
				'organizeName'   => $organizeName,
				'payType'        => $row->payType,
				'cmsStatus'      => $row->cmsStatus,
				'memStatus'      => $row->memStatus,
				'memAssort'      => $row->memAssort,
				'insuId'         => $row->insuId,
				'conciergeId'    => $row->conciergeId,
				'firstLogin'     => $row->firstLogin,
				'infoStatus'     => $infoStatus,
				'token'          => $token
			);

			$sql = "UPDATE member SET firstLogin = '1' WHERE memId = '$userId'";
			$connect->query($sql);

		} else {
			$result_status = "1";
			$result_message = "현재 회원님은 '승인대기' 상태입니다.";
		}

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "아이디 또는 비밀번호 오류입니다.";
	}

	$response = array(
		'userId'         => $row->id,
		'userName'       => $row->name,
		'userAuth'       => $row->auth,
		'groupCode'      => $row->groupCode,
		'organizeName'   => $organizeName,
		'mdName'         => $mdName,
		'payType'        => $row->payType,
		'cmsStatus'      => $row->cmsStatus,
		'memStatus'      => $row->memStatus,
		'insuId'         => $row->insuId,
		'conciergeId'    => $row->conciergeId,
		'infoStatus'     => $infoStatus,
		'token'          => $token,
		'result'         => $result_status,
		'message'        => $result_message,
		'data'           => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>