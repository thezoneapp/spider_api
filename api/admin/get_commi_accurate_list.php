<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* 수수료 정산서 목록
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

	if ($idx === null || $idx === "") $idx_sql = "";
	else $idx_sql = "and idx = '$idx' ";

	if ($accurateDate === null || $accurateDate === "") $date_sql = "";
	else $date_sql = "and wdate = '$accurateDate' ";

	if ($accurateStatus === null || $accurateStatus === "") $status_sql = "";
	else $status_sql = "and accurateStatus = '$accurateStatus' ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM commi_accurate WHERE idx > 0 $search_sql $idx_sql $date_sql $status_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT @a:=@a+1 no, idx, memId, memName, minDate, maxDate, commission, accurateAmount, accurateStatus, wdate 
	        FROM ( select idx, memId, memName, date_format(minDate, '%m-%d') as minDate, date_format(maxDate, '%m-%d') as maxDate, commission, accurateAmount, accurateStatus, wdate 
		           from commi_accurate 
		           where idx > 0 $search_sql $idx_sql $date_sql $status_sql 
		   		   order by idx desc 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$accurateDate = $row[minDate] . " ~ " . $row[maxDate];
			$status = selected_object($row[accurateStatus], $arrAccurateStatus);
			
			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
			    'memId'          => $row[memId],
				'memName'        => $row[memName],
				'accurateDate'   => $accurateDate,
				'commission'     => number_format($row[commission]),
				'accurateAmount' => number_format($row[accurateAmount]),
				'accurateStatus' => $status,
				'wdate'          => $row[wdate],
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
		'statusOptions'   => $arrAccurateStatus,
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
