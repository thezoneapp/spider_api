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
function make_map($nodes) {
	global $connect;

	$data = array();

	if ($nodes[childCnt] > 0) {
		$sql = "SELECT m.idx, m.memId, m.memName, m.memAssort, ifnull(c.childCnt,0) as childCnt  
	            FROM member m 
		             left outer join ( select sponsId, count(idx) as childCnt 
					                   from member 
					                   group by sponsId 
					                 ) c ON m.memId = c.sponsId 
	            WHERE m.sponsId = '" . $nodes[memId] . "' 
				ORDER BY memName ASC";
		$result = $connect->query($sql);

	    if ($result->num_rows > 0) {
			while($row = mysqli_fetch_array($result)) {
				$memAssort = $row[memAssort] == "A" ? "" : "(판) ";

				$nodes_info = array(
					'idx'       => $row[idx],
					'memId'     => $row[memId],
					'memName'   => $memAssort . $row[memName] . " / " . $row[memId] . " (" . $row[childCnt]. ")",
					'childCnt'  => $row[childCnt],
				);

				if ($nodes_info[childCnt] > 0) {
					$nodes_info[children] = make_map($nodes_info);
				} else {
					$nodes_info[children] = null;
				}

				array_push($data, $nodes_info);
			}
		}

		return $data;
	}
}

//*************************************************************************************************************
//**********                               알림톡                                     **************************
//*************************************************************************************************************

//**********  알림톡 생성   **************************
function sendTalk($templateCode, $receiptInfo) {
    global $connect;

    // 알림톡 템플릿 가져오기
    $sql = "SELECT content FROM sms_message WHERE code = '$templateCode'";
	$result = $connect->query($sql);
	$row = mysqli_fetch_object($result);

    $talkMessage = templateReplace($receiptInfo, $row->content);

    // 알림톡 전송
	postTALK($receiptInfo[receiptHpNo], $talkMessage, $templateCode);
}

//**********  템플릿 치환   **************************
function templateReplace($receiptInfo, $content) {
    $content = str_replace("{MEM_ID}",         $receiptInfo[memId],         $content);
    $content = str_replace("{MEM_PW}",         $receiptInfo[memPw],         $content);
    $content = str_replace("{MEM_NAME}",       $receiptInfo[memName],       $content);
    $content = str_replace("{MEM_HpNo}",       $receiptInfo[memHpNo],       $content);
    $content = str_replace("{MEM_HPNO}",       $receiptInfo[memHpNo],       $content);  // 회원휴대폰번호
    $content = str_replace("{JOIN_AMOUNT}",    $receiptInfo[joinAmount],    $content);
    $content = str_replace("{CMS_MESSAGE}",    $receiptInfo[cmsMessage],    $content);
    $content = str_replace("{CMS_AMOUNT}",     $receiptInfo[cmsAmount],     $content);
    $content = str_replace("{CUST_NAME}",      $receiptInfo[custName],      $content); // 고객명
    $content = str_replace("{CUST_HpNo}",      $receiptInfo[custHpNo],      $content); // 고객휴대폰번호
    $content = str_replace("{CUST_HPNO}",      $receiptInfo[custHpNo],      $content); // 고객휴대폰번호
    $content = str_replace("{CERTIFY_NO}",     $receiptInfo[certifyNo],     $content); // 인증번호
    $content = str_replace("{MODEL_NAME}",     $receiptInfo[modelName],     $content); // 휴대폰 모델
    $content = str_replace("{MODEL_COLOR}",    $receiptInfo[colorName],     $content); // 휴대폰 색상
    $content = str_replace("{USE_TELECOM}",    $receiptInfo[useTelecom],    $content); // 기존통신사
    $content = str_replace("{CHANGE_TELECOM}", $receiptInfo[changeTelecom], $content); // 이동통신사
    $content = str_replace("{CHARGE_NAME}",    $receiptInfo[chargeName],    $content); // 요금제
    $content = str_replace("{REQUEST_MEMO}",   $receiptInfo[requestMemo],   $content); // 신청자메모

    return $content;
}

/***************************************************************************************************
 *                               알림톡 전송 실행                                                    *
 ***************************************************************************************************/
function postTALK($receTel, $talkMessage, $templateCode) {
	$talkKey = "da21876ce059358738c709764922b861551b45d4";
	$userCode = "spiderfla";
	$deptcode = "WD-0EH-M4";

    $sendTel = "01051907770 ";
    $receTel = str_replace("-", "", $receTel);
    $receTel = "82" . floatval($receTel);

    $talkMessages = array(
        "message_id"    => "",
        "to"            => $receTel,
        "text"          => $talkMessage,
        "from"          => $sendTel,
        "template_code" => $templateCode,
        "re_send"       => "Y"
    );

    $arrTalkMessages = array($talkMessages);

    $curlPostData = array(
        "usercode"     => $userCode,
        "deptcode"     => $deptcode,
        "yellowid_key" => $talkKey,
        "messages"     => $arrTalkMessages
    );
 
    $jsonData = json_encode($curlPostData);
    $ch = curl_init('https://api.surem.com/alimtalk/v1/json');

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
        'Content-Length: '.strlen($jsonData)
	));
    curl_setopt($ch, CURLOPT_URL, 'https://api.surem.com/alimtalk/v1/json');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POST, 1); 
 
    // Send the request
    $talkResponse = curl_exec($ch);
	//print_r($response);
    // Check for errors
    if($talkResponse === FALSE){
        die(curl_error($ch));
    }

    // Decode the response
    //$responseData = json_decode($talkResponse, TRUE);
    
    //header('Content-type: text/html; charset=utf-8');
    // Print the date from the response
    //echo $responseData['code'];
    //echo $responseData['message'];
}
?>