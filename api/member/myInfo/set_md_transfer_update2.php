<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/memberStatusUpdate2.php";
	include "../../../inc/kakaoTalk.php";

	/*
	* 내정보 > MD전환신청
	* parameter
		userId: 회원아이디
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$userId     = $input_data->{'userId'};
	
	// $userId = "a55645136";

	// 회원정보 검색
	$sql = "SELECT idx, memId, memName, hpNo, groupCode, payType FROM member WHERE memId = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($row->hpNo !== "") $row->hpNo = aes_decode($row->hpNo);

		$idx       = $row->idx;
		$memId     = $row->memId;
		$memName   = $row->memName;
		$hpNo      = $row->hpNo;
		$groupCode = $row->groupCode;
		$payType   = $row->payType;

		// 그룹정보 > 회원구성정보 > MD
		$sql = "SELECT organizeCode FROM group_organize WHERE groupCode = '$groupCode' AND memAssort = 'M'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);
			$organizeCode = $row2->organizeCode;

			// 회원정보 > 서비스이용료 > 납부방식 > 구독료 > CMS 납부금액을 변경
			if ($payType == "S") {
				// 그룹정보 > 회원구성정보 > 서비스정보
				$sql = "SELECT subsFee FROM group_organize_service WHERE groupCode = '$groupCode' and organizeCode = '$organizeCode'";
				$result3 = $connect->query($sql);

				if ($result3->num_rows > 0) {
					$row3 = mysqli_fetch_object($result3);
					$cmsAmount = $row3->subsFee;

					// 그룹정보 > 회원구성정보 > 보너스정보 > 피추천인이 구독료 납부
					$sql = "SELECT subsSaveAssort, subsSaveFee FROM group_organize_bonus WHERE groupCode = '$groupCode' and organizeCode = '$organizeCode' AND payType = '$payType'";
					$result4 = $connect->query($sql);

					if ($result4->num_rows > 0) {
						$row4 = mysqli_fetch_object($result4);
						$subsSaveAssort = $row4->subsSaveAssort;
						$subsSaveFee    = $row4->subsSaveFee;

						if ($subsSaveAssort == "R") $commiAmount = ($cmsAmount / 110 * 100) * ($subsSaveFee / 100);
						else $commiAmount = $subsSaveFee;

						// CMS 납부금액을 변경한다.
						$sql = "UPDATE cms SET cmsAmount = '$cmsAmount', commiAmount = '$commiAmount' WHERE memId = '$userId'";
						$connect->query($sql);
					}
				}
			}

			// 회원구분을 MD로 변경한다.
			$sql = "UPDATE member SET organizeCode = '$organizeCode', memStatus = '0' WHERE memId = '$userId'";
			$connect->query($sql);

			// 레그를 구성한다.
			$status = "9";
			$result_approval = memberApproval($idx, $status);

			$arrResult = explode("|", $result_approval);
			$result_status  = $arrResult[0];
			$result_message = $arrResult[1];

			// member log table에 등록한다.
			$sql = "INSERT INTO member_log (memIdx, adminId, adminName, logAssort, status, wdate)
									VALUES ('$idx', '$memId', '$memName', 'T', '$status', now())";
			$connect->query($sql);

		} else {
			$result_status = "1";
			$result_message = "'회원그룹정보'가 없습니니다.";
		}

	} else {
		$result_status = "1";
		$result_message = "등록되지 않은 회원입니다.";
	}

	$response = array(
		'result'  => $result_status,
		'message' => $result_message,
    );
//print_r($response);
//exit;
    echo json_encode( $response );

    // db connection 을 닫거나, connection을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>