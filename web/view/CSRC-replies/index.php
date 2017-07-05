<?php
	include '../../public/dom_functions.php';

	print_html_prefix('查看已抓取的反馈意见', 'UTF-8');

	$page_no = $_GET['page'];
	if ($page_no == NULL || !is_numeric($page_no) || $page_no <= 0) $page_no = 1;

	$mysqli = new mysqli('localhost', 'root', 'wuhanA2214', 'StockInsider_replies_CSRC');
	if (!$mysqli->connect_error) {
		$sql = 'SELECT reply_id,title,date FROM replies_CSRC WHERE downloaded=1 LIMIT 20 OFFSET ' . ($page_no - 1) * 20 . ';';
		$result = $mysqli->query($sql);

		if ($result == false || ($page_no == 1 && $result->num_rows == 0)) echo $code . '没有已下载的反馈意见';
		else {
			if ($result->num_rows == 0) header('Location: ?page=1');
			
			echo '<h1>查看证监会发布的反馈意见</h1>';
			echo '<ul>';
			while ($row = $result->fetch_row()) {
				echo '<li>';
				echo '<a target="_blank" href="../../get-content/CSRC-reply/?id=' . $row[0] . '" style="color:black;text-decoration:none;">';
				echo $row[2] . '&nbsp;&nbsp;《' . $row[1] . '》';
				echo '</a>';
				echo '</li>';
			}
			echo '</ul>';
			echo '<br>';

			if ($page_no > 1) echo '<a href="?page=' . ($page_no - 1) . '">上一页</a>&nbsp;&nbsp;&nbsp;&nbsp;';
			echo '第' . $page_no . '页&nbsp;&nbsp;&nbsp;&nbsp;';
			if ($result->num_rows == 20) {
				$result = $mysqli->query('SELECT reply_id FROM replies_CSRC WHERE downloaded=1 LIMIT 1 OFFSET ' . ($page_no) * 20 . ';');
				if ($result != false && $result->num_rows > 0)
					echo '<a href="?page=' . ($page_no + 1) . '">下一页</a>';
			}
		}

		$result->close();
	}
	$mysqli->close();

	print_html_postfix();
?>
