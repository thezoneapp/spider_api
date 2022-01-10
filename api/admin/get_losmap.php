<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 레그 목록
	* parameter ==> sponsId: 검색할 스폰서ID
	*/
	$input_data = json_decode(file_get_contents('php://input'));
	$sponsId    = trim($input_data->{'sponsId'});

	if ($sponsId == "" || $sponsId == null) $sponsId = "dream";

	$data = array();

	$sql = "SELECT m.idx, m.memId, m.memName, m.memAssort, c.childCnt 
			FROM member m 
				INNER JOIN ( select sponsId, count(idx) as childCnt 
							 from member 
							 group by sponsId 
							) c ON m.memId = c.sponsId 
			WHERE m.memId = '$sponsId' and memStatus = '9'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

	$idx = $row->idx;
	$memId = $row->memId;
	$memName = $row->memName;
	$memAssort = $row->memAssort;
	$childCnt = $row->childCnt;

	$root = array(
		'idx'      => $idx,
		'memId'    => $memId,
		'memName'  => $memName . " / " . $memId . " (" . $childCnt. ")",
		'childCnt' => $childCnt
	);

	$root[children] = make_map($root);


	$response = array(
		'result'    => "0",
        'data'      => $root
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>