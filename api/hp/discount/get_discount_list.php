<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 휴대폰 할인요금정책 목록
	* parameter 
		page:           해당페이지
		rows:           페이지당 행의 갯수
		searchValue:    검색값
		discountType:   할인구분
		useYn:          사용여부
	*/
	$input_data   = json_decode(file_get_contents('php://input'));
	$page         = $input_data->{'page'};
	$rows         = $input_data->{'rows'};
	$searchValue  = trim($input_data->{'searchValue'});
	$discountType = $input_data->{'discountType'};
	$useYn        = $input_data->{'useYn'};

	//$searchKey    = $searchKey->{'code'};
	//$discountType = $discountType->{'code'};
	//$useYn        = $useYn->{'code'};

	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	// 할인구분
	$typeValue = getCheckedToString($discountType);
	// 사용여부
	$useYnValue = getCheckedToString($useYn);

	if ($searchValue != "") $search_sql = "and (discountCode like '%$searchValue%' or discountName like '%$searchValue%') ";
	else $search_sql = "";

	if ($typeValue == null || $typeValue == "") $type_sql = "";
	else $type_sql = "and discountType IN ($typeValue) ";

	if ($useYnValue == null || $useYnValue == "") $use_sql = "";
	else $use_sql = "and useYn IN ($useYnValue) ";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM hp_discount WHERE idx > 0 $search_sql $type_sql $use_sql ";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();

    $sql = "SELECT no, idx, discountCode, discountName, discountPrice, discountType, allYn, useYn 
	        FROM ( select @a:=@a+1 no, idx, discountCode, discountName, discountPrice, discountType, allYn, useYn 
		           from hp_discount, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $type_sql $use_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$typeName = selected_object($row[discountType], $arrDiscountType);
			$allYn = selected_object($row[allYn], $arrAllYnAssort);
			$useYn = selected_object($row[useYn], $arrUseAssort);

			$data_info = array(
				'no'            => $row[no],
				'idx'           => $row[idx],
				'discountCode'  => $row[discountCode],
				'discountName'  => $row[discountName],
				'discountPrice' => number_format($row[discountPrice]),
				'discountType'  => $typeName,
				'allYn'         => $allYn,
				'useYn'         => $useYn,
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_ok = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_ok = "1";
	}

	$response = array(
		'result'         => $result_ok,
		'rowTotal'       => $total,
		'pageCount'      => $pageCount,
		'typeOptions'    => array_all_add($arrDiscountType),
		'useOptions'     => array_all_add($arrUseAssort),
		'data'           => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
