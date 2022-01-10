<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 관리자 >제휴제안 > 목록
	* parameter
		page:           해당페이지
		rows:           페이지당 행의 갯수
		answerYn:       답변여부
		searchValue:    검색값
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$answerYn    = $input_data->{'answerYn'};
	$searchValue = trim($input_data->{'searchValue'});

	if (is_object($answerYn)) $answerYn = $answerYn->{'code'};

	$searchHpNo  = aes128encrypt($searchValue);

	// 검색조건
	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchValue == "") $search_sql = "";
	else $search_sql = "and (companyName like '%$searchValue%' or chargeName like '%$searchValue%' or hpNo like '%$searchHpNo%') ";

	if ($answerYn == null || $answerYn == "") $answerYn_sql = "";
	else $answerYn_sql = "and answerYn = '$answerYn'";

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM proposal WHERE idx > 0 $search_sql $answerYn_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, companyName, postCode, addr1, addr2, telNo, hpNo, email, chargeName, introduction, content, companyDoc, answerYn, wdate 
	        FROM ( select @a:=@a+1 no, idx, companyName, postCode, addr1, addr2, telNo, hpNo, email, chargeName, introduction, content, companyDoc, answerYn, date_format(wdate, '%Y-%m-%d') as wdate 
		           from proposal, (select @a:= 0) AS a 
		           where idx > 0 $search_sql $answerYn_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[telNo] != "") $row[telNo] = aes_decode($row[telNo]);
			if ($row[hpNo] != "") $row[hpNo] = aes_decode($row[hpNo]);
			if ($row[email] != "") $row[email] = aes_decode($row[email]);

			$answerYn = selected_object($row[answerYn], $arrProposalStatus);

			$data_info = array(
				'no'          => $row[no],
				'idx'         => $row[idx],
				'companyName' => $row[companyName],
				'postCode'    => $row[postCode],
				'addr1'       => $row[addr1],
				'addr2'       => $row[addr2],
				'telNo'       => $row[telNo],
				'hpNo'        => $row[hpNo],
				'email'       => $row[email],
				'chargeName'  => $row[chargeName],
				'answerYn'    => $answerYn,
				'wdate'       => $row[wdate],
				'isChecked'   => false,
			);
			array_push($data, $data_info);
		}

		// 성공 결과를 반환합니다.
		$result_status = "0";

    } else {
		// 실패 결과를 반환합니다.
		$result_status = "1";
	}

	$response = array(
		'result'        => $result_status,
		'rowTotal'      => $total,
		'pageCount'     => $pageCount,
		'data'          => $data,
		'answerOptions' => array_all_add($arrProposalStatus),
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
