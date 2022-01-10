<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 정산서 세부 목록
	* parameter ==> page: 해당페이지
	* parameter ==> rows: 페이지당 행의 갯수
	* parameter ==> idx:  정산서번호
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page = $input_data->{'page'};
	$rows = $input_data->{'rows'};
	$idx  = trim($input_data->{'idx'});

	if ($page === null) $page = 1;
	if ($rows === null) $rows = 20;

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM commi_accurate_detail WHERE accurateIdx = '$idx'";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT @a:=@a+1 no, idx, memId, memName, memAssort, assort, custName, price, commiDate 
	        FROM ( select idx, memId, memName, memAssort, assort, custName, price, commiDate 
		           from commi_accurate_detail 
		           where accurateIdx = '$idx' 
		         ) m, (select @a:= 0) as a 
			ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$memAssort  = selected_object($row[memAssort], $arrMemAssort);
			$assortName = selected_object($row[assort], $arrCommiAssort);

			$data_info = array(
				'no'        => $row[no],
				'idx'       => $row[idx],
				'memId'     => $row[memId],
				'memName'   => $row[memName],
				'memAssort' => $memAssort,
				'assort'    => $assortName,
				'custName'  => $row[custName],
				'price'     => number_format($row[price]),
				'commiDate' => $row[commiDate]
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result = "1";
	}

	$response = array(
		'result'          => $result,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>