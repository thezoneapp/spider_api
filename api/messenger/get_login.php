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

	if ($userPw != "") $userPw = aes128encrypt($userPw);
	//$userId = "27233377";
	//$userPw = "27233377";
	// 실행할 쿼리를 작성합니다.
	$data = Array();
    $sql = "SELECT idx, id, name, passwd, auth, contractStatus, cmsStatus, memStatus, memAssort,
	               registNo, accountName, accountNo, accountBank 
			FROM ( SELECT idx, id, name, passwd, auth, '9' AS contractStatus, '9' AS cmsStatus, if(use_yn = 'Y', '9', '8') AS memStatus, 'A' as memAssort,
			              '1' as registNo, '1' as accountName, '1' as accountNo, '1' as accountBank 
                   FROM admin
                   union 
                   SELECT idx, memId AS id, memName AS name, memPw AS passwd, memAssort AS auth, contractStatus, cmsStatus, memStatus, 'M' as memAssort, 
				          registNo, accountName, accountNo, accountBank 
                   FROM member
				 ) m 
			WHERE id = '$userId' and passwd = '$userPw'";
	$result = $connect->query($sql);
//echo $sql;
//exit;
    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->memStatus == "8") {
			// 결과를 반환합니다.
			$result_ok = "1";
			$result_message = "'사용중지'된 아이디입니다.";

		} else {
			// 토큰값 생성
			if ($row->memAssort == "M") {
				$token = $row->idx . date("ymdHis");

				$sql = "UPDATE member SET token = '$token' WHERE memId = '$userId'";
				$connect->query($sql);
			}

			// 성공 결과를 반환합니다.
			if ($row->memStatus == "9") {
				if ($row->registNo == "" || $row->accountName == "" || $row->accountNo == "" || $row->accountBank == "") {
					$result_ok = "0";
					$infoStatus = "0";
					$result_message = "정산계좌정보를 입력해주세요.";

				} else {
					$result_ok = "0";
					$infoStatus = "9";
					$result_message = "로그인에 성공하였습니다.";
				}

			} else {
				if ($row->contractStatus == "0" || $row->cmsStatus == "0") {
					$result_ok = "0";
					$result_message = "'가맹점 또는 CMS 계약'이 체결되지 않았습니다.";

				} else {
					$result_ok = "0";
					$result_message = "현재 회원님은 '승인대기' 상태입니다.";
				}
			}

			$data = array(
				'userId'         => $row->id,
				'userName'       => $row->name,
				'userAuth'       => $row->auth,
				'contractStatus' => $row->contractStatus,
				'cmsStatus'      => $row->cmsStatus,
				'memStatus'      => $row->memStatus,
				'memAssort'      => $row->memAssort,
				'infoStatus'     => $infoStatus,
				'token'          => $token
			);
		}

    } else {
		// 실패 결과를 반환합니다.
		$result_ok = "1";
		$result_message = "아이디 또는 비밀번호 오류입니다.";
	}

	$response = array(
		'userId'         => $row->id,
		'userName'       => $row->name,
		'userAuth'       => $row->auth,
		'contractStatus' => $row->contractStatus,
		'cmsStatus'      => $row->cmsStatus,
		'memStatus'      => $row->memStatus,
		'infoStatus'     => $infoStatus,
		'result'         => $result_ok,
		'message'        => $result_message,
		'data'           => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>