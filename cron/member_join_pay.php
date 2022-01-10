i<?
    /***********************************************************************************************************************
	* 회원 가입비 미납 > 납부 자동 업데이트 > 현재 사용안함
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
	$logAssort = "P";

	// 승인일자
	$approvalDate = date("Y-m-d");

	$sql = "SELECT m.idx, m.sponsId, m.memId, m.memName, m.memAssort, p.point 
			FROM member m 
				 INNER JOIN ( 
					 select memId, sum(point) as point from point group by memId 
				  ) p ON m.memId = p.memId 
			WHERE m.memAssort = 'M' AND m.memStatus = '9' AND m.joinPayStatus = '1' AND p.point >= 0";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$idx       = $row[idx];
			$sponsId   = $row[sponsId];
			$memId     = $row[memId];
			$memName   = $row[memName];
			$memAssort = $row[memAssort];

			// 스폰서 정보
			$sql = "SELECT memName FROM member WHERE memId = '$sponsId'";
			$result2 = $connect->query($sql);
			$row2 = mysqli_fetch_object($result2);
			$sponsName = $row2->memName;

			// 기초정보 테이블의 개설비용, MD유치보너스를 가져온다.
			$subsPriceA = 0;
			$commiPrice = 0;
			$sql = "SELECT code, content FROM setting WHERE code in('commiS','subsA')";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				while($row2 = mysqli_fetch_array($result2)) {
					if ($row2[code] == "subsA") $subsPriceA = $row2[content];
					else if ($row2[code] == "commiS") $commiPrice = $row2[content];
				}
			}

			// ***************** 회원정보 > 가입비납부 > 납부처리 **************************************
			$sql = "UPDATE member SET joinPayStatus = '$status' WHERE idx = '$idx'";
			$connect->query($sql);

			// ***************** 스폰서 > MD유치 보너스 **************************************
			// 기존자료가 있으면 삭제
			$sql = "DELETE FROM commission WHERE assort = 'CS' and sponsId = '$sponsId' and memId = '$memId'";
			$connect->query($sql);

			// 스폰서 ==> MD유치 수수료 등록
			$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, memAssort, assort, price, wdate) 
									VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$memAssort', 'CS', '$commiPrice', now())";
			$connect->query($sql);

			// ***************** member log table에 등록 **************************************
			$sql = "INSERT INTO member_log (memIdx, adminId, adminName, logAssort, status, wdate)
									VALUES ('$idx', '$adminId', '$adminName', '$logAssort', '$status', now())";
			$connect->query($sql);
		}
	}

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);

	exit;
?>
