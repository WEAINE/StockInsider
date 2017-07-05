<?php
	include '../../public/dom_functions.php';

	start_point: {
		print_html_prefix('查看已抓取的公告', 'UTF-8');

		switch ($_GET['action']) {
			case 'list_stocks':
				goto list_stocks;
				break;
			case 'list_announcements':
				goto list_announcements;
				break;
			default:
				goto list_stocks;
				break;
		}
	}

	list_stocks: {
		import_jquery(2);

		$stock_code_set = fopen('/home/weaine/dev/StockInsider/spider/stock_code.set', 'r');
		$items_count = 0;
		$offset = 0;
		$more_pages_exist = true;
		$scripts = array();

		$page_no = $_GET['page'];
		if ($page_no == NULL || !is_numeric($page_no) || $page_no <= 0) $page_no = 1;

		echo '<ul>';
		while ($items_count <= 20) {
			fseek($stock_code_set, ($page_no - 1) * 20 * 6 + $offset * 6);

			if ($content = fread($stock_code_set, 6)) {
				echo '<li>';
				echo '<a id="link_' . $content . '" style="color:black;text-decoration:none;" target="_blank">';
				echo '查看<span id="name_' . $content . '"> 加载中… </span>的公告';
				echo '</a>';
				echo '</li>';

				$script = 'code_' . $content . '="' . $content . '";';
				$script .= 'name_' . $content . '=$.ajax({url:"get_stock_name.php?code=' . $content . '",async:false});';
				$script .= '$("#name_' . $content . '").html(" "+name_' . $content . '.responseText+"（"+code_' . $content .'+"） ");';
				$script .= '$("#link_' . $content . '").attr("href","?action=list_announcements&code="+code_' . $content . '+"&name="+name_' . $content . '.responseText+"&page=1");';

				array_push($scripts, $script);

				$items_count++;
				$offset++;
			}
			else break;
		}
		echo '</ul>';

		if ($items_count < 20) $more_pages_exist = false;
		else {
			fseek($stock_code_set, ($page_no - 1) *20 * 6 + $offset *6);
			if (fread($stock_code_set, 6) == false) $more_pages_exist = false;
		}

		if ($items_count == 0) header('Location: ?action=list_stocks&page=1');

		echo '<br>';
		if ($page_no > 1) echo '<a href="?action=list_stocks&page=' . ($page_no - 1) . '">上一页</a>&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '第' . $page_no . '页&nbsp;&nbsp;&nbsp;&nbsp;';
		if ($more_pages_exist) echo '<a href="?action=list_stocks&page=' . ($page_no + 1) . '">下一页</a>';

		echo '<script type="text/javascript">';
		echo '$(document).ready(function() {';
		foreach ($scripts as $script) echo $script;
		echo '});';
		echo '</script>';

		fclose($stock_code_set);
		goto end_point;
	}

	list_announcements: {
		$code = $_GET['code'];
		if ($code == NULL || strlen($code) != 6 || !is_numeric($code)) goto list_stocks;

		$page_no = $_GET['page'];
		if ($page_no == NULL || !is_numeric($page_no) || $page_no <= 0) $page_no = 1;

		$mysqli = new mysqli('localhost', 'root', 'wuhanA2214', 'StockInsider_announcements_companies');
		if (!$mysqli->connect_error) {
			$sql = 'SELECT announcement_id,title,date FROM announcements_' . $code . ' WHERE downloaded=1 LIMIT 20 OFFSET ' . ($page_no - 1) * 20 . ';';
			$result = $mysqli->query($sql);

			if ($result == false || ($page_no == 1 && $result->num_rows == 0)) echo $code . '没有已下载的公告';
			else {
				if ($result->num_rows == 0) header('Location: ?action=list_announcements&code=' . $code . '&name=' . $_GET['name'] . '&page=1');
				if ($_GET['name'] != NULL) echo '<h1>查看' . $_GET['name'] . '的公告</h1>';

				echo '<ul>';
				while ($row = $result->fetch_row()) {
					$title = mb_convert_encoding($row[1], 'UTF-8', 'GBK, GB2312, ASCII');
					$title = (strpos($title, '新浪') == false) ? $title : substr($title, 0, strpos($title, '新浪'));
					if ($title == NULL) $title = $_GET['name'] . '（' . $code . '）的公告';
					
					echo '<li>';
					echo '<a target="_blank" href="../../get-content/company-announcement/?code=' . $code . '&id=' . $row[0] . '&date=' . $row[2] . '" style="color:black;text-decoration:none;">';
					echo $row[2] . '&nbsp;&nbsp;《' . $title . '》';
					echo '</a>';
					echo '</li>';
				}
				echo '</ul>';
				echo '<br>';

				if ($page_no > 1) echo '<a href="?action=list_announcements&code=' . $code . '&name=' . $_GET['name'] . '&page=' . ($page_no - 1) . '">上一页</a>&nbsp;&nbsp;&nbsp;&nbsp;';
				echo '第' . $page_no . '页&nbsp;&nbsp;&nbsp;&nbsp;';
				if ($result->num_rows == 20) {
					$result = $mysqli->query('SELECT announcement_id FROM announcements_' . $code . ' WHERE downloaded=1 LIMIT 1 OFFSET ' . ($page_no) * 20 . ';');
					if ($result != false && $result->num_rows > 0)
						echo '<a href="?action=list_announcements&code=' . $code . '&name=' . $_GET['name'] . '&page=' . ($page_no + 1) . '">下一页</a>';
				}
			}

			$result->close();
		}
		$mysqli->close();

		goto end_point;
	}

	end_point: print_html_postfix();
	?>
