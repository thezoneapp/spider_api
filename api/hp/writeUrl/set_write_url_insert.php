<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 신청 > 가입신청서 > 추가
	*/

	$requestAssorts = ['N','M','C'];
	$installments = ['24','30','36'];
	$discountTypes = ['S','C'];

	$sql = "SELECT hg.telecoms, hm.modelCode 
			FROM hp_model hm 
				 INNER JOIN hp_goods hg ON hm.goodsCode = hg.goodsCode";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$telecoms  = $row[telecoms];
			$modelCode = $row[modelCode];

			$telecoms = explode(",", $telecoms);

			for ($i = 0; $i < count($telecoms); $i++) {
				$telecom = $telecoms[$i];

				for ($j = 0; $j < count($requestAssorts); $j++) {
					$requestAssort = $requestAssorts[$j];

					for ($m = 0; $m < count($installments); $m++) {
						$installment = $installments[$m];

						for ($n = 0; $n < count($discountTypes); $n++) {
							$discountType = $discountTypes[$n];

							$sql = "SELECT count(idx) as count 
									FROM hp_write_url 
									WHERE modelCode = '$modelCode' AND telecom = '$telecom' AND requestAssort = '$requestAssort' AND installment = '$installment' AND discountType = '$discountType'";
							$result2 = $connect->query($sql);
							$row2 = mysqli_fetch_object($result2);

							if ($row2->count == 0) {
								$sql = "INSERT INTO hp_write_url (modelCode, telecom, requestAssort, installment, discountType)
														  VALUES ('$modelCode', '$telecom', '$requestAssort', '$installment', '$discountType')";
								$connect->query($sql);
							}
						}
					}
				}
			}
		}
    }

	// 성공 결과를 반환합니다.
	$result_status = "0";
	$result_message = "업데이트를 완료했습니다.";

	$response = array(
		'result'  => $result_status,
		'message' => $result_message
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>