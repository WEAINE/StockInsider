#include "download_announcements_companies_thread.h"

int get_code(char* file, char* dest, int num) {
	FILE* file_ptr = fopen(file, "r");
	fseek(file_ptr, num * 6, SEEK_SET);
	int count = fread(dest, 1, 6, file_ptr);
	fclose(file_ptr);

	return count;
}

void get_raw_html(CURL* handler, char* url) {
	curl_easy_setopt(handler, CURLOPT_URL, url);
	curl_easy_perform(handler);
}

size_t write_callback_get_length(char* ptr, size_t size, size_t nmemb, void* userdata) {
	raw_html_length += size * nmemb;

	return size * nmemb;
}

size_t write_callback_get_raw(char* ptr, size_t size, size_t nmemb, void* userdata) {
	for (int i = 0; i < size * nmemb; i++) {
		raw_html[html_index] = ptr[i];
		html_index++;
	}

	return size * nmemb;
}

int main(int argc, char** argv) {
	if (argc != 2) return -1;

	CURLcode init_ret = curl_global_init(CURL_GLOBAL_ALL);
	if (init_ret != CURLE_OK) return -2;

	CURL *easy_handler = curl_easy_init();
	if (easy_handler == NULL) {
		curl_global_cleanup();
		return -3;
	}

	regcomp(&reg_title, pattern_title, REG_EXTENDED);
	regcomp(&reg_content, pattern_content, REG_EXTENDED);
	regcomp(&reg_br, pattern_br, REG_EXTENDED);

	mysql_init(&conn);
	if (!mysql_real_connect(&conn, "localhost", "root", "wuhanA2214", "StockInsider_announcements_companies", 0, NULL, 0)) 
		return -4;

	//strcpy(dir, basedir);
	//strcat(dir, argv[1] + strlen(argv[1]) - 2);
	//strcpy(mkdir, basemkdir);
	//strcat(mkdir, dir);
	//system(mkdir);

	for (int i = 0; get_code(argv[1], code, i) == 6; i++) {
		strcpy(SQL_select, SQL_select_prefix);
		strcat(SQL_select, code);
		strcat(SQL_select, SQL_select_postfix);

		mysql_real_query(&conn, SQL_select, (unsigned int)strlen(SQL_select));
		res = mysql_store_result(&conn);

		while (row = mysql_fetch_row(res)) {
			strcpy(filename, basedir);
			strcat(filename, "/");
			strcat(filename, code);
			strcat(filename, "_");
			strcat(filename, row[0]);
			strcat(filename, "_");
			strcat(filename, row[2]);
			remove(filename);

			curl_easy_setopt(easy_handler, CURLOPT_WRITEFUNCTION, write_callback_get_length);
			get_raw_html(easy_handler, row[1]);

			raw_html = (char*)malloc(++raw_html_length);

			curl_easy_setopt(easy_handler, CURLOPT_WRITEFUNCTION, write_callback_get_raw);
			get_raw_html(easy_handler, row[1]);

			regexec(&reg_title, raw_html, 1, match_title, 0);
			title = (char*)malloc(++match_title[0].rm_eo - match_title[0].rm_so);
			strncpy(title, raw_html + match_title[0].rm_so + 7, match_title[0].rm_eo - match_title[0].rm_so - 16);

			regexec(&reg_content, raw_html, 1, match_content, 0);
			content = (char*)malloc(++match_content[0].rm_eo - match_content[0].rm_so);
			strncpy(content, raw_html + match_content[0].rm_so + 5, match_content[0].rm_eo - match_content[0].rm_so - 12);

			if (regexec(&reg_br, content, 1, match_br, 0) != REG_NOMATCH) content_write_length = match_br[0].rm_so;
			else content_write_length = strlen(content);

			FILE* file_ptr = fopen(filename, "a");
			fwrite("[", 1, 1, file_ptr);
			fwrite(title, strlen(title), 1, file_ptr);
			fwrite("]{", 2, 1, file_ptr);
			fwrite(content, content_write_length, 1, file_ptr);
			fwrite("}", 1, 1, file_ptr);
			fclose(file_ptr);

			strcpy(SQL_update, SQL_update_prefix);
			strcat(SQL_update, code);
			strcat(SQL_update, SQL_update_mid_left_fix);
			strcat(SQL_update, title);
			strcat(SQL_update, SQL_update_mid_right_fix);
			strcat(SQL_update, row[0]);
			strcat(SQL_update, SQL_update_postfix);

			//strcpy(command, basecom);
			//strcat(command, filename);

			//int status = WEXITSTATUS(system(command));
			//while (status != 0) status = WEXITSTATUS(system(command));
			mysql_real_query(&conn, SQL_update, (unsigned int)strlen(SQL_update));

			printf("已下载：%s\n", filename);

			free(title);
			free(content);
			free(raw_html);
			raw_html_length = 0;
			html_index = 0;
		}
	}

	mysql_free_result(res);
	mysql_close(&conn);
	curl_easy_cleanup(easy_handler);
	curl_global_cleanup();

	return 0;
}


