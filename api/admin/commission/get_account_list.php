<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 수수료대금 목록
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> searchKey:      검색항목
	* parameter ==> searchValue:    검색값
	* parameter ==> assort:         구분
	* parameter ==> accountDate:    입금일자
	* parameter ==> minDate:        등록일자-최소일자
	* parameter ==> maxDate:        등록일자-최대일자
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$searchValue = trim($input_data->{'searchValue'});
	$assort      = $input_data->{'assort'};
	$accountDate = $input_data->{'accountDate'};
	$minDate     = $input_data->{'minDate'};
	$maxDate     = $input_data->{'maxDate'};

	$assort = $assort->{'code'};

	if ($page === null) $page = 1;
	if ($rows === null) $rows = 20;

	if ($searchValue !== "") $search_sql = "and (memId like '%$searchValue%' or memName like '%$searchValue%') ";
	else $search_sql = "";

	if ($assort === null || $assort === "") $assort_sql = "";
	else $assort_sql = "and assort = '$assort' ";

	if ($accountDate === null || $accountDate === "") $accountDate_sql = "";
	else $accountDate_sql = "and accountDate = '$accountDate' ";

	if ($maxDate === null || $maxDate=== "") $registDate_sql = "";
	else $registDate_sql = "and (wdate >= '$minDate' and wdate <= '$maxDate') ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM commi_account WHERE idx > 0 $search_sql $assort_sql $accountDate_sql $registDate_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;
	$no = $total - $start;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT @a:=@a+1 no, idx, memId, memName, assort, accurateIdx, accurateDate, accountAmount, accountDate, bankName, accountNo, accountName, wdate 
	        FROM ( select idx, memId, memName, assort, accurateIdx, accurateDate, accountAmount, accountDate, bankName, accountNo, accountName, wdate 
		           from commi_account 
		           where idx > 0 $search_sql $assort_sql $accountDate_sql $registDate_sql 
		   		   order by idx desc 
		         ) m, (select @a:= 0) as a 
		    ORDER BY no DESC 
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$assortName = selected_object($row[assort], $arrAccountAssort);
			
			if ($row[assort] == "A") {
				$accurateAmount = number_format($row[accountAmount]);
				$accountAmount = "";
			} else {
				$accurateAmount = "";
				$accountAmount = number_format($row[accountAmount]);
			}

			$data_info = array(
				'no'             => $row[no],
				'idx'            => $row[idx],
			    'memId'          => $row[memId],
				'memName'        => $row[memName],
				'assort'         => $row[assort],
				'assortName'     => $assortName,
				'accurateIdx'    => $row[accurateIdx],
				'accurateDate'   => $row[accurateDate],
				'accurateAmount' => $accurateAmount,
				'accountDate'    => $row[accountDate],
				'accountAmount'  => $accountAmount,
				'bankName'       => $row[bankName],
				'accountNo'      => $row[accountNo],
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
		'result'        => $result,
		'rowTotal'      => $total,
		'pageCount'     => $pageCount,
		'assortOptions' => $arrAccountAssort,
		'data'          => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
