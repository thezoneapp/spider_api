<?php
//***********************************************************************************************************************************
//            회원 승인완료         
//***********************************************************************************************************************************
function memberApproval($idx, $status) {
	global $connect;

	// 기초정보 테이블의 개설비용, 회원 수수료를 가져온다.
	$subsPriceA = 0;
	$subsPriceS = 0;
	$commiPrice = 0;

	$sql = "SELECT code, content FROM setting WHERE code in('commiS','subsA','subsS')";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[code] == "subsA") $subsPriceA = $row[content];
			else if ($row[code] == "subsS") $subsPriceS = $row[content];
			else if ($row[code] == "commiS") $commiPrice = $row[content];
		}
	}

	// 해당 회원의 추천인, 회원구분, 가입비 납부 정보.
	$sql = "SELECT memId, memName, memPw, hpNo, memAssort, recommandId, joinPayStatus FROM member WHERE idx = '$idx'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$memAssort     = $row->memAssort;
	$memId         = $row->memId;
	$memName       = $row->memName;
	$memPw         = $row->memPw;
	$hpNo          = $row->hpNo;
	$recommandId   = $row->recommandId;
	$recommandName = $row->memName;
	$joinPayStatus = $row->joinPayStatus;

	if ($memPw != "") $memPw = aes_decode($memPw);
	if ($hpNo != "") $hpNo = aes_decode($hpNo);

	// 승인일자
	$approvalDate = date("Y-m-d");

	$result_status = "0";
	$result_message = "적용되었습니다.";

	// 구독
	if ($memAssort == "S") {
		$accountAssort = "SS";
		$accountPrice = $subsPriceS;

		// 추천인의 정보
		$sql = "SELECT memId, memName, memStatus FROM member WHERE memId = '$recommandId'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		$commiId   = $row->memId;
		$commiName = $row->memName;

		if ($row->memStatus == "9") {
			// 해당 회원에 스폰서를 저장한다.
			$sql = "UPDATE member SET sponsId = '$recommandId', approvalDate = '$approvalDate' WHERE idx = '$idx'";
			$connect->query($sql);

			// 추천인 history 등록
			$comment = "스폰서 지정";
			$sql = "SELECT memName FROM member WHERE memId = '$recommandId'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);
				$sponsName = $row2->memName;
			} else {
				$sponsName = "";
			}

			$sql = "INSERT INTO recommand_log (recommandId, memId, memName, sponsId, sponsName, comment, wdate)
									   VALUES ('$recommandId', '$memId', '$memName', '$recommandId', '$sponsName', '$comment', now())";
			$connect->query($sql);


		} else {
			$result_status = "1";
			$result_message = "추천인이 '승인완료'상태가 아닙니다.";
		}

	// MD
	} else if ($memAssort == "M") {
		$accountAssort = "SA";
		$accountPrice = $subsPriceA;

		// 추천인의 스폰서 아이디, 회원 추천 횟수를 알아본다.
		$sql = "SELECT count(idx) as recommandCnt FROM member WHERE memAssort = 'M' and memStatus = '9' and recommandId = '$recommandId'";
		$result = $connect->query($sql);
		$row = mysqli_fetch_object($result);

		$recommandCnt = $row->recommandCnt;

		// 추천인이 0명이면 추천인의 스폰서에게 붙인다.
		if ($recommandCnt == 0) {
			// 추천인의 스폰서를 불러온다.
			$sql = "SELECT sponsId FROM member WHERE memId = '$recommandId'";
			$result_sponse = $connect->query($sql);
			$row_sponse = mysqli_fetch_object($result_sponse);

			$sponsId = $row_sponse->sponsId;

			// 스폰서 아이디, 이름.
			$sql = "SELECT memId, memName, memStatus FROM member WHERE memId = '$sponsId'";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);

			$commiId = $row->memId;
			$commiName = $row->memName;

			if ($row->memStatus == "9") {
				// 해당 회원에 스폰서를 저장한다.
				$sql = "UPDATE member SET sponsId = '$sponsId', approvalDate = '$approvalDate' WHERE idx = '$idx'";
				$connect->query($sql);

				// 추천인 history 등록
				$comment = "스폰서 지정";
				$sql = "INSERT INTO recommand_log (recommandId, memId, memName, sponsId, sponsName, comment, wdate)
										   VALUES ('$recommandId', '$memId', '$memName', '$sponsId', '$commiName', '$comment', now())";
				$connect->query($sql);

			} else {
				$result_status = "1";
				$result_message = "추천인의 스폰서가 '승인완료'상태가 아닙니다.";
			}

		// 추천인이 2명 이상이면 추천인에게 붙인다.
		} else {
			// 추천인 아이디, 이름.
			$sql = "SELECT memId, memName, memStatus FROM member WHERE memId = '$recommandId'";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);

			$commiId = $row->memId;
			$commiName = $row->memName;
			$memStatus = $row->memStatus;

			if ($memStatus == "9") {
				// 해당 회원에 스폰서, 레그를 저장한다.
				$sql = "UPDATE member SET sponsId = '$recommandId', approvalDate = '$approvalDate' WHERE idx = '$idx'";
				$connect->query($sql);

				// 추천인 history 등록
				$comment = "스폰서 지정";
				$sql = "INSERT INTO recommand_log (recommandId, memId, memName, sponsId, sponsName, comment, wdate)
										   VALUES ('$recommandId', '$memId', '$memName', '$recommandId', '$commiName', '$comment', now())";
				$connect->query($sql);

			} else {
				$result_status = "1";
				$result_message = "추천인이 '승인완료'상태가 아닙니다.";
			}
		}

		// **************** 포인트 테이블에 가입비 후납내용을 추가한다. *********************
		if ($result_status == "0") {
			// 가입비 후납 존재여부 체크.
			$sql = "SELECT ifnull(count(idx),0) as pointCnt FROM point WHERE memId = '$memId' and assort = 'IJ'";
			$result_point = $connect->query($sql);
			$row_point = mysqli_fetch_object($result_point);

			$pointCnt = $row_point->pointCnt;

			// 포인트 목록에 추가
			if ($pointCnt == 0) {
				$assort = "IJ"; // 가입비납부
				$descript = "가입비 후납";
				$point = 0 - $accountPrice;
				$sql = "INSERT INTO point (memId, memName, assort, descript, point, wdate)
								   VALUES ('$memId', '$memName', '$assort', '$descript', '$point', now())";
				$connect->query($sql);
			}
		}
	}

	// 최종적으로 해당 idx의 memStatus를 $status로 바꾼다.
	if ($result_status == "0") {
		$sql = "UPDATE member SET memStatus = '$status' WHERE idx = '$idx'";
		$connect->query($sql);

		// 알림톡 전송
		$memHpNo = preg_replace('/\D+/', '', $hpNo);
		$receiptInfo = array(
			"memName"     => $memName,
			"receiptHpNo" => $memHpNo
		);
		sendTalk("J_09_02", $receiptInfo);

		// 알림톡 전송 - 가자렌탈 박효정실장
		$receiptHpNo = "01055991009";
		$receiptInfo = array(
			"memId"       => $memId,
			"memName"     => $memName,
			"memPw"       => $memPw,
			"memHpNo"     => $hpNo,
			"receiptHpNo" => $receiptHpNo
		);
		sendTalk("J_09_03", $receiptInfo);
	}

	return $result_status . "|" . $result_message;
}

//***********************************************************************************************************************************
//             회원 탈퇴완료         
//***********************************************************************************************************************************
// 하위 레그 재구성
function reStructure($upMemId, $upSponsId) {
	global $connect;

	// 올려준 회원 검색
	$sql = "SELECT idx, memId, memName, recommandId FROM member WHERE recommandId = '$upMemId' and sponsId != '$upMemId' and memAssort = 'M' and memStatus = '9' ORDER BY idx ASC LIMIT 1";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$upIdx         = $row->idx;
	$upMemId       = $row->memId;
	$upMemName     = $row->memName;
	$upRecommandId = $row->recommandId;

	if ($upIdx) {
		// 검색된 회원을 탈퇴하고자 하는 회원의 스폰서ID로 변경해준다.
		$sql = "UPDATE member SET sponsId = '$upSponsId' WHERE idx = '$upIdx'";
		$connect->query($sql);

		// 추천인 history 등록
		$comment = "스폰서 재지정";
		$sql = "SELECT memName FROM member WHERE memId = '$upSponsId'";
		$result2 = $connect->query($sql);

		if ($result2->num_rows > 0) {
			$row2 = mysqli_fetch_object($result2);
			$upSponsName = $row2->memName;
		} else {
			$upSponsName = "";
		}

		$sql = "INSERT INTO recommand_log (recommandId, memId, memName, sponsId, sponsName, comment, wdate)
								   VALUES ('$upRecommandId', '$upMemId', '$upMemName', '$upSponsId', '$upSponsName', '$comment', now())";
		$connect->query($sql);

		$resultValue = "1";

	} else {
		$resultValue = "0";
	}

	return $resultValue . "|" . $upMemId;
}

// 회원 탈퇴 처리
function memberOut($idx, $status) {
	global $connect;

	// 탈퇴할 회원의 추천인ID, 스폰서ID, 회원구분을 가져온다.
	$sql = "SELECT memId, memName, memAssort, sponsId, recommandId, memStatus FROM member WHERE idx = '$idx'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$memAssort   = $row->memAssort;
	$memStatus   = $row->memStatus;
	$memId       = $row->memId;
	$memName     = $row->memName;
	$recommandId = $row->recommandId;
	$sponsId     = $row->sponsId;

	$result_status = "0";
	$result_message = "적용되었습니다.";

	// 해당 회원을 탈퇴처리한다.
	$sql = "UPDATE member SET sponsId = null, memStatus = '$status' WHERE idx = '$idx'";
	$connect->query($sql);

	// 추천인 history 등록
	$comment = "회원탈퇴";
	$sql = "SELECT memName FROM member WHERE memId = '$sponsId'";
	$result2 = $connect->query($sql);

	if ($result2->num_rows > 0) {
		$row2 = mysqli_fetch_object($result2);
		$sponsName = $row2->memName;
	} else {
		$sponsName = "";
	}

	$sql = "INSERT INTO recommand_log (recommandId, memId, memName, sponsId, sponsName, comment, wdate)
	                           VALUES ('$recommandId', '$memId', '$memName', '$sponsId', '$sponsName', '$comment', now())";
	$connect->query($sql);

	// 회원구분 == MD && 회원상태 == 승인완료 
	if ($memAssort == "A" && $memStatus == "9") {
		// 추천인ID == 스폰서ID ======> 자신을 스폰서로 가진 회원을 본사(dream)로 귀속
		if ($recommandId == $sponsId) {
			$sql = "UPDATE member SET sponsId = 'dream' WHERE sponsId = '$memId'";
			$connect->query($sql);

		// 추천인ID != 스폰서ID ======> 회원과 동일한 추천인ID를 가진 MD회원중에서 1명을 해당 회원의 스폰서ID로 올려준다.
		} else if ($recommandId != $sponsId) {
			// 올려줄 회원 검색 ==> 회원과 동일한 추천인ID를 가진 MD회원중에서 1명을 검색
			$sql = "SELECT idx, memId FROM member WHERE recommandId = '$recommandId' and sponsId = '$recommandId' and memAssort = 'M' and memStatus = '9' ORDER BY idx ASC LIMIT 1";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);

			$upIdx   = $row->idx;
			$upMemId = $row->memId;

			if ($upIdx) {
				// 위에서 검색된 회원을 탈퇴하고자 하는 회원의 스폰서ID로 변경해준다.
				$sql = "UPDATE member SET sponsId = '$sponsId' WHERE idx = '$upIdx'";
				$connect->query($sql);

				// 매 하위 단계의 첫번째 회원을 탈퇴하고자 하는 회원의 스폰서ID로 변경해준다.
				$true = true;

				while ($true) {
					$result_re = reStructure($upMemId, $sponsId);
					$arrResult = explode("|", $result_re);
					$upResult  = $arrResult[0];
					$upMemId   = $arrResult[1];

					if ($upResult == "0") $true = false;
				}
			}
		}
	}

	return $result_status . "|" . $result_message;
}
?>