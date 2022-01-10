<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";
	include "../../../inc/memberStatusUpdate.php";
	//include "../../../inc/sponsCommissionUpdate.php";

	/*
	* 회원정보 추가/수정
	* parameter ==> mode:     c(CMS상태), a(회원상태)
	* parameter ==> idx:      변경할 레코드 id
	* parameter ==> status:   상태코드
	* parameter ==> adminId:  관리자 아이디
	*/
	$data_back = json_decode(file_get_contents('php://input'));
	$mode      = $data_back->{'mode'};
	$idx       = $data_back->{'idx'};
	$status    = $data_back->{'status'};
	$adminId   = $data_back->{'adminId'};

	//$mode = "P";
	//$idx = 266;
	//$status = "9";
	//$adminId = "admin";

	$result_status = "0";
	$result_message = "적용되었습니다.";

	// 관리자 정보
	$sql = "SELECT name FROM admin WHERE id = '$adminId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$adminName = $row->name;

	if ($mode === "M" || $mode === "E" || $mode === "C" || $mode === "P" || $mode === "D" || $mode === "A") {
		// 가입구분 변경
		if ($mode === "M") {
			// 해당 idx의 memAssort를 $status로 바꾼다.
			$sql = "UPDATE member SET memAssort = '$status' WHERE idx = '$idx'";
			$connect->query($sql);

		// 전자계약 상태 변경
		} else if ($mode === "E") {
			// 해당 idx의 contractStatus를 $status로 바꾼다.
			$sql = "UPDATE member SET contractStatus = '$status' WHERE idx = '$idx'";
			$connect->query($sql);

		// CMS 상태 변경
		} else if ($mode === "C") {
			// 해당 idx의 cmsStatus를 $status로 바꾼다.
			$sql = "UPDATE member SET cmsStatus = '$status' WHERE idx = '$idx'";
			$connect->query($sql);

		// 가입비 상태 변경
		} else if ($mode == "P") {
			// 회원 정보
			$sql = "SELECT sponsId, memId, memName FROM member WHERE idx = '$idx'";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);
			$sponsId   = $row->sponsId;
			$memId     = $row->memId;
			$memName   = $row->memName;

			// 스폰서 정보
			$sql = "SELECT memName FROM member WHERE memId = '$sponsId'";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);
			$sponsName = $row->memName;

			// 해당 idx의 joinPayStatus를 $status로 바꾼다.
			$sql = "UPDATE member SET joinPayStatus = '$status' WHERE idx = '$idx'";
			$connect->query($sql);

			// 가입비 납부이면
			if ($status == "9") {
				// 기초정보 테이블의 개설비용, 회원 수수료를 가져온다.
				$subsPriceA = 0;
				$subsPriceS = 0;
				$commiPrice = 0;

				$sql = "SELECT code, content FROM setting WHERE code in('commitS','subsA','subsS')";
				$result = $connect->query($sql);

				if ($result->num_rows > 0) {
					while($row = mysqli_fetch_array($result)) {
						if ($row[code] == "subsA") $subsPriceA = $row[content];
						else if ($row[code] == "subsS") $subsPriceS = $row[content];
						else if ($row[code] == "commitS") $commiPrice = $row[content];
					}
				}

				// 포인트 목록에 추가
				$assort = "IJ"; // 가입비납부
				$descript = "가입비 선납";
				$point = $subsPriceA;

				// 기존자료가 있으면 삭제
				$sql = "DELETE FROM point WHERE memId = '$memId' and assort = '$assort' and descript = '$descript'";
				$connect->query($sql);

				$sql = "INSERT INTO point (memId, memName, assort, descript, point, wdate)
								   VALUES ('$memId', '$memName', '$assort', '$descript', '$point', now())";
				$connect->query($sql);

				// MD 유치 수수료
				//sponsCommissionUpdate($sponsId, $sponsName, $memId, $memName);

				// 기존자료가 있으면 삭제
				$sql = "DELETE FROM commission WHERE assort = 'CS' and sponsId = '$sponsId' and memId = '$memId'";
				$connect->query($sql);

				// 스폰서 ==> MD유치 수수료 등록
				$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, assort, price, wdate) 
											VALUES ('$sponsId', '$sponsName', '$memId', '$memName', 'CS', '$commiPrice', now())";
				$connect->query($sql);

				/* *************************** 매출자료 ********************* */
				$accountAssort = "SA";
				$accountPrice  = $subsPriceA;

				// 기존자료가 있으면 삭제
				$sql = "DELETE FROM sales WHERE assort = '$accountAssort' and memId = '$memId'";
				$result = $connect->query($sql);

				// 매출(개설) 등록
				if ($accountPrice > 0) {
					$sql = "INSERT INTO sales (sponsId, sponsName, memId, memName, assort, price, wdate) 
										VALUES ('$sponsId', '$sponsName', '$memId', '$memName', 'SA', '$accountPrice', now())";
					$connect->query($sql);
				}

			// 가입비 납부가 아니면
			} else if ($status == "1") {
				// 포인트 자료 삭제
				$sql = "DELETE FROM point WHERE assort = 'IJ' and memId = '$memId' and point > 0";
				$connect->query($sql);

				// 수수료 자료 삭제
				$sql = "DELETE FROM commission WHERE assort = 'CS' and sponsId = '$sponsId' and memId = '$memId'";
				$connect->query($sql);

				// 매출자료 삭제
				$sql = "DELETE FROM sales WHERE assort = 'SA' and memId = '$memId'";
				$result = $connect->query($sql);
			}

		// 구독료납부 상태 변경
		} else if ($mode === "D") {
			// 해당 idx의 clearStatus를 $status로 바꾼다.
			$sql = "UPDATE member SET clearStatus = '$status' WHERE idx = '$idx'";
			$connect->query($sql);

			// 회원 정보
			$sql = "SELECT memId FROM member WHERE idx = '$idx'";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);
			$memId = $row->memId;

			// commission 테이블 ==> 해당회원 and 정산대기 and 보류중인 자료를 해제처리한다.
			if ($status == "0") {
				$clearDate = date("Y-m-d");
				$sql = "UPDATE commission SET clearStatus = 'N', clearDate = '$clearDate' WHERE (sponsId = '$memId' or memId = '$memId') and accurateStatus = '0' and clearStatus = 'Y'";
				$connect->query($sql);

			// commission 테이블 ==> 해당회원 and 정산대기중인 자료를 보류처리한다.
			} else {
				$sql = "UPDATE commission SET clearStatus = 'Y', clearDate = null WHERE (sponsId = '$memId' or memId = '$memId') and accurateStatus = '0'";
				$connect->query($sql);
			}

		// 회원 상태 변경
		} else if ($mode === "A") {
			// **************************** 승인완료인 경우 
			if ($status == "9") { 
				// 해당 회원상태 정보
				$sql = "SELECT memStatus FROM member WHERE idx = '$idx'";
				$result = $connect->query($sql);
				$row = mysqli_fetch_object($result);
				$memStatus = $row->memStatus;

				if ($memStatus == "0") {
					// 레그를 구성한다.
					$result_approval = memberApproval($idx, $status);

					$arrResult = explode("|", $result_approval);
					$result_status  = $arrResult[0];
					$result_message = $arrResult[1];

				} else {
					// 해당 idx의 cmsStatus를 $status로 바꾼다.
					$sql = "UPDATE member SET memStatus = '$status' WHERE idx = '$idx'";
					$connect->query($sql);
					$result_status  = "0";
					$result_message = "변경되었습니다.";
				}

			// **************************** 탈퇴완료인 경우 ==> 레그를 재구성한다. 
			} else if ($status == "8") { 
				$result_out = memberOut($idx, $status);

				$arrResult = explode("|", $result_out);
				$result_status  = $arrResult[0];
				$result_message = $arrResult[1];

				// 회원정보
				$sql = "SELECT groupCode, memId, memName, hpNo, cmsId, outReason FROM member WHERE idx = '$idx'";
				$result = $connect->query($sql);
				$row = mysqli_fetch_object($result);

				$groupCode = $row->groupCode;
				$memId     = $row->memId;
				$memName   = $row->memName;
				$hpNo      = $row->hpNo;
				$cmsId     = $row->cmsId;
				$outReason = $row->outReason;

				if ($hpNo != "") $hpNo = aes_decode($hpNo);

				// CMS 해지 처리
				$url = "https://api.hyosungcms.co.kr/v1/members/$cmsId";

				// header
				$header = Array(
					"Content-Type: application/json; charset=utf-8", 
					"Authorization: VAN $SW_KEY:$CUST_KEY"
				);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
				curl_setopt($ch, CURLOPT_ENCODING , "");
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

				$response = curl_exec($ch);

				curl_close($ch);

				$response = json_decode($response, true);

				if ($response[error] != null) {
					$message = $response[error][message];

				} else {
					// 회원 테이블 ==> CMS상태 '해지완료' 변경
					$sql = "UPDATE member SET cmsStatus = '8' WHERE memId = '$memId'";
					$connect->query($sql);
				}
				
				// 탈퇴완료 알림톡 전송
				$hpNo = preg_replace('/\D+/', '', $hpNo);
				$receiptInfo = array(
					"memName"    => $memName,
					"memHpNo"    => $hpNo,
					"outReason"  => $outReason
				);
				sendTalk($groupCode, "M_04_01", $receiptInfo);

			// **************************** 접수중인 경우 ==> 레그를 초기화한다.
			} else if ($status === "0") {  
				// 해당 회원의 ID를 가져온다.
				$sql = "SELECT memId FROM member WHERE idx = '$idx'";
				$result = $connect->query($sql);
				$row = mysqli_fetch_object($result);

				$memId = $row->memId;

				// 해당 회원의 스폰서 아이디, 레그를 초기화
				$sql = "UPDATE member SET sponsId = null, leg = null, memStatus = '0', approvalDate = null WHERE idx = '$idx'";
				$connect->query($sql);

				// 추천인 아이디에 포함되는 회원의 스폰서 아이디, 레그, 회원 상태를 초기화
				$sql = "UPDATE member SET sponsId = null, leg = null, memStatus = '0' WHERE recommandId = '$memId'";
				$connect->query($sql);

				// 수수료 삭제
				$sql = "DELETE FROM commission WHERE assort = 'CS' and memId = '$memId'";
				$connect->query($sql);

				// 매출 삭제
				$sql = "DELETE FROM sales WHERE (assort = 'SA' or assort = 'SS') and memId = '$memId'";
				$connect->query($sql);

			// **************************** 1-3. 처리,보류,중지.
			} else {
				// 해당 idx의 memStatus를 $status로 바꾼다.
				$sql = "UPDATE member SET memStatus = '$status' WHERE idx = '$idx'";
				$connect->query($sql);
			}
		}

		// member log table에 등록한다.
		$sql = "INSERT INTO member_log (memIdx, adminId, adminName, logAssort, status, wdate)
		                        VALUES ('$idx', '$adminId', '$adminName', '$mode', '$status', now())";
		$connect->query($sql);

	} else {
		$result_status = "1";
		$result_message = "변경할 상태값이 없습니다";
	}

	$response = array(
		'result'    => $result_status,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>