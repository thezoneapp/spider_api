<?
	include "../../inc/common.php";
	include "../../inc/array.php";
	include "../../inc/utility.php";
    
	/*
	* 가입구분 및 약관 정보
	*/

	// CMS 납부비용 정보
	$payA = 0;
	$payS = 0;
	$sql = "SELECT code, content FROM setting WHERE code in ('payA', 'payS')";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[code] == "payA") $payA = number_format($row[content]);
			else $payS = number_format($row[content]);
		}
	}

	$cms_info = array(
		'cms_m' => $payA,
		'cms_s' => $payS,
	);

	// 회원구분 설명 및 이용약관
	$explain_m   = "";
	$explain_s   = "";
	$cms_term    = "";
	$person_term = "";
    $sql = "SELECT code, content FROM setting WHERE code in('join_m_explain','join_s_explain','cms_agree','term_02')";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[code] == "join_m_explain") $explain_m = $row[content];
			else if ($row[code] == "join_s_explain") $explain_s = $row[content];
			else if ($row[code] == "cms_agree") $cms_term = $row[content];
			else if ($row[code] == "term_02") $person_term = $row[content];
		}
	}

	// 회원구분 설명
	$explain_info = array(
		'explain_m' => $explain_m,
		'explain_s' => $explain_s,
	);

	// 이용약관
	$term_info = array(
		'cms_term'    => $cms_term,
		'person_term' => $person_term,
	);

	$data = array(
		'joinAssort'    => $arrMemAssort4,
		'assortExplain' => $explain_info,
		'cms_info'      => $cms_info,
		'term_info'     => $term_info,
		'bank_info'     => $arrBankCode,
	);

	$response = array(
		'result' => "0",
		'data'   => $data
    );

    echo json_encode( $response );

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>