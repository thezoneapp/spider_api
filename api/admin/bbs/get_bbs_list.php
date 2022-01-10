<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../inc/utility.php";

	/*
	* 게시글 목록
	* parameter ==> userId:         조회자ID
	* parameter ==> bbsCode:        게시판코드
	* parameter ==> page:           해당페이지
	* parameter ==> rows:           페이지당 행의 갯수
	* parameter ==> bbsCode:        게시판 코드
	* parameter ==> searchValue:    검색값
	*/
	$input_data  = json_decode(file_get_contents('php://input'));
	$userId      = $input_data->{'userId'};
	$page        = $input_data->{'page'};
	$rows        = $input_data->{'rows'};
	$bbsCode     = $input_data->{'bbsCode'};
	$assortCode  = $input_data->{'assortCode'};
	$answerYn    = $input_data->{'answerYn'};
	$searchValue = trim($input_data->{'searchValue'});

	if (is_object($assortCode)) $assortCode = $assortCode->{'code'};
	if (is_object($answerYn)) $answerYn = $answerYn->{'code'};

    $sql = "SELECT auth 
			FROM ( SELECT id, auth FROM admin
                   union 
                   SELECT memId as id, memAssort AS auth FROM member
				 ) m 
			WHERE id = '$userId'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);
		$authAssort = $row->auth; // 관리자 또는 회원

	} else {
		$authAssort = "N"; // 비회원
	}

	// 게시판 정보
	$sql = "SELECT authAdmin, authPartner, authMember, authNone
			FROM bbs_manager 
			WHERE bbsCode = '$bbsCode'";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		$row = mysqli_fetch_object($result);

		if ($authAssort == "A") $arrAuth = explode(",", $row->authAdmin);
		else if ($authAssort == "P") $arrAuth = explode(",", $row->authPartner);
		else if ($authAssort == "M" || $authAssort == "S") $arrAuth = explode(",", $row->authMember);
		else if ($authAssort == "N") $arrAuth = explode(",", $row->authNone);

		if ($arrAuth[0] == "Y") $authWrite = true;
		else $authWrite = false;

		if ($arrAuth[2] == "Y") $authDelete = true;
		else $authDelete = false;

	} else {
		// 관리자이면 쓰기/삭제권한
		if ($authAssort == "A") {
			$authWrite = true;
			$authDelete = true;
		} else {
			$authWrite = false;
			$authDelete = false;
		}
	}

	// 검색조건
	if ($page == null) $page = 1;
	if ($rows == null) $rows = 20;

	if ($searchValue !== "") $search_sql = "and (memId like '%$searchValue%' or memName like '%$searchValue%' or subject like '%$searchValue%') ";
	else $search_sql = "";

	if ($bbsCode == null || $bbsCode == "") $bbsCode_sql = "";
	else $bbsCode_sql = "and bbsCode = '$bbsCode' ";

	if ($assortCode == null || $assortCode == "") $assortCode_sql = "";
	else $assortCode_sql = "and assortCode = '$assortCode' ";

	if ($answerYn == null || $answerYn == "") $answerYn_sql = "";
	else {
		if ($answerYn == "1") $answerYn_sql = "and replyCount > 0 ";
		else $answerYn_sql = "and replyCount = 0 ";
	}

	// 전체 데이타 갯수
    $sql = "SELECT idx FROM bbs WHERE parentIdx = 0 $search_sql $bbsCode_sql $assortCode_sql $answerYn_sql";
	$result = $connect->query($sql);
	$total = $result->num_rows;

	$pageCount = ceil($total / $rows);
	if ($page < 1 || $page > $pageCount) $page = 1;
	$start = ($page - 1) * $rows;

	// 조건에 맞는 데이타 검색 
	$data = array();
    $sql = "SELECT no, idx, memId, memName, hpNo, email, assortCode, subject, thumbnail, replyStatus, replyCount, viewCount, score, wdate 
	        FROM ( select @a:=@a+1 no, idx, memId, memName, hpNo, email, assortCode, subject, thumbnail, replyStatus, replyCount, viewCount, score, date_format(wdate, '%Y-%m-%d') as wdate 
		           from bbs, (select @a:= 0) AS a 
		           where parentIdx = 0 $search_sql $bbsCode_sql $assortCode_sql $answerYn_sql 
		         ) m 
			ORDER BY no DESC
			LIMIT $start, $rows";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$assortCode = $row[assortCode];

			if ($row[hpNo] != "") $row[hpNo] = aes_decode($row[hpNo]);
			if ($row[email] != "") $row[email] = aes_decode($row[email]);

			if ($row[replyCount] > 0) $replyStatus = "답변완료";
			else $replyStatus = "접수";

			// 분류 정보
			$assortName = "";
			$sql = "SELECT assortName FROM bbs_assort WHERE assortCode = '$assortCode'";
			$result2 = $connect->query($sql);

			if ($result2->num_rows > 0) {
				$row2 = mysqli_fetch_object($result2);
				$assortName = $row2->assortName;
			}

			$data_info = array(
				'no'          => $row[no],
				'idx'         => $row[idx],
				'memId'       => $row[memId],
				'memName'     => $row[memName],
				'hpNo'        => $row[hpNo],
				'email'        => $row[email],
				'assortName'  => $assortName,
				'subject'     => $row[subject],
				'thumbnail'   => $row[thumbnail],
				'replyStatus' => $replyStatus,
				'replyCount'  => $row[replyCount],
				'viewCount'   => $row[viewCount],
				'score'       => $row[score],
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

	// 게시판구분
	$bbsOptions = array();
    $sql = "SELECT bbsCode, title FROM bbs_manager";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$code_info = array(
				'code'    => $row[bbsCode],
				'name'    => $row[title],
			);
			array_push($bbsOptions, $code_info);
		}
	}

	// 대분류옵션
	$assortOptions = array();
	$sql = "SELECT bbsCode, assortCode, assortName FROM bbs_assort WHERE depthNo = '1'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$bbsCode = $row[bbsCode];
			$code    = $row[assortCode];
			$name    = $row[assortName];

			$assort_info = array(
				'bbsCode' => $bbsCode,
				'code'    => $code,
				'name'    => $name,
			);
			array_push($assortOptions, $assort_info);
		}
	}

	// 검색항목
	$searchOptions = array(
		['code' => 'memName', 'name' => '회원명'],
		['code' => 'memId',   'name' => '아이디'],
		['code' => 'subject', 'name' => '제목'],
	);

	// 답변여부옵션
	$answerOptions = array(
		['code' => '0', 'name' => '접수'],
		['code' => '1', 'name' => '답변완료'],
	);

	$response = array(
		'result'        => $result_status,
		'rowTotal'      => $total,
		'pageCount'     => $pageCount,
		'searchOptions' => $searchOptions,
		'answerOptions' => array_all_add($answerOptions),
		'assortOptions' => $assortOptions,
		'bbsOptions'    => $bbsOptions,
		'data'          => $data,
		'authWrite'     => $authWrite,
		'authDelete'    => $authDelete,
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>
