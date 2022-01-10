<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 미지급금 목록
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> searchKey:      검색항목
	* parameter ==> searchValue:    검색값
	* parameter ==> idx:            정산번호
	* parameter ==> accurateDate:   정산일자
	* parameter ==> accurateStatus: 정산상태
	*/
	$input_data     = json_decode(file_get_contents('php://input'));
	$page           = $input_data->{'page'};
	$rows           = $input_data->{'rows'};
	$searchValue    = trim($input_data->{'searchValue'});
	$idx            = $input_data->{'idx'};
	$accurateDate   = $input_data->{'accurateDate'};
	$accurateStatus = $input_data->{'accurateStatus'};

	$accurateStatus = $accurateStatus->{'code'};

	if ($page === null) $page = 1;
	if ($rows === null) $rows = 20;

	if ($searchValue !== "") $search_sql = "and (memId like '%$searchValue%' or memName like '%$searchValue%') ";
	else $search_sql = "";

	// 전체 데이타 갯수
    $sql = "SELECT memId, memName, plusAmount, minusAmount, balanceAmount 
            FROM ( select memId, memName, sum(plusAmount) AS plusAmount, sum(minusAmount) AS minusAmount, SUM(balanceAmount) AS balanceAmount 
                   from ( select memId, memName, plusAmount, minusAmount, (plusAmount + minusAmount) balanceAmount
                          from ( select memId, memName, if(assort = 'A', accountAmount, 0) AS plusAmount, if(assort = 'P', 0 - accountAmount, 0) AS minusAmount 
                                 from commi_account
								 where idx > 0 $search_sql 
                               ) t1
                        ) t2
                   group by memId
                 ) t3";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT memId, memName, plusAmount, minusAmount, balanceAmount 
            FROM ( select memId, memName, sum(plusAmount) AS plusAmount, sum(minusAmount) AS minusAmount, SUM(balanceAmount) AS balanceAmount 
                   from ( select memId, memName, plusAmount, minusAmount, (plusAmount + minusAmount) balanceAmount
                          from ( select memId, memName, if(assort = 'A', accountAmount, 0) AS plusAmount, if(assort = 'P', 0 - accountAmount, 0) AS minusAmount 
                                 from commi_account 
								 where idx > 0 $search_sql 
                               ) t1
                        ) t2
                   group by memId
                 ) t3
            ORDER BY memName ASC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$accurateDate = $row[minDate] . " ~ " . $row[maxDate];
			$status = selected_object($row[accurateStatus], $arrAccurateStatus);
			
			$data_info = array(
				'no'            => $no,
			    'memId'         => $row[memId],
				'memName'       => $row[memName],
				'plusAmount'    => number_format($row[plusAmount]),
				'minusAmount'   => number_format($row[minusAmount]),
				'balanceAmount' => number_format($row[balanceAmount]),
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
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
