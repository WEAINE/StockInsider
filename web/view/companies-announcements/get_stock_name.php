<?php
	$code = $_GET['code'];
	if ($code == NULL || !is_numeric($code) || strlen($code) != 6) die('');

	switch (substr($code, 0, 3)) {
		case '000':
		case '001':
		case '002':
		case '300':
			$prefix = 'sz';
			break;
		case '600':
		case '601':
			$prefix = 'sh';
			break;
		default:
			die('');
	}

	$curl_handler = curl_init();
	curl_setopt($curl_handler, CURLOPT_URL, "http://hq.sinajs.cn/list=" . $prefix . $code);
	curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_handler, CURLOPT_HEADER, false);
	$retstr = mb_convert_encoding(curl_exec($curl_handler), 'UTF-8', 'GBK, GB2312, ASCII');
	curl_close($curl_handler);

	$exploded_ele = explode(',', $retstr);
	if (count($exploded_ele) == 1) die('');
	else echo substr($exploded_ele[0], 21);
?>
