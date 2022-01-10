<?php
//*********************************************************************
//**********          암호화/복호화            **************************
//*********************************************************************
$key = "eoqkr!#18@$";
$key = substr(hash('sha256', $key), 0, 32);
$key = pack('H*', hash('sha256', $key));

// AES128/ECB/PKCS5Padding 암호화
function aes128encrypt($data) {
    global $key;

	$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
	$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

    mcrypt_generic_init($td, $key, $iv);

    $result = mcrypt_generic($td, pkcs5_pad($data, $size));

    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    return base64_encode($result);
}

// AES128/ECB/PKCS5Padding 복호화
function aes128decrypt($data) {
    global $key;
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

    mcrypt_generic_init($td, $key, $iv);

    $decrypted_text = mdecrypt_generic($td, base64_decode($data));

    $rt = $decrypted_text;

    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    return pkcs5_unpad($rt);
}

// PKCS5Padding 인코딩
function pkcs5_pad($text, $blocksize) {
    $pad = $blocksize - (strlen($text) % $blocksize);

    return $text . str_repeat(chr($pad), $pad);
}

// PKCS5Padding 디코딩
function pkcs5_unpad($text) { 
    $pad = ord($text{strlen($text)-1}); 

    if ($pad > strlen($text)) return false; 

    if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false; 

    return substr($text, 0, -1 * $pad); 
}

// 디코딩 결과값 리턴
function aes_decode($data) {
	if (empty($data)) $data = "";
	else $data = aes128decrypt($data);

	return $data;
}

//*********************************************************************
//**********   배열요소에 '전체' 추가  **************************
//*********************************************************************
function array_all_add($arrArg) {
	$newArray = array();

	$data_info = array(
		'code'    => '',
		'name'    => '전체'
	);
	array_push($newArray, $data_info);

    foreach ($arrArg as $arg) {
		$data_info = array(
			'code'    => $arg[code],
			'name'    => $arg[name]
		);
		array_push($newArray, $data_info);
    }

	return $newArray;
}

//*********************************************************************
//**********   배열요소에 '전체, 카운트' 추가  **************************
//*********************************************************************
function array_all_add_count($arrArg, $arrCount, $sumCount) {
	$newArray = array();

	$data_info = array(
		'code'    => '',
		'name'    => '전체[' . number_format($sumCount) . ']'
	);
	array_push($newArray, $data_info);

    foreach ($arrArg as $arg) {
		$statusName = "";

		for ($i = 0; count($arrCount) > $i; $i++) {
			$row = $arrCount[$i];

			if ($arg[code] == $row[statusName]) {
				$statusName = $arg[name] . "[" . number_format($row[statusCount]) . "]";
				$i = count($arrCount);
			}
		}

		if ($statusName == "") $statusName = $arg[name] . "[0]";

		$data_info = array(
			'code'    => $arg[code],
			'name'    => $statusName
		);
		array_push($newArray, $data_info);
    }

	return $newArray;
}

//*********************************************************************
//**********   배열요소에 '카운트' 추가  **************************
//*********************************************************************
function array_add_count($arrArg, $arrCount, $sumCount) {
	$newArray = array();

	$data_info = array(
		'code'    => '',
		'name'    => '전체',
		'count'   => number_format($sumCount)
	);
	//array_push($newArray, $data_info);

    foreach ($arrArg as $arg) {
		$count = 0;

		for ($i = 0; count($arrCount) > $i; $i++) {
			$row = $arrCount[$i];

			if ($arg[code] == $row[statusName]) {
				$count = number_format($row[statusCount]);
				$i = count($arrCount);
			}
		}

		$data_info = array(
			'code'    => $arg[code],
			'name'    => $arg[name],
			'count'   => $count,
		);
		array_push($newArray, $data_info);
    }

	return $newArray;
}

/***************************************************************************************************
 *                               년도 배열                                                          *
 ***************************************************************************************************/
function getYearOptions() {
	// 년도
	$year = date("Y");
	$yearOptions = array();

	$data_info = array(
		'code' => "",
		'name' => "전체년도",
	);
	array_push($yearOptions, $data_info);

	for ($i = 2020; $i <= $year; $i++) {
		$data_info = array(
			'code' => $i,
			'name' => $i,
		);
		array_push($yearOptions, $data_info);
	}

	return $yearOptions;
}

//*********************************************************************
//**********          효성 CMS               **************************
//*********************************************************************
$managerId = "nbbang18";
$CUST_ID   = "nbbang18";
$SW_KEY    = "sldcgtgYKYQ03R2W";
$CUST_KEY  = "7tPKQGphGwFLD5TJ";

// 테스트용
//"https://add-test.hyosungcms.co.kr/v1/custs/$CUST_ID/agreements",
//$url = "https://api.efnc.co.kr:1443/v1/"; // 포트번호가 없으면 실서버;
//$managerId = "sdsitest";
//$CUST_ID   = "sdsitest";
//$SW_KEY = "4LjFflzr6z4YSknp";
//$CUST_KEY = "BT2z4D5DUm7cE5tl";

//*********************************************************************
//**********   원하는 값과 일치하는 배열값 리턴  **************************
//*********************************************************************
function selected_array($input_value, $arrayName, $arrayValue) {
    $selected_value = "";

    for ($n = 0; $n < count($arrayValue); $n++) {
        if ($input_value == $arrayValue[$n]) $selected_value = $arrayName[$n];
    }

    return $selected_value;
}

function selected_object($input_value, $arrayName) {
    $name = "";

    for ($i = 0; $i < count($arrayName); $i++) {
		$arrObj = new ArrayObject($arrayName[$i]);
		$arrObj->setFlags(ArrayObject::ARRAY_AS_PROPS);

        if ($input_value == $arrObj->code) $name = $arrObj->name;
    }

    return $name;
}

//*********************************************************************
//**********               File Upload       **************************
//*********************************************************************
function fileUpload($path, $file) {
	$response = array();

	try {
		if (!isset($_FILES[$file]['error']) || is_array($_FILES[$file]['error']) ) {
			throw new RuntimeException('Invalid parameters.');
		}

		// Check $_FILES value.
		switch ($_FILES[$file]['error']) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				throw new RuntimeException('No file sent.');
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				throw new RuntimeException('Exceeded filesize limit.');
			default:
				throw new RuntimeException('Unknown errors.');
		}

		// check filesize. 
		if ($_FILES[$file]['size'] > 3000000) {
			throw new RuntimeException('파일 싸이즈가 3MB를 초과하였습니다.');
		}

		// Check MIME Type.
		$finfo = new finfo(FILEINFO_MIME_TYPE);

		if (false === $ext = array_search(
			$finfo->file($_FILES[$file]['tmp_name']),
			array(
				'jpg' => 'image/jpeg',
				'png' => 'image/png',
				'gif' => 'image/gif',
				'pdf' => 'application/pdf'
			),
			true
		)) {
			throw new RuntimeException('jpg/png/gif만 가능합니다');
		}

		// 임시 파일을 upload폴더로 이동
		$fileName = $_FILES[$file]['name'];
		$fileTmp  = $_FILES[$file]['tmp_name'];
		$fileType = $_FILES[$file]['type'];
		$fileSize = $_FILES[$file]['size'];
		$fileExt  = strtolower(end(explode('.', $_FILES[$file]['name'])));

		$fileName = time() . "_" . $fileName ;
		$saveFileName = $path . $fileName;

		if (!move_uploaded_file($fileTmp, $saveFileName)) {
			throw new RuntimeException($fileTmp . $file);
		}

		$response = array(
			"result"  => "0",
			"message" => $fileName
		);

		return $response;

	} catch (RuntimeException $e) {
		$response = array(
			"result" => "1",
			"message" => $e->getMessage()
		);

		return $response;
	}
}

//*********************************************************************
//**********           LOS MAP 재귀함수       **************************
//*********************************************************************
function make_map($nodes, $memStatus, $month, $searchValue) {
	global $connect;
	global $arrMemAssort;

	$data = array();

	if ($nodes[depthCnt] > 0) {
		if ($memStatus == null || $memStatus == "") $memStatus_sql = "";
		else {
			if ($memStatus == "0") $memStatus_sql = "and memStatus = '9' ";
			else $memStatus_sql = "and memStatus != '9' ";
		}

		if ($month == null || $month == "") $month_sql = "";
		else {
			if ($month == "C") $month_sql = "and date_format(wdate, '%Y-%m') = date_format(NOW(), '%Y-%m') ";
			else $month_sql = "and date_format(wdate, '%Y-%m') = date_format(DATE_SUB(  curdate(),  INTERVAL 1 MONTH  ), '%Y-%m') ";
		}

		if ($searchValue == null || $searchValue == "") $search_sql = "";
		else $search_sql = "and (memName like '%$searchValue%' or memId like '%$searchValue%') ";

		$sql = "SELECT m.idx, m.memId, m.memName, m.memAssort, ifnull(c.childCnt,0) as childCnt  
	            FROM member m 
		             left outer join ( select sponsId, count(idx) as childCnt 
					                   from member 
									   where idx > 0 $memStatus_sql $month_sql 
					                   group by sponsId 
					                 ) c ON m.memId = c.sponsId 
	            WHERE m.sponsId = '" . $nodes[memId] . "' $memStatus_sql $month_sql $search_sql 
				ORDER BY memName ASC";
		$result = $connect->query($sql);

	    if ($result->num_rows > 0) {
			while($row = mysqli_fetch_array($result)) {
				$memAssort = selected_object($row[memAssort], $arrMemAssort);

				$personPoint = 0;
				$depthPoint = 0;
				$sumPoint = 0;

				$personPoint = 0;
				$depthPoint = 0;
				$sumPoint = 0;

				$sql = "SELECT SUM(depthPrice) AS depthPoint, SUM(personPrice) AS personPoint 
						FROM ( SELECT if (assort = 'CS' OR assort = 'MA' OR assort = 'MS' OR assort = 'R2', price, 0) AS depthPrice, 
									  if (assort = 'R1' OR assort = 'P1' OR assort = 'A1', price, 0) AS personPrice 
							   FROM commission
							   WHERE sponsId = '" . $row[memId] . "' $month_sql $search_sql 
							 ) t";
				$result2 = $connect->query($sql);
				$row2 = mysqli_fetch_object($result2);

				$personPoint = $row2->personPoint;
				$depthPoint = $row2->depthPoint;
				$sumPoint = $personPoint + $depthPoint;

				$nodes_info = array(
					'id'          => $row[idx],
					'memId'       => $row[memId],
					'memName'     => $row[memName],
					'memAssort'   => $memAssort,
					'depthCnt'    => $row[childCnt],
					'personPoint' => number_format($personPoint),
					'depthPoint'  => number_format($depthPoint),
					'sumPoint'    => number_format($sumPoint),
				);

				if ($nodes_info[depthCnt] > 0) {
					$nodes_info[employees] = make_map($nodes_info, $memStatus, $month, "");
				} else {
					$nodes_info[employees] = null;
				}

				array_push($data, $nodes_info);
			}
		}

		return $data;
	}
}
?>