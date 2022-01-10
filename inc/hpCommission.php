<?php
//*********************************************************************
//**********         MD��ġ ������ ������Ʈ    **************************
//*********************************************************************
function sponsCommissionUpdate($sponsId, $sponsName, $memId, $memName) {
	global $connect;

	// �������� ���̺��� ������ ������ �����´�.
	$subsPriceA = 0; // �������
	$commiS = 0;    // MD��ġ(�⺻�ݾ�)
	$commiSA = 0;   // MD��ġ(�߰��ݾ�)
	$commiSL = 0;   // �߰��ݾ��ѵ�

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

	// ���ݼ��� MD��ġ Ƚ���� �˾ƺ���.
	$sql = "SELECT ifnull(count(idx),0) AS sponsCnt FROM member WHERE sponsId = '$sponsId' and recommandId = '$sponsId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$sponsCnt = $row->sponsCnt;

	if ($sponsCnt > 0) {
		if ($sponsCnt == 1) $commiPrice = $commiS
		else {
			$addPrice = $commiSA * $sponsCnt;

			if ($addPrice > $commiSL) $addPrice = $commiSL; // �߰��ݾ��� �߰��ݾ��ѵ����� ũ�ٸ�
		}

		$commiPrice = $commiS + $addPrice;

		// �����ڷᰡ ������ ����
		$sql = "DELETE FROM commission WHERE assort = 'CS' and sponsId = '$sponsId' and memId = '$memId'";
		$connect->query($sql);

		// ������ ==> MD��ġ ������ ���
		$sql = "INSERT INTO commission (sponsId, sponsName, memId, memName, assort, price, wdate) 
								VALUES ('$sponsId', '$sponsName', '$memId', '$memName', 'CS', '$commiPrice', now())";
		$connect->query($sql);

		/* *************************** �����ڷ� ********************* */
		$assort = "SA";

		// �����ڷᰡ ������ ����
		$sql = "DELETE FROM sales WHERE assort = '$assort' and memId = '$memId'";
		$result = $connect->query($sql);

		// ����(�������) ���
		if ($subsPriceA > 0) {
			$sql = "INSERT INTO sales (sponsId, sponsName, memId, memName, assort, price, wdate) 
							   VALUES ('$sponsId', '$sponsName', '$memId', '$memName', '$assort', '$subsPriceA', now())";
			$connect->query($sql);
		}
	}
}
?>