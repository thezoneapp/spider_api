<?
	include "../../../inc/common.php";
	include "../../../inc/array.php";
	include "../../../utils/utility.php";


		// 회원가입 API 호출
		//$url = "http://spiderplatform.co.kr/api/partner/get_token.php";
		$url = "http://spiderplatform.co.kr/api/partner/insu/set_status_update.php";

		$headers = Array(
			'OPERA-TOKEN: AwLbydK4HGlEmVDNYxCCxIwDWSGeGYmQyqwBWI2inM4=',
			'Content-Type: application/json'
		);
		//$fields = Array(
		//	"comId" => "addinsu",
		//	"comPw" => "Kro0e0yEwRd8mxgJagFXhQ=="
		//);

		$data = array();
		$data_info = Array(
			"seqNo"    => "1020901",
			"status"   => "8",
			"insurFee" => "800000",
		);
		array_push($data, $data_info);

		$data_info = Array(
			"seqNo"    => "1020901",
			"status"   => "8",
			"insurFee" => "800000",
		);
		array_push($data, $data_info);

		$fields = Array(
			"comId"     => "spider",
			"fieldData" => $data
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
		curl_setopt($ch, CURLOPT_ENCODING , "");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields)); 

		$response = curl_exec($ch);
		$err = curl_error($ch);

		curl_close($ch);

		if ($err) {
			echo "curl Error #:" . $err;
		}
print_r ($response);

?>