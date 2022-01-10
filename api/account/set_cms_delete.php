<?
	include "../../inc/common.php";
	include "../../inc/cms.php";
	include "../../inc/utility.php";

	$memId = "a23367777";
	$response = cmsDelete($memId);
print_r($response);
    // db connection 을 닫거나, connection pool 을 이용중이라면 사용한 세션을 반환합니다.
    @mysqli_close($connect);
?>