<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 그룹관리 > 그룹 > API key
	* parameter
		groupCode: 그룹코드
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$groupCode  = $input_data->{'groupCode'};
//$groupCode = "spider";

	$data = array();

	for ($i = 0; count($arrApiAssort) > $i; $i++) {
		$item = $arrApiAssort[$i];

		$assortCode = $item['code'];
		$assortName = $item['name'];

		$sql = "SELECT idx, assortCode, apiKey, useYn FROM group_api_key WHERE groupCode = '$groupCode' and assortCode = '$assortCode'";
		$result = $connect->query($sql);

		if ($result->num_rows > 0) {
			$row = mysqli_fetch_object($result);
			$idx    = $row->idx;
			$apiKey = $row->apiKey;
			$useYn  = $row->useYn;

		} else {
			$idx    = 0;
			$apiKey = "";
			$useYn  = "";
		}

		$data_info = array(
			'idx'        => $idx,
			'assortCode' => $assortCode,
			'assortName' => $assortName,
			'apiKey'     => $apiKey,
			'useYn'      => $useYn,
		);
		array_push($data, $data_info);
	}


	// 최종 결과
	$response = array(
		'result'        => $result_status,
		'data'          => $data,
		'assortOptions' => $arrApiAssort,
		'useOptions'    => $arrUseAssort,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>