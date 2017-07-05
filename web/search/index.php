<?php
	include '../public/dom_functions.php';

	start_point: {
		print_html_prefix('StockInsider - 搜索', 'UTF-8');
		import_jquery(1);
		import_css('search');
		print_header();

		switch ($_GET['type']) {
			case 'simple':
				goto simple_search;
				break;
			case 'advanced':
				goto advanced_search;
				break;
			default:
				goto simple_search;
				break;
		}
	}

	simple_search: {
		echo '<form action="../result/?type=simple" method="post">';
		echo '<div class="date_selector">';
		echo '<div class="start">';
		echo '起始年份：';
		echo '<select name="start">';
		for ($i = 1984; $i <= date('Y'); $i++) echo '<option name="' . $i . '" value="' . $i . '">' . $i . '</option>';
		echo '</select>';
		echo '</div>';
		echo '<div class="end">';
		echo '终止年份：';
		echo '<select name="end">';
		for ($i = date('Y'); $i >= 1984; $i--) echo '<option name="' . $i . '" value="' . $i . '">' . $i . '</option>';
		echo '</select>';
		echo '</div>';
		echo '</div>';
		echo '<div class="terms_selector">';
		echo '<div class="source">';
		echo '数据源：';
		echo '<select name="source">';
		echo '<option name="announcements_companies" value="announcements_companies">上市公司公告</option>';
		echo '<option name="announcements_CSRCRC" value="announcements_CSRCRC">证监会重组委公告</option>';
		echo '<option name="replies_CSRC" value="replies_CSRC">证监会并购反馈意见</option>';
		echo '</select>';
		echo '</div>';
		echo '<div class="field">';
		echo '搜索域：';
		echo '<select name="field">';
		echo '<option name="title" value="title">标题</option>';
		echo '<option name="content" value="content">正文</option>';
		echo '<option name="date" value="date">发布日期</option>';
		echo '</select>';
		echo '</div>';
		echo '</div>';
		echo '<div class="terms">';
		echo '<input type="text" name="terms" placeholder="支持多关键字，用空格分开" />';
		echo '</div>';
		echo '<div class="submit_selector">';
		echo '<div class="cache">';
		echo '<input type="checkbox" name="use_cache" value="use_cache" checked="checked" />';
		echo '使用缓存';
		echo '</div>';
		echo '<div class="submit">';
		echo '<input type="submit" value="搜索" />';
		echo '</div>';
		echo '</div>';
		echo '</form>';
		echo '<div class="switch">';
		echo '<a href="?type=advanced">';
        echo '切换到高级搜索';
        echo '</a>';
		echo '</div>';
		echo '<div class="loading">';
		echo '正在分析数百万篇上市公司公告…';
		echo '</div>';

		goto end_point;
	}

	advanced_search: {
		echo '<form action="../result/?type=advanced" method="post">';
		echo '<div class="date_selector">';
		echo '<div class="start">';
		echo '起始年份：';
		echo '<select name="start">';
		for ($i = 1984; $i <= date('Y'); $i++) echo '<option name="' . $i . '" value="' . $i . '">' . $i . '</option>';
		echo '</select>';
		echo '</div>';
		echo '<div class="end">';
		echo '终止年份：';
		echo '<select name="end">';
		for ($i = date('Y'); $i >= 1984; $i--) echo '<option name="' . $i . '" value="' . $i . '">' . $i . '</option>';
		echo '</select>';
		echo '</div>';
		echo '</div>';
		echo '<div class="terms_selector">';
		echo '<div class="source">';
		echo '数据源：';
        echo '<select name="source">';
        echo '<option name="announcements_companies" value="announcements_companies">上市公司公告</option>';
        echo '<option name="announcements_CSRCRC" value="announcements_CSRCRC">证监会重组委公告</option>';
		echo '<option name="replies_CSRC" value="replies_CSRC">证监会并购反馈意见</option>';
        echo '</select>';
        echo '</div>';
		echo '<div class="field">';
		echo '搜索域：';
        echo '<select name="field">';
        echo '<option name="title" value="title">标题</option>';
        echo '<option name="content" value="content">正文</option>';
        echo '<option name="date" value="date">发布日期</option>';
        echo '</select>';
		echo '</div>';
		echo '</div>';
        echo '<div class="terms">';
        echo '<input type="text" name="terms" placeholder="支持多关键字，用空格分开" />';
		echo '</div>';
		echo '<div class="regexp_selector">';
		echo '<div class="regexp_field">';
		echo '正则作用域：';
		echo '<select name="regexp_field">';
        echo '<option name="title" value="title">标题</option>';
        echo '<option name="content" value="content">正文</option>';
        echo '<option name="date" value="date">发布日期</option>';
        echo '</select>';
		echo '</div>';
		echo '<div class="regexp">';
		echo '<input type="text" name="regexp" placeholder="请输入正确格式的正则表达式" />';
		echo '</div>';
		echo '</div>';
		echo '<div class="submit_selector">';
		echo '<div class="cache">';
		echo '<input type="checkbox" name="use_cache" value="use_cache" checked="checked" />';
		echo '使用缓存';
		echo '</div>';
		echo '<div class="submit">';
        echo '<input type="submit" value="搜索" class="submit" />';
        echo '</div>';
		echo '</div>';
        echo '</form>';
		echo '<div class="switch">';
		echo '<a href="?type=simple">';
        echo '切换到普通搜索';
        echo '</a>';
		echo '</div>';
        echo '<div class="loading">';
		echo '正在分析数百万篇上市公司公告（高级搜索耗时更长）…';
		echo '</div>';

		goto end_point;
	}

	end_point: {
		echo '<script type="text/javascript">';
		echo '$(".submit").click(function() {';
		echo '$(".loading").css("display", "block");';
		echo '});';
		echo '</script>';

		print_html_postfix();
	}
?>
