<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 그룹 > API key > 저장
	* parameter
		groupCode: 그룹코드
		data:      data 배열
	*/
	$back_data = json_decode(file_get_contents('php://input'));
	$groupCode  = $back_data->{'groupCode'};
	$arrData    = $back_data->{'data'};

	if (count($arrData) > 0) {
		for($i = 0; count($arrData) > $i; $i++) {
			$row = $arrData[$i];

			$idx        = $row->idx;
			$assortCode = $row->assortCode;
			$apiKey     = $row->apiKey;
			$useYn      = $row->useYn;

			//$useYn      = $useYn->{'code'};

			if ($idx == 0) {
				$sql = "INSERT INTO group_api_key (groupCode, assortCode, apiKey, useYn) 
				                           VALUES ('$groupCode', '$assortCode', '$apiKey', '$useYn')";
				$connect->query($sql);

			} else {
				$sql = "UPDATE group_api_key SET apiKey = '$apiKey', 
												 useYn = '$useYn' 
							WHERE idx = '$idx'";
				$connect->query($sql);
			}
		}
	}

	// 결과 리턴
	$response = array(
		'result'  => "0",
		'message' => "저장하였습니다."
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>