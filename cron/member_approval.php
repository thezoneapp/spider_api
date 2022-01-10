<?
    /***********************************************************************************************************************
	* 회원상태 자동 업데이트
	***********************************************************************************************************************/

	$db_host = "localhost";
	$db_user = "spiderfla";
	$db_pass = "dlfvkf#$12";
	$db_name = "spiderfla";

	$connect = @mysqli_connect($db_host, $db_user, $db_pass, $db_name) or die("Failed to connect to MySQL: " . mysqli_error()); 

	// 계약상태:계약완료 & CMS상태:승인완료 & 가입비납부상태:입금완료 ==> 회원상태:접수중인 회원을 승인완료처리한다.
	$adminId = "auto";
	$adminName = "자동";
	$status = "9";
	$logAssort = "A";

	// 승인일자
	$approvalDate = date("Y-m-d");

	$sql = "SELECT idx, comment 
			FROM member 
			WHERE contractStatus = '9' and cmsStatus = '9' and joinPayStatus = '9' and memStatus = '0' 
			ORDER BY idx ASC";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$idx     = $row[idx];
			$comment = $row[comment];

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

			// 해당 회원의 추천인, 회원 구분을 가져온다.
			$result_ok = "0";
			$sql = "SELECT memId, memName, memAssort, recommandId FROM member WHERE idx = '$idx'";
			$result = $connect->query($sql);
			$row = mysqli_fetch_object($result);

			$memAssort = $row->memAssort == "M" ? "(MD)" : "(구독)";
			$memId     = $row->memId;
			$memName   = $memAssort . " " . $row->memName;
			$memAssort = $row->memAssort;
			$recommandId  = $row->recommandId;
			$recommandName = $row->memName;

			// **************************** 1. 레그를 구성한다. 
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
					$sql = "UPDATE member SET sponsId = '$recommandId', approvalDate = '$approvalDate' WHERE idx = '$idx'";
					$connect->query($sql);

				} else {
					$result_ok = "1";
					$result_message = "추천인이 '승인완료'상태가 아닙니다.";
				}

			// MD
			} else if ($memAssort == "M") {
				$accountAssort = "SA";
				$accountPrice = $subsPriceA;

				// 추천인의 스폰서, 레그를 불러온다.
				$sql = "SELECT sponsId, ifnull(leg,0) as leg FROM member WHERE memId = '$recommandId'";
				$result_leg = $connect->query($sql);
				$row_leg = mysqli_fetch_object($result_leg);

				// 추천인의 스폰서 아이디, 회원 추천 횟수를 알아본다.
				$sql = "SELECT count(idx) as recommandCnt FROM member WHERE memAssort = 'M' and memStatus = '9' and recommandId = '$recommandId'";
				$result = $connect->query($sql);
				$row = mysqli_fetch_object($result);

				$recommandCnt = $row->recommandCnt;

				// 추천인이 0명이면 추천인의 스폰서에게 붙인다.
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
						$sql = "UPDATE member SET sponsId = '$sponsId', approvalDate = '$approvalDate' WHERE idx = '$idx'";
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
						$sql = "UPDATE member SET sponsId = '$recommandId', approvalDate = '$approvalDate' WHERE idx = '$idx'";
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

			} else {
				// 오류 메세지를 관리자메모에 저장한다.
				if ($comment == "") $comment = $result_message;
				else $comment = $comment . "\n" . $result_message;

				$sql = "UPDATE member SET comment = '$comment' WHERE idx = '$idx'";
				$result = $connect->query($sql);
			}

			// member log table에 등록한다.
			$sql = "INSERT INTO member_log (memIdx, adminId, adminName, logAssort, status, wdate)
									VALUES ('$idx', '$adminId', '$adminName', '$logAssort', '$status', now())";
			$connect->query($sql);
		}
	}

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);

	exit;
?>