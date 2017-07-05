<?php
	include '../public/dom_functions.php';

	start_point: {
		print_html_prefix('搜索结果', 'UTF-8');

		switch ($_GET['type']) {
			case 'simple':
				goto simple_result;
				break;
			case 'advanced':
				goto advanced_result;
				break;
			default:
				echo '参数缺失';
				goto end_point;
				break;
		}
	}

	simple_result: {
		$source = $_POST['source'] != null ? $_POST['source'] : $_GET['source'];
		$start = $_POST['start'];
		$end = $_POST['end'];
		$field = $_POST['field'];
		$terms = $_POST['terms'];
		$use_cache = $_POST['use_cache'];
		$cache_dir = $_GET['cache'];

		if (intval($start) > intval($end)) {
			echo '起止时间错误';
			goto end_point;
		}

		if ($cache_dir == null || !is_dir('cache/' . $cache_dir)) {
			if (($source == null || $start == null || $end == null || $field == null || $terms == null) && $cache_dir == null) {
				echo '参数缺失';
				goto end_point;
			}

			$terms_param = '';
			$terms_md5_seed = $source . $start . $end . $field;
			foreach (explode(' ', $terms) as $term) {
				$terms_param .= ' "' . $term . '"';
				$terms_md5_seed .= $term;
			}
			$cache_dir = md5($terms_md5_seed);

			if ($use_cache != 'use_cache') system('rm -rf cache/' . $cache_dir);

			if (!is_dir('cache/' . $cache_dir)) {
				$command = 'java -classpath .:/usr/lib/jvm/java-8-openjdk-amd64/lib:/usr/lib/jvm/java-8-openjdk-amd64/jre/lib:/home/weaine/dev/lucene-6.5.0/core/lucene-core-6.5.0.jar:/home/weaine/dev/lucene-6.5.0/queryparser/lucene-queryparser-6.5.0.jar:/home/weaine/dev/lucene-6.5.0/analysis/common/lucene-analyzers-common-6.5.0.jar:/home/weaine/dev/lucene-6.5.0/demo/lucene-demo-6.5.0.jar:/home/weaine/dev/commons-codec-1.10/commons-codec-1.10.jar ChineseTextsSearcher -' . $source . ' --' . $start . ' --' . $end . ' --' .$field . ' ' . $terms_param;
				setlocale(LC_ALL, 'zh_CN.UTF-8');
				putenv('LC_ALL=zh_CN.UTF-8');
				system($command);
			}
		}
		
		$caches = scandir('cache/' . $cache_dir);
		if ($caches == false || count($caches) == 2) {
			echo '无结果，试试换个关键词？';
			goto end_point;
		}

		$pages_count = (count($caches) - 2) % 20 == 0 ? floor((count($caches) - 2) / 20) : floor((count($caches) - 2) / 20) + 1;
		$page_no = $_GET['page'] == null ? 1 : (is_numeric($_GET['page']) ? $_GET['page'] : 1);
		if ($page_no > $pages_count) $page_no = 1;

		echo '<ul>';
		for ($i = 2 + ($page_no - 1) * 20; ($i < 22 + ($page_no - 1) * 20) && ($i < count($caches)); $i++) {
			if (strpos($caches[$i], '_') != false) {
				$filename = '/home/ssd/StockInsider/text/' .$source . '/' . $caches[$i];
				$file = fopen($filename, 'r');
				$raw_content = fread($file, filesize($filename));
				if ($source == 'announcements_companies') $raw_content = mb_convert_encoding($raw_content, 'UTF-8', 'GBK, GB2312, ASCII');
				$title = substr($raw_content, 1, strpos($raw_content, ']') - 1);
				if ($source == 'announcements_companies') $title = (strpos($title, '新浪') == false) ? $title : substr($title, 0, strpos($title, '新浪'));
				$content = substr($raw_content, strpos($raw_content, '{') + 1);
				$content = substr($content, 0, strlen($content) - 1);

				echo '<li>';
				if ($source == 'announcements_companies') echo '<a target="_blank", href="../get-content/company-announcement/?code=' . explode('_', $caches[$i])[0] . '&id=' . explode('_', $caches[$i])[1] . '&date=' . explode('_', $caches[$i])[2] . '" style="color:black;text-decoration:none;">';
				else if ($source == 'announcements_CSRCRC') echo '<a target="_blank", href="../get-content/CSRCRC-announcement/?id=' . $caches[$i] . '" style="color:black;text-decoration:none;">';
				else echo '<a target="_blank", href="../get-content/CSRC-reply/?id=' . $caches[$i] . '" style="color:black;text-decoration:none;">';
				echo explode('_', $caches[$i])[2] . '&nbsp;&nbsp;《' . $title . '》<br>';
				echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . trim(mb_substr($content, 0, 150, 'UTF-8')) . '...<br><br>';
				echo '</a>';
				echo '</li>';

				fclose($file);
			}
		}
		echo '</ul>';
		echo '<br><br>';

		if ($page_no > 1) echo '<a href="?type=simple&source=' . $source . '&cache=' . $cache_dir . '&page=' . ($page_no - 1) . '">上一页</a>&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '第' . $page_no . '页，共' . $pages_count . '页&nbsp;&nbsp;&nbsp;&nbsp;';
		if ($page_no < $pages_count) echo '<a href="?type=simple&source=' . $source . '&cache=' . $cache_dir . '&page=' . ($page_no + 1) . '">下一页</a>';

		goto end_point;
	}

	advanced_result: {
		$source = $_POST['source'] != null ? $_POST['source'] : $_GET['source'];
		$start = $_POST['start'];
		$end = $_POST['end'];
		$field = $_POST['field'];
        $terms = $_POST['terms'];
		$regexp_field = $_POST['regexp_field'];
		$regexp = $_POST['regexp'];
		$use_cache = $_POST['use_cache'];
        $cache_dir = $_GET['cache'];
		$regexp_cache_dir = $_GET['regcache'];

		if (intval($start) > intval($end)) {
			echo '起止时间错误';
			goto end_point;
		}

        if ($cache_dir == null || !is_dir('cache/' . $cache_dir)) {
            if (($source == null || $start == null || $end == null || $field == null || $terms == null) && $cache_dir == null) {
                echo '参数缺失';
                goto end_point;
            }

            $terms_param = '';
            $terms_md5_seed = $source . $start . $end . $field;
            foreach (explode(' ', $terms) as $term) {
                $terms_param .= ' "' . $term . '"';
                $terms_md5_seed .= $term;
            }
            $cache_dir = md5($terms_md5_seed);

            if ($use_cache != 'use_cache') system('rm -rf cache/' . $cache_dir);

            if (!is_dir('cache/' . $cache_dir)) {
                $command = 'java -classpath .:/usr/lib/jvm/java-8-openjdk-amd64/lib:/usr/lib/jvm/java-8-openjdk-amd64/jre/lib:/home/weaine/dev/lucene-6.5.0/core/lucene-core-6.5.0.jar:/home/weaine/dev/lucene-6.5.0/queryparser/lucene-queryparser-6.5.0.jar:/home/weaine/dev/lucene-6.5.0/analysis/common/lucene-analyzers-common-6.5.0.jar:/home/weaine/dev/lucene-6.5.0/demo/lucene-demo-6.5.0.jar:/home/weaine/dev/commons-codec-1.10/commons-codec-1.10.jar ChineseTextsSearcher -' . $source . ' --' . $start . ' --' . $end . ' --' . $field . ' ' . $terms_param;
                setlocale(LC_ALL, 'zh_CN.UTF-8');
                putenv('LC_ALL=zh_CN.UTF-8');
                system($command);
            }
        }

        $caches = scandir('cache/' . $cache_dir);
        if ($caches == false || count($caches) == 2) {
            echo '无结果，试试换个关键词？';
            goto end_point;
        }

		if ($regexp_cache_dir == null || !is_dir('cache/' . $cache_dir . '/' . $regexp_cache_dir)) {
			if (($regexp_field == null || $regexp == null) && $regexp_cache_dir == null) {
				echo '参数缺失';
				goto end_point;
			}

			$regexp_md5_seed = $regexp_field . $regexp;
			$regexp_cache_dir = md5($regexp_md5_seed);

			if (!is_dir('cache/' . $cache_dir . '/' . $regexp_cache_dir)) {
				mkdir('cache/' . $cache_dir . '/' . $regexp_cache_dir);

				$excel = fopen('cache/' . $cache_dir . '/' . $regexp_cache_dir . '/' . $regexp_cache_dir . '.xls', 'a');
				fwrite($excel, '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html><head><meta http-equiv="Content-type" content="text/html;charset=UTF-8" /></head><body><div x:publishsource="Excel"><table x:str>');

				for ($i = 2; $i < count($caches); $i++) {
					if (strpos($caches[$i], '_') != false) {
						$filename = '/home/ssd/StockInsider/text/' . $source . '/' . $caches[$i];

						if (strcmp($regexp_field, 'date') == 0) {
							if ($source == 'announcements_companies') $raw_content = explode('_', $caches[$i])[2];
							else {
								$raw_content = mb_substr($caches[$i], 1, 8, 'UTF-8');
								$raw_content = mb_substr($raw_content, 0, 4, 'UTF-8') . '-' . mb_substr($raw_content, 5, 2, 'UTF-8') . '-' . mb_substr($raw_content, 7, 2,'UTF-8');
							}
						}
						else {
							$file = fopen($filename, 'r');

							$raw_content = fread($file, filesize($filename));
							if ($source == 'announcements_companies') $raw_content = mb_convert_encoding($raw_content, 'UTF-8', 'GBK, GB2312, ASCII');
							$title = substr($raw_content, 1, strpos($raw_content, ']') - 1);
							if ($source == 'announcements_companies') $title = (strpos($title, '新浪') == false) ? $title : substr($title, 0, strpos($title, '新浪'));
							if (strcmp($regexp_field, 'title') == 0) {
								$raw_content = $title;
							}
							if (strcmp($regexp_field, 'content') == 0) {
								$raw_content = substr($raw_content, strpos($raw_content, '{') + 1);
								$raw_content = substr($raw_content, 0, strlen($raw_content) - 1);
							}

							fclose($file);
						}

						$regexp_result = preg_match_all('|' . $regexp . '|U', $raw_content, $matches);
						if ($regexp_result != false && $regexp_result > 0) {
							$file = fopen('cache/' . $cache_dir . '/' . $regexp_cache_dir . '/' . $caches[$i], 'a');
							$offset = 0;

							fwrite($file, '[' . $title . ']');
							fwrite($excel, '<tr>');
							if ($source == 'announcements_companies') {
								fwrite($excel, '<td>' . mb_substr(explode('_', $title)[0], 0, mb_strpos(explode('_', $title)[0], '(', 0, 'UTF-8'), 'UTF-8') . '</td>');
								fwrite($excel, '<td>' . mb_substr(explode('_', $title)[0],  mb_strpos(explode('_', $title)[0], '(', 0, 'UTF-8') + 1, 6, 'UTF-8') . '</td>');
								fwrite($excel, '<td>' . explode('_', $caches[$i])[2] . '</td>');
							}
							else {
								$datefix = mb_substr($caches[$i], 1, 8, 'UTF-8');
								$date = mb_substr($datefix, 0, 4, 'UTF-8') . '-' . mb_substr($datefix, 5, 2, 'UTF-8') . '-' . mb_substr($datefix, 7, 2,'UTF-8');
								fwrite($excel, '<td>' . $date . '</td>');
							}
							fwrite($excel, '<td>' . $title . '</td>');
							foreach ($matches[0] as $match) {
								fwrite($file, 'M{' . $match . '}M');
								fwrite($excel, '<td>' . $match . '</td>');
								
								$offset = mb_strpos($raw_content, $match, $offset, 'UTF-8') + mb_strlen($match, 'UTF-8');
								$front = ($offset - mb_strlen($match, 'UTF-8') - 60) < 0 ? 0 : ($offset - mb_strlen($match, 'UTF-8')- 20);
								$end = ($offset + 20) > mb_strlen($raw_content, 'UTF-8') ? $offset : ($offset + 60);

								fwrite($file, 'C{' . mb_substr($raw_content, $front, $end - $front, 'UTF-8') . '}C');
								fwrite($excel, '<td>' . mb_substr($raw_content, $front, $end - $front, 'UTF-8') . '</td>');
							}
							fclose($file);
							fwrite($excel, '</tr>');
						}
					}
				}

				fwrite($excel, '</table></body></html>');
				fclose($excel);
			}
		}

		$regexp_caches = scandir('cache/' . $cache_dir . '/' . $regexp_cache_dir);
		if ($regexp_caches == false || count($regexp_caches) == 2) {
			echo '无结果，试试换个正则表达式？';
			goto end_point;
		}

		$pages_count = (count($regexp_caches) - 3) % 20 == 0 ? floor((count($regexp_caches) - 3) / 20) : floor((count($regexp_caches) - 3) / 20) + 1;
        $page_no = $_GET['page'] == null ? 1 : (is_numeric($_GET['page']) ? $_GET['page'] : 1);
        if ($page_no > $pages_count) $page_no = 1;

		echo '<a href="cache/' . $cache_dir . '/' . $regexp_cache_dir . '/' . $regexp_cache_dir . '.xls">';
        echo '下载高级搜索结果（Excel）';
        echo '</a><br><br>';
        echo '<ul>';
        for ($j = 2 + ($page_no - 1) * 20; ($j < 22 + ($page_no - 1) * 20) && ($j < count($regexp_caches)); $j++) {
			if (strpos($regexp_caches[$j], 'xls') == false) {
				$filename = 'cache/' . $cache_dir . '/' . $regexp_cache_dir . '/' . $regexp_caches[$j];
				$file = fopen($filename, 'r');
				$raw_content = fread($file, filesize($filename));
				$title = substr($raw_content, 1, strpos($raw_content, ']') - 1);

				preg_match_all('/M{([\s\S]*?)}M/', $raw_content, $matches);
				preg_match_all('/C{([\s\S]*?)}C/', $raw_content, $contexts);

				echo '<li>';
				if ($source == 'announcements_companies') echo '<a target="_blank" href="../get-content/company-announcement/?code=' . explode('_', $regexp_caches[$j])[0] . '&id=' . explode('_', $regexp_caches[$j])[1] . '&date=' . explode('_', $regexp_caches[$j])[2] . '" style="color:black;text-decoration:none;">';
				else if ($source == 'announcements_CSRCRC') echo '<a target="_blank" href="../get-content/CSRCRC-announcement/?id=' . $regexp_caches[$j] . '" style="color:black;text-decoration:none;">';
				else echo '<a target="_blank" href="../get-content/CSRC-reply/?id=' . $regexp_caches[$j] . '" style="color:black;text-decoration:none;">';
				echo explode('_', $regexp_caches[$j])[2] . '&nbsp;&nbsp;《' . $title . '》<br>';
				echo '<ol>';
				for ($k = 0; $k < count($matches[0]); $k++) {
					echo '<li>';
					echo '<i>';
					echo substr($matches[0][$k], 2, strpos($matches[0][$k], '}M') - 2) . '：';
					echo '…' . substr($contexts[0][$k], 2, strpos($contexts[0][$k], '}C') - 2) . '…';
					echo '</i>';
					echo '</li>';
				}
				echo '</ol>';
				echo '</a>';
				echo '</li>';
			}
		}
		echo '</ul>';
		echo '<br><br>';

        if ($page_no > 1) echo '<a href="?type=advanced&source=' . $source . '&cache=' . $cache_dir . '&regcache=' . $regexp_cache_dir . '&page=' . ($page_no - 1) . '">上一页</a>&nbsp;&nbsp;&nbsp;&nbsp;';
        echo '第' . $page_no . '页，共' . $pages_count . '页&nbsp;&nbsp;&nbsp;&nbsp;';
       	if ($page_no < $pages_count) echo '<a href="?type=advanced&source=' . $source . '&cache=' . $cache_dir . '&regcache=' . $regexp_cache_dir . '&page=' . ($page_no + 1) . '">下一页</a>';

		goto end_point;
	}

	end_point: print_html_postfix();
?>
