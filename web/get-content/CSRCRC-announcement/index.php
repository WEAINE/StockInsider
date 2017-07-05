<?php
	include '../../public/dom_functions.php';

	print_html_prefix("查看公告", 'UTF-8');

	$id = $_GET['id'];
	$filename = '/home/ssd/StockInsider/text/announcements_CSRCRC/' . $id;
	$file = fopen($filename, 'r');

	if ($file == false) die('公告未抓取');
	$raw_content = fread($file, filesize($filename));
	$title = substr($raw_content, 1, strpos($raw_content, ']') - 1);
	$content = substr($raw_content, strpos($raw_content, '{') + 1);
	$content = substr($content, 0, strlen($content) - 1);
	fclose($file);

	echo '<h1 style="text-align:center;width:550px;">' . $title . '</h1>';
	echo '<p style="width:550px;">' . $content . '</p>';

	print_html_postfix();
?>
