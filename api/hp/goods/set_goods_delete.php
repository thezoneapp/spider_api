<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 > 상품 정보 삭제
	* parameter ==> idx: 모델 일련번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$arrIdx = $input_data->{'idx'};

	$idx = "";
	for ($i = 0; count($arrIdx) > $i; $i++) {
		if ($idx != "") $idx .= ",";

		$idx .= "'" . $arrIdx[$i] . "'";
	}

    $sql = "SELECT goodsCode FROM hp_goods WHERE idx IN ($idx)";
	$result = $connect->query($sql);

	while($row = mysqli_fetch_array($result)) {
		$goodsCode = $row[goodsCode];
        //@unlink($thumbnail);

		$sql = "SELECT modelCode FROM hp_model WHERE goodsCode = '$goodsCode'";
		$result2 = $connect->query($sql);
		$row2 = mysqli_fetch_object($result2);

		$modelCode = $row2->modelCode;

		// 모델 > 용량
		$sql = "DELETE FROM hp_model_capacity WHERE modelCode = '$modelCode'";
		$connect->query($sql);

		// 모델 > 색상
		$sql = "DELETE FROM hp_model_color WHERE modelCode = '$modelCode'";
		$connect->query($sql);

		// 모델
		$sql = "DELETE FROM hp_model WHERE modelCode = '$modelCode'";
		$connect->query($sql);

		// 상품
		$sql = "DELETE FROM hp_goods WHERE goodsCode = '$goodsCode'";
		$connect->query($sql);
	}

	// 성공 결과를 반환합니다.
	$response = array(
		'result'    => "0",
		'message'   => "삭제하였습니다."
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>