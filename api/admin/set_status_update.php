<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 관리자 추가/수정
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

	//$mode = "A";
	//$idx = 149;
	//$status = "9";
	//$adminId = "admin";

	$result_ok = "0";
	$result_message = "적용되었습니다.";

	// 관리자 정보
	$sql = "SELECT name FROM admin WHERE id = '$adminId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$adminName = $row->name;

	if ($mode === "M" || $mode === "E" || $mode === "C" || $mode === "P" || $mode === "A") {
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
		} else if ($mode === "P") {
			// 해당 idx의 joinPayStatus를 $status로 바꾼다.
			$sql = "UPDATE member SET joinPayStatus = '$status' WHERE idx = '$idx'";
			$connect->query($sql);

		// 회원 상태 변경
		} else if ($mode === "A") {
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

			// 해당 회원의 추천인, 회원 구분을 가져온다.
			$sql = "SELECT memId, memName, memAssort, recommandId FROM member WHERE idx = '$idx'";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);

			$memAssort = $row->memAssort == "A" ? "(대)" : "(판)";
			$memId     = $row->memId;
			$memName   = $memAssort . " " . $row->memName;
			$memAssort = $row->memAssort;
			$recommandId  = $row->recommandId;
			$recommandName = $row->memName;

			// **************************** 1-1. 승인완료인 경우 ==> 레그를 구성한다. 
			if ($status === "9") { 
				$approvalDate = date("Y-m-d");

				// 구독
				if ($memAssort == "S") {
					$accountAssort = "SS";
					$accountPrice = $subsPriceS;

					// 추천인의 레그를 알아본다.
					$sql = "SELECT memId, memName, memStatus, leg FROM member WHERE memId = '$recommandId'";
					$result = $connect->query($sql);
					$row = mysqli_fetch_object($result);

					$commiId   = $row->memId;
					$commiName = $row->memName;
					$leg       = $row->leg + 1;

					if ($row->memStatus == "9") {
						// 해당 회원에 스폰서, 레그를 저장한다.
						$sql = "UPDATE member SET sponsId = '$recommandId', leg = '$leg', approvalDate = '$approvalDate' WHERE idx = '$idx'";
						$connect->query($sql);

					} else {
						$result_ok = "1";
						$result_message = "추천인이 '승인완료'상태가 아닙니다.";
					}

				// MD
				} else if ($memAssort == "A") {
					$accountAssort = "SA";
					$accountPrice = $subsPriceA;

					// 추천인의 스폰서, 레그를 불러온다.
					$sql = "SELECT sponsId, ifnull(leg,0) as leg FROM member WHERE memId = '$recommandId'";
					$result_leg = $connect->query($sql);
					$row_leg = mysqli_fetch_object($result_leg);

					// 추천인의 스폰서 아이디, 회원 추천 횟수를 알아본다.
					$sql = "SELECT count(idx) as recommandCnt FROM member WHERE memAssort = 'A' and memStatus = '9' and recommandId = '$recommandId'";
					$result = $connect->query($sql);
					$row = mysqli_fetch_object($result);

					$recommandCnt = $row->recommandCnt;

					// 추천인이 1명이면 추천인의 스폰서에게 붙인다.
					if ($recommandCnt == 0) {
						$sponsId = $row_leg->sponsId;
						$leg     = $row_leg->leg;

						// 스폰서 아이디, 이름.
						$sql = "SELECT memId, memName, memStatus FROM member WHERE memId = '$sponsId'";
						$result = $connect->query($sql);
						$row = mysqli_fetch_object($result);
						$commiId = $row->memId;
						$commiName = $row->memName;

						if ($row->memStatus == "9") {
							// 해당 회원에 스폰서, 레그를 저장한다.
							$sql = "UPDATE member SET sponsId = '$sponsId', leg = '$leg', approvalDate = '$approvalDate' WHERE idx = '$idx'";
							$connect->query($sql);

						} else {
							$result_ok = "1";
							$result_message = "추천인의 스폰서가 '승인완료'상태가 아닙니다.";
						}

					// 추천인이 2명 이상이면 추천인에게 붙인다.
					} else {
						$leg = $row_leg->leg + 1;

						// 추천인 아이디, 이름.
						$sql = "SELECT memId, memName, memStatus FROM member WHERE memId = '$recommandId'";
						$result = $connect->query($sql);
						$row = mysqli_fetch_object($result);
						$commiId = $row->memId;
						$commiName = $row->memName;

						if ($row->memStatus == "9") {
							// 해당 회원에 스폰서, 레그를 저장한다.
							$sql = "UPDATE member SET sponsId = '$recommandId', leg = '$leg', approvalDate = '$approvalDate' WHERE idx = '$idx'";
							$connect->query($sql);

						} else {
							$result_ok = "1";
							$result_message = "추천인이 '승인완료'상태가 아닙니다.";
						}
					}

					// **************** 수수료 테이블에 개설비용을 추가한다. *********************
					if ($result_ok == "0") {
						// 기존자료가 있으면 삭제
						$sql = "DELETE FROM commission WHERE assort = 'CS' and sponsId = '$commiId' and memId = '$memId'";
						$connect->query($sql);

						// 수수료(개설) 등록
						$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, assort, price, wdate) 
						                        VALUES ('$commiId', '$commiName', '$memId', '$memName', 'CS', '$commiPrice', now())";
						$connect->query($sql);
					}
				}

				// **************** 매출 테이블에 개설비용을 추가한다. *********************
				if ($result_ok == "0") {
					// 기존자료가 있으면 삭제
					$sql = "DELETE FROM sales WHERE assort = '$accountAssort' and memId = '$memId'";
					$result = $connect->query($sql);

					// 매출(개설) 등록
					if ($accountPrice > 0) {
						$sql = "INSERT INTO sales (sponsId, sponsName, memId, memName, assort, price, wdate) 
											VALUES ('$commiId', '$commiName', '$memId', '$memName', '$accountAssort', '$accountPrice', now())";
						$connect->query($sql);
					}

					// 최종적으로 해당 idx의 memStatus를 $status로 바꾼다.
					$sql = "UPDATE member SET memStatus = '$status' WHERE idx = '$idx'";
					$result = $connect->query($sql);
				}

			// **************************** 1-2. 접수중인 경우 ==> 레그를 초기화한다.
			} else if ($status === "0") {  
				// 해당 회원의 스폰서 아이디, 레그를 초기화
				$sql = "UPDATE member SET sponsId = null, leg = null, memStatus = '0' WHERE idx = '$idx'";
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
		$result_ok = "1";
		$result_message = "CMS상태/회원상태 선택값이 없습니다";
	}

	$response = array(
		'result'    => $result_ok,
		'message'   => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>