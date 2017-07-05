<?php
	function character_set($set) {
                echo '<meta charset="' . $set . '">';
        }

	function title_set($set) {
		echo '<title>' . $set . '</title>';
	}

	function print_html_prefix($title, $charset) {
		echo '<html>';
		echo '<head>';
		character_set($charset);
		title_set($title);
		echo '</head>';
		echo '<body>';
	}
	
	function print_html_postfix() {
		echo '</body>';
		echo '</html>';
	}	

	function clear() {
                echo '<script type="text/javascript">document.body.innerHTML="";</script>';
        }

        function import_jquery($level) {
                echo '<script type="text/javascript" src="';
                for ($i = 0; $i < $level; $i++) echo "../";
                echo 'public/jquery-3.2.0.min.js"></script>';
        }

	function import_css($css) {
		echo '<link rel="stylesheet" type="text/css" href="' . $css . '.css" />';
	}

	function print_header() {
		echo '<div id="logo">StockInsider</div>';
	}
?>
