<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";

	/*
	* CMS 출금신청 목록
	* parameter
		page:          해당페이지
		rows:          페이지당 행의 갯수
		memId:         회원아이디
		searchName:    검색값
		payStatus:     출금상태
		year:          년도
		month:         월
	*/
	$input_data    = json_decode(file_get_contents('php://input'));
	$page          = $input_data->{'page'};
	$rows          = $input_data->{'rows'};
	$memId         = $input_data->{'memId'};
	$searchValue   = trim($input_data->{'searchValue'});
	$payStatus     = $input_data->{'payStatus'};
	$payYear       = $input_data->{'year'};
	$payMonth      = $input_data->{'month'};

	$searchKey     = $searchKey->{'code'};
	$payStatus     = $payStatus->{'code'};

	//$memId = "a33368055";
	//$payYear = "2021";
	//$payMonth = "05";

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchValue == null || $searchValue == "") $search_sql = "";
	else $search_sql = "and (memId = '$searchValue' or memName = '$searchValue') ";

	if ($payStatus === null || $payStatus === "") $payStatus_sql = "";
	else {
		if ($payStatus == "9") $payStatus_sql = "and payStatus = '9' ";
		else $payStatus_sql = "and payStatus != '9' ";
	}

	if ($payYear == "" || $payMonth == "") $date_sql = "";
	else {
		$payMonth = $payYear . "-" . $payMonth;
		$date_sql = "and payMonth = '$payMonth' ";
	}

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM cms_pay WHERE sponsId = '$memId' $search_sql $payStatus_sql $date_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, memId, memName, memAssort, payMonth, payAmount, requestStatus, payStatus, wdate 
	        FROM ( select @a:=@a+1 no, memId, memName, memAssort, payMonth, payAmount, requestStatus, payStatus, date_format(wdate, '%Y-%m-%d') as wdate
		           from cms_pay, (select @a:= 0) AS a 
		           where sponsId = '$memId' $search_sql $payStatus_sql $date_sql 
		         ) t 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$memAssort = selected_object($row[memAssort], $arrMemAssort);

			if ($row[payStatus] == '9') $payStatusName = "납부";
			else $payStatusName = "미납";

			$data_info = array(
				'no'            => $row[no],
				'idx'           => $row[idx],
				'memId'         => $row[memId],
				'memName'       => $row[memName],
				'memAssort'     => $memAssort,
				'payMonth'      => $row[payMonth],
				'payAmount'     => number_format($row[payAmount]),
				'payStatus'     => $payStatusName,
				'wdate'         => $row[wdate],
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	// 검색조건별 합계
    $sql = "SELECT sum(payAmount) as sumAmount FROM cms_pay WHERE sponsId = '$memId' $search_sql $payStatus_sql $date_sql ";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);
	$sumAmount = $row->sumAmount;

	// 납부상태별 금액
	$payAmount = 0;
	$unPayAmount = 0;
    $sql = "SELECT payStatus, sum(payAMount) AS payAmount  
			FROM ( SELECT if(payStatus = '9', 'P', 'U') AS payStatus, payAMount
				   FROM cms_pay
				   WHERE sponsId = '$memId'
				 ) t
			GROUP BY payStatus";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[payStatus] == "P") $payAmount = $row[payAmount];
			else $unPayAmount = $row[payAmount];
		}
	}

	// 납부상태
	$payOptions = array(
		['code' => '',  'name' => '전체'],
		['code' => '1', 'name' => '미납'],
		['code' => '9', 'name' => '납부']
	);

	// 월
	$monthOptions = array();

	for ($i = 0; $i <= 12; $i++) {
		if ($i == 0) {
			$code = "";
			$name = "전체월";
		} else {
			$code = (String) $i + 100;
			$code = substr($code,1,2);
			$name = $code;
		}

		$data_info = array(
			'code' => $code,
			'name' => $name,
		);
		array_push($monthOptions, $data_info);
	}

	$response = array(
		'result'          => $result_status,
		'rowTotal'        => $total,
		'pageCount'       => $pageCount,
		'payOptions'      => $payOptions,
		'yearOptions'     => getYearOptions(),
		'monthOptions'    => $monthOptions,
		'payAmount'       => number_format($payAmount),
		'unPayAmount'     => number_format($unPayAmount),
		'sumAmount'       => number_format($sumAmount),
		'data'            => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>