<?
    /***********************************************************************************************************************
	* 다이렉트보험 (만기일자가 45일 이전인 정보 전송)
	* 1. 출금신청목록 > 출금상태 > 출금대기 검색
	* 2. 출금완료 > 수수료목록에 추가
	***********************************************************************************************************************/

	$db_host = "localhost";
	$db_user = "spiderfla";
	$db_pass = "dlfvkf#$12";
	$db_name = "spiderfla";

	$connect = @mysqli_connect($db_host, $db_user, $db_pass, $db_name) or die("Failed to connect to MySQL: " . mysqli_error()); 

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

	/**************************** 만료일자가 45일 이내인 신청서 목록 **********************************************************/
	$coCode = "c045cDhrc1cvcmNTU2FzZlViYm5PSGpjR2hxL2dLQWtHaHhVcmlPL3FNYz0%3D";

	//$url = "https://dev-api.chabot.kr:9499/api/tm_regist/";    // 개발서버
	$url = "https://api.chabot.kr/api/tm_regist/"; // 운영서버

	// header
	$header = Array(
		"Content-Type: application/json; charset=utf-8", 
	);	
	
	$today = date("Y-m-d", time());
	$expiredDate = date("Y-m-d", strtotime($today." +45 day"));

	$sql = "SELECT m.insuId, ir.idx, ir.seqNo, ir.memId, ir.memName, ir.memAssort, ir.insuAssort, ir.custName, ir.hpNo, ir.carNoType, ir.carNo, ir.expiredDate, ir.custRegion, ir.marketingFlag 
			FROM insu_request ir 
				 INNER JOIN member m ON ir.memId = m.memId 
			WHERE ir.expiredDate = '$expiredDate'";
	$result = $connect->query($sql);

	if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			$hpNo = $row[hpNo];

			if ($row[hpNo] != "") $row[hpNo] = aes_decode($row[hpNo]);

			if ($seqNo == "0") { // 입력한 계약구분
				$isInsert = "N";

				if ($row[insuAssort] == "N") $carType = "new_car";
				else $carType = "re_new_car";

			} else {
				$isInsert = "Y";
				$carType = "re_new_car"; // 갱신계약
			}

			$dealerCode    = $row[insuId];
			$idx           = $row[idx];
			$memId         = $row[memId];
			$memName       = $row[memName];
			$memAssort     = $row[memAssort];
			$insuAssort    = $row[insuAssort];
			$custName      = $row[custName];
			$custHpNo      = $row[hpNo];
			$carNoType     = $row[carNoType];
			$carNo         = $row[carNo];
			$expiredDate   = $row[expiredDate];
			$custRegion    = $row[custRegion];
			$marketingFlag = $row[marketingFlag];

			$body = Array(
				"coCode"         => $coCode, 
				"mode"           => "tm_regist", 
				"dealerCode"     => $dealerCode, 
				"customerName"   => $custName, 
				"customerMobile" => $custHpNo, 
				"carNoType"      => $carNoType, 
				"carNo"          => $carNo, 
				"carType"        => $carType, 
				"expiredDate"    => $expiredDate,
				"customerRegion" => $custRegion,
				"marketingFlag"  => $marketingFlag
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
			curl_setopt($ch, CURLOPT_ENCODING , "");
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); 

			$response = curl_exec($ch);

			curl_close($ch);

			$response = json_decode($response, true);
			//print_r( $response );

			if ($response[status] == "200") {
				$seqNo = $response[sid];

				if ($isInsert == "Y") {
					$sql = "INSERT INTO insu_request (memId, memName, memAssort, seqNo, carType, custName, hpNo, carNoType, carNo, expiredDate, custRegion, marketingAgree, marketingFlag, requestStatus, wdate) 
											  VALUES ('$memId', '$memName', '$memAssort', '$seqNo', '$carType', '$custName', '$hpNo', '$carNoType', '$carNo', '$expiredDate', '$custRegion', '$marketingAgree', '$marketingFlag', '0', now())";
					$connect->query($sql);

				} else {
					$sql = "UPDATE insu_request SET seqNo = '$seqNo' WHERE idx = '$idx'";
					$connect->query($sql);
				}
			}
		}
	}

    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);

	exit;
?>