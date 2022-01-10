<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    include "../../inc/kakaoTalk.php";

	/*
	* 관리자정보
	* parameter
		userName:     회원명
		userHpNo:     휴대폰번호
		device:       접속기기
		connectIP:    접속 IP
	*/
	$back_data = json_decode(file_get_contents('php://input'));
	$userName  = trim($back_data->{'userName'});
	$userHpNo  = trim($back_data->{'userHpNo'});
	$device    = $back_data->{'device'};
	$connectIP = $back_data->{'connectIP'};

	//$userName = "박태수";
	//$userHpNo = "010-2723-3377";
	//$device    = "A";
	//$connectIP = "114.204.52.185";

	// 그룹코드
	$groupCode = getDomainGroupCode($_SERVER['HTTP_REFERER']);

	if ($userHpNo != "") $hpNo = $userHpNo;
	if ($userHpNo != "") $userHpNo = aes128encrypt($userHpNo);

	// 실행할 쿼리를 작성합니다.
	$certifyStatus = "Y";
	$certifyNo = "";

	$data = Array();
    $sql = "SELECT idx, memId, memName, memPw, memAssort, groupCode, organizeCode, payType, cmsStatus, clearStatus, memStatus, 
			       registNo, accountName, accountNo, accountBank, insuId, conciergeId, firstLogin 
            FROM member
			WHERE memName = '$userName' and hpNo = '$userHpNo'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$memId        = $row->memId;
		$groupCode    = $row->groupCode;
		$organizeCode = $row->organizeCode;

		// 토큰값 생성
		$token = $row->idx . date("ymdHis");

		// 접속 기기 체크
		$sql = "SELECT idx FROM member WHERE memName = '$userName' and hpNo = '$userHpNo' and connectIp = '$connectIP' and connectDevice = '$device'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$certifyStatus = "Y";

			// 접속 기기
			if ($device == "W") $device_sql = ", useWeb = 'Y' ";
			else if ($device == "A") $device_sql = ", useAndroid = 'Y' ";
			else if ($device == "I") $device_sql = ", useIphone = 'Y' ";
			else $device_sql = "";

			$sql = "UPDATE member SET token = '$token',
									  connectIp = '$connectIP', 
									  connectDevice = '$device' $device_sql 
						WHERE memId = '$memId'";
			$connect->query($sql);

		} else {
			$certifyStatus = "N";
			$certifyNo = mt_rand(100000, 999999);

			// 알림톡 전송
			$hpNo = preg_replace('/\D+/', '', $hpNo);
			$receiptInfo = array(
				"memId"       => $row->memId,
				"memName"     => $row->memName,
				"certifyNo"   => $certifyNo,
				"receiptHpNo" => $hpNo,
			);
			sendTalk($groupCode, "M_05_01", $receiptInfo);
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
				'userId'         => $row->memId,
				'userName'       => $row->memName,
				'userAuth'       => $row->memAssort,
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

			if ($kakaoId != "" && $kakaoId != null) $kakaoId_sql = ", kakaoId = '$kakaoId' ";
			else $kakaoId_sql = "";

			$sql = "UPDATE member SET firstLogin = '1' $kakaoId_sql WHERE memId = '$memId'";
			$connect->query($sql);

		} else {
			$result_status = "1";
			$result_message = "현재 회원님은 '승인대기' 상태입니다.";
		}

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
		$result_message = "이름 또는 휴대폰번호 오류입니다.";
	}

	$response = array(
		'result'         => $result_status,
		'message'        => $result_message,
		'userId'         => $row->memId,
		'userName'       => $row->memName,
		'userAuth'       => $row->memAssort,
		'groupCode'      => $row->groupCode,
		'organizeName'   => $organizeName,
		'mdName'         => $mdName,
		'payType'        => $row->payType,
		'cmsStatus'      => $row->cmsStatus,
		'memStatus'      => $row->memStatus,
		'insuId'         => $row->insuId,
		'conciergeId'    => $row->conciergeId,
		'infoStatus'     => $infoStatus,
		'certifyStatus'  => $certifyStatus,
		'certifyNo'      => $certifyNo,
		'token'          => $token,
		'data'           => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>