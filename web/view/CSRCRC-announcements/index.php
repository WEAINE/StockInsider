<?php
	include '../../public/dom_functions.php';

	print_html_prefix('查看已抓取的公告', 'UTF-8');

	$page_no = $_GET['page'];
	if ($page_no == NULL || !is_numeric($page_no) || $page_no <= 0) $page_no = 1;

	$mysqli = new mysqli('localhost', 'root', 'wuhanA2214', 'StockInsider_announcements_CSRCRC');
	if (!$mysqli->connect_error) {
		$sql = 'SELECT announcement_id,title,date FROM announcements_CSRCRC WHERE downloaded=1 LIMIT 20 OFFSET ' . ($page_no - 1) * 20 . ';';
		$result = $mysqli->query($sql);

		if ($result == false || ($page_no == 1 && $result->num_rows == 0)) echo $code . '没有已下载的公告';
		else {
			if ($result->num_rows == 0) header('Location: ?page=1');
			
			echo '<h1>查看证监会重组委发布的公告</h1>';
			echo '<ul>';
			while ($row = $result->fetch_row()) {
				echo '<li>';
				echo '<a target="_blank" href="../../get-content/CSRCRC-announcement/?id=' . $row[0] . '" style="color:black;text-decoration:none;">';
				echo $row[2] . '&nbsp;&nbsp;《' . $row[1] . '》';
				echo '</a>';
				echo '</li>';
			}
			echo '</ul>';
			echo '<br>';

			if ($page_no > 1) echo '<a href="?page=' . ($page_no - 1) . '">上一页</a>&nbsp;&nbsp;&nbsp;&nbsp;';
			echo '第' . $page_no . '页&nbsp;&nbsp;&nbsp;&nbsp;';
			if ($result->num_rows == 20) {
				$result = $mysqli->query('SELECT announcement_id FROM announcements_CSRCRC WHERE downloaded=1 LIMIT 1 OFFSET ' . ($page_no) * 20 . ';');
				if ($result != false && $result->num_rows > 0)
					echo '<a href="?page=' . ($page_no + 1) . '">下一页</a>';
			}
		}

		$result->close();
	}
	$mysqli->close();

	print_html_postfix();
?>
