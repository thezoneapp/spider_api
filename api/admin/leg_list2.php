<?
	include "../../../inc/common.php";
	include "../../../inc/utility.php";

	/*
	* 하위 대리점 레그 목록
	* parameter ==> page:         해당페이지
	* parameter ==> rows:         페이지당 행의 갯수
	* parameter ==> memId:     검색항목
	*/
	$post_data  = json_decode(file_get_contents('php://input'));
	$memId = $post_data->{'memId'};

	$spons_sql = "and sponsId = '$memId' ";

	$sql = "SELECT leg FROM member WHERE memId = '$memId'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$leg = $row->leg;
	$leg2 = $row->leg + 1;
	$leg3 = $row->leg + 2;

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM member WHERE leg = '$leg' $spons_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$page_count = ceil($total / $rows);
	if ($page < 1 || $page > $page_count) $page = 1;
	$start = ($page - 1) * $rows;

	$data = array();

	// 조건에 맞는 데이타 검색 
    $sql = "SELECT idx, memId, memName, memAssort 
	        FROM member 
			WHERE leg = '$leg2' $spons_sql 
			ORDER BY memName ASC";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$memAssort = $row[memAssort] == "A" ? "" : "(판) ";

			// 자신이 스폰하고 있는 영업점/대리점 검색 
			$sql = "SELECT idx, memId, memName, memAssort FROM member WHERE leg = '$leg3' and sponsId = '$row[memId]' ORDER by memName ASC";
			$result2 = $connect->query($sql);

			$data_info = array(
				'idx'          => $row[idx],
				'memId'     => $row[memId],
				'memName'   => $memAssort . $row[memName] . " / " . $row[memId],
				'sponsData'    => $result2->num_rows
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
		'result'    => $result,
        'data'      => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>