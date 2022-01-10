<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 > 사업자회원 목록
	* parameter:
		page:           해당페이지
		rows:           페이지당 행의 갯수
		searchValue:    검색값
		certifyStatus:  공인인증서등록여부
	*/
	$input_data    = json_decode(file_get_contents('php://input'));
	$page          = $input_data->{'page'};
	$rows          = $input_data->{'rows'};
	$searchValue   = trim($input_data->{'searchValue'});
	$certifyStatus = $input_data->{'certifyStatus'};

	//$certifyStatus = $certifyStatus->{'code'};

	if ($page === null) $page = 1;
	if ($rows === null) $rows = 20;

	// 인증여부
	$certifyValue = getCheckedToString($certifyStatus);

	if ($searchValue !== "") $search_sql = "and (tm.memId like '%$searchValue%' or m.memName like '%$searchValue%') ";
	else $search_sql = "";

	if ($certifyValue == null || $certifyValue == "") $certify_sql = "";
	else $certify_sql = "and certifyStatus IN ($certifyValue) ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM tax_member WHERE idx > 0 $search_sql $certify_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT @a:=@a+1 no, idx, memId, memName, corpNum, corpName, ceoName, certifyStatus, wdate 
	        FROM ( select tm.idx, tm.memId, m.memName, tm.corpNum, tm.corpName, tm.ceoName, tm.certifyStatus, date_format(tm.wdate, '%Y/%m/%d') as wdate 
		           from tax_member tm 
						inner join member m on tm.memId = m.memId 
						where tm.idx > 0 $search_sql $certify_sql 
		   		   order by tm.idx DESC 
		         ) t, (select @a:= 0) as a 
		    ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$certifyName = selected_object($row[certifyStatus], $arrYesNo);

			$data_info = array(
				'no'            => $row[no],
				'idx'           => $row[idx],
			    'memId'         => $row[memId],
				'memName'       => $row[memName],
				'corpNum'       => $row[corpNum],
				'corpName'      => $row[corpName],
				'certifyStatus' => $certifyName,
				'wdate'         => $row[wdate],
				'isChecked'     => false,
			);
			array_push($data, $data_info);
			$no--;
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
		'certifyOptions'  => array_all_add($arrYesNo),
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
