<?php
	include '../../public/dom_functions.php';

	print_html_prefix("查看公告", 'UTF-8');

	$code = $_GET['code'];
	$id = $_GET['id'];
	$date = $_GET['date'];
	$filename = '/home/ssd/StockInsider/text/announcements_companies/' . $code . '_' . $id . '_' . $date;
	$file = fopen($filename, 'r');

	if ($file == false) die('公告未抓取');
	$raw_content = fread($file, filesize($filename));
	$raw_content = mb_convert_encoding($raw_content, 'UTF-8', 'GBK, GB2312, ASCII');
	$title = substr($raw_content, 1, strpos($raw_content, ']') - 1);
	$title = (strpos($title, '新浪') == false) ? $title : substr($title, 0, strpos($title, '新浪'));
	$content = substr($raw_content, strpos($raw_content, '{') + 1);
	$content = substr($content, 0, strlen($content) - 1);
	fclose($file);

	echo '<h1 style="text-align:center;width:550px;">' . $title . '</h1>';
	echo '<pre>' . $content . '</pre>';

	print_html_postfix();
?>
