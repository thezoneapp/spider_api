<?
	include "../../inc/common.php";
	include "../../inc/utility.php";

	/*
	* ���� ���� ����
	*/

    $sql = "SELECT code, content FROM setting WHERE assort = 'V' ORDER by code asc";
	$result = $connect->query($sql);

    if ($result->num_rows > 0) {
		while($row = mysqli_fetch_array($result)) {
			if ($row[code] == "subsA") $subsA = $row[content];
			else if ($row[code] == "subsS") $subsS = $row[content];
			else if ($row[code] == "payA") $payA = $row[content];
			else if ($row[code] == "payS") $payS = $row[content];
			else if ($row[code] == "commitS") $commitS = $row[content];
			else if ($row[code] == "commitR") $commitR = $row[content];
			else if ($row[code] == "commitMA") $commitMA = $row[content];
			else if ($row[code] == "commitMS") $commitMS = $row[content];
		}

		$data = array(
			'subsA'    => $subsA,
			'subsS'    => $subsS,
			'payA'     => $payA,
			'payS'     => $payS,
			'commitS'  => $commitS,
			'commitR'  => $commitR,
			'commitMA' => $commitMA,
			'commitMS' => $commitMS,
		);

		// ���� ����� ��ȯ�մϴ�.
		$result = "0";

    } else {
		// ���� ����� ��ȯ�մϴ�.
		$result = "1";
		$data = array();
	}

	$response = array(
		'result'    => $result,
		'data'      => $data
    );

    echo json_encode( $response );

    // db connection �� �ݰų�, connection pool �� �̿����̶�� ����� ������ ��ȯ�մϴ�.
    @mysqli_close($connect);
?>