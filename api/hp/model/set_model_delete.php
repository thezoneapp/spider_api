<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 모델 정보 삭제
	* parameter ==> idx: 모델 일련번호
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT modelCode FROM hp_model WHERE idx = '$idx'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$modelCode = $row->modelCode;

	// 상품 > 모델
	$sql = "DELETE FROM hp_goods_model WHERE modelCode = '$modelCode'";
	$connect->query($sql);

	// 모델 > 색상
	$sql = "DELETE FROM hp_model_color WHERE modelCode = '$modelCode'";
	$connect->query($sql);

	// 모델 > 용량
	$sql = "DELETE FROM hp_model_capacity WHERE modelCode = '$modelCode'";
	$connect->query($sql);

	// 모델
	$sql = "DELETE FROM hp_model WHERE idx = '$idx'";
	$connect->query($sql);

	$result_ok = "0";
	$result_message = "'삭제'되었습니다.";

	$response = array(
		'result'  => $result,
		'message' => $result_message
	);

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>