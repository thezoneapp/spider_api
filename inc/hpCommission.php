<?php
//*********************************************************************
//**********         MD유치 수수료 업데이트    **************************
//*********************************************************************
function sponsCommissionUpdate($sponsId, $sponsName, $memId, $memName) {
	global $connect;

	// 기초정보 테이블의 수수료 정보를 가져온다.
	$subsPriceA = 0; // 개설비용
	$commiS = 0;    // MD유치(기본금액)
	$commiSA = 0;   // MD유치(추가금액)
	$commiSL = 0;   // 추가금액한도

	$sql = "SELECT code, content FROM setting WHERE code in('subsPriceA','commiS','commiSA','commiSL')";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[code] == "commiS") $commiS = $row[content];
			else if ($row[code] == "commiSA") $commiSA = $row[content];
			else if ($row[code] == "commiSL") $commiSL = $row[content];
			else if ($row[code] == "subsPriceA") $subsPriceA = $row[content];
		}
	}

	// 스펀서의 MD유치 횟수를 알아본다.
	$sql = "SELECT ifnull(count(idx),0) AS sponsCnt FROM member WHERE sponsId = '$sponsId' and recommandId = '$sponsId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$sponsCnt = $row->sponsCnt;

	if ($sponsCnt > 0) {
		if ($sponsCnt == 1) $commiPrice = $commiS
		else {
			$addPrice = $commiSA * $sponsCnt;

			if ($addPrice > $commiSL) $addPrice = $commiSL; // 추가금액이 추가금액한도보다 크다면
		}

		$commiPrice = $commiS + $addPrice;

		// 기존자료가 있으면 삭제
		$sql = "DELETE FROM commission WHERE assort = 'CS' and sponsId = '$sponsId' and memId = '$memId'";
		$connect->query($sql);

		// 스폰서 ==> MD유치 수수료 등록
		$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, assort, price, wdate) 
								VALUES ('$sponsId', '$sponsName', '$memId', '$memName', 'CS', '$commiPrice', now())";
		$connect->query($sql);

		/* *************************** 매출자료 ********************* */
		$assort = "SA";

		// 기존자료가 있으면 삭제
		$sql = "DELETE FROM sales WHERE assort = '$assort' and memId = '$memId'";
		$result = $connect->query($sql);

		// 매출(개설비용) 등록
		if ($subsPriceA > 0) {
			$sql = "INSERT INTO sales (sponsId, sponsName, memId, memName, assort, price, wdate) 
							   VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$assort', '$subsPriceA', now())";
			$connect->query($sql);
		}
	}
}
?>