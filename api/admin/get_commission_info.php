<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 수수료 정보
	* parameter ==> idx: 수수료에 해당하는 idx
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$idx = $input_data->{'idx'};

    $sql = "SELECT idx, sponsId, sponsName, memId, memName, assort, price, accurateStatus 
	        FROM commission 
			WHERE idx = '$idx'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		$assortName = selected_object($row->assort, $arrCommiAssort);
		$statusName = selected_object($row->accurateStatus, $arrAccurateStatus);

		$data = array(
			'idx'           => $row->idx,
			'sponsId'       => $row->sponsId,
			'sponsName'     => $row->sponsId,
			'memId'         => $row->memId,
			'memName'       => $row->memName,
			'assort'        => $row->assort,
			'assortName'    => $assortName,
			'assortOptions' => $arrCommiAssort,
			'price'         => $row->price,
			'status'        => $row->accurateStatus,
			'statusName'    => $statusName,
			'statusOptions' => $arrAccurateStatus,
		);

		// 업데이트모드로 결과를 반환합니다.
		$result = "0";

    } else {
		$data = array(
			'idx'           => '',
			'sponsId'       => '',
			'sponsName'     => '',
			'memId'         => '',
			'memName'       => '',
			'assort'        => '',
			'assortName'    => '',
			'assortOptions' => $arrCommiAssort,
			'price'         => '',
			'status'        => '',
			'statusName'    => '',
			'statusOptions' => $arrAccurateStatus,
		);

		// 추가모드로 결과를 반환합니다.
		$result = "1";
	}

	$response = array(
		'result'    => $result,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
