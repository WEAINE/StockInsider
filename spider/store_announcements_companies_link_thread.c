#include "store_announcements_companies_link_thread.h"

int get_code(char* file, char* dest, int num) {
	FILE* file_ptr = fopen(file, "r");
	fseek(file_ptr, num * 6, SEEK_SET);
	int count = fread(dest, 1, 6, file_ptr);
	fclose(file_ptr);

	return count;
}

void create_table_if_not_exists(char* stock_code, MYSQL handler) {
	char SQL[1024];
	strcpy(SQL, SQL_table_prefix);
	strcat(SQL, stock_code);
	strcat(SQL, SQL_table_postfix);

	mysql_real_query(&handler, SQL, (unsigned int)strlen(SQL));
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

int id_exists(char* stock_code, char* id, MYSQL handler) {
	char SQL[256];
	strcpy(SQL, SQL_select_prefix);
	strcat(SQL, stock_code);
	strcat(SQL, SQL_select_postfix);
	strcat(SQL, id);
	strcat(SQL, "';");

	mysql_real_query(&handler, SQL, (unsigned int)strlen(SQL));
	MYSQL_RES* res = mysql_store_result(&handler);
	if (res && mysql_num_rows(res) > 0) {
		mysql_free_result(res);
		return 1;
	}
	else{
		mysql_free_result(res);
		return 0;
	}
}

void store_announcement(char* stock_code, char* id, char* date, MYSQL handler) {
	char SQL[512];
	strcpy(SQL, SQL_insert_prefix);
	strcat(SQL, stock_code);
	strcat(SQL, SQL_insert_mid_left_fix);
	strcat(SQL, id);
	strcat(SQL, SQL_insert_mid_mid_fix);
	strcat(SQL, stock_code);
	strcat(SQL, "&id=");
	strcat(SQL, id);
	strcat(SQL, SQL_insert_mid_right_fix);
	strcat(SQL, date);
	strcat(SQL, SQL_insert_postfix);

	mysql_real_query(&handler, SQL, (unsigned int)strlen(SQL));
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

	regcomp(&reg_id, pattern_id, REG_EXTENDED);
	regcomp(&reg_date, pattern_date, REG_EXTENDED);

	mysql_init(&conn);
	if (!mysql_real_connect(&conn, "localhost", "root", "wuhanA2214", "StockInsider_announcements_companies", 0, NULL, 0)) 
		return -4;

	for (int i = 0; get_code(argv[1], code, i) == 6; i++) {
		strcpy(url, sina_api);
		strcat(url, code);
		create_table_if_not_exists(code, conn);

		while (up_to_date == 0 && more == 1) {
			sprintf(page_string, "%d", page);
			strcat(url, "&Page=");
			strcat(url, page_string);

			curl_easy_setopt(easy_handler, CURLOPT_WRITEFUNCTION, write_callback_get_length);
			get_raw_html(easy_handler, url);

			raw_html = (char*)malloc(++raw_html_length);

			curl_easy_setopt(easy_handler, CURLOPT_WRITEFUNCTION, write_callback_get_raw);
			get_raw_html(easy_handler, url);

			if (regexec(&reg_id, raw_html, 1, match_id, 0) == REG_NOMATCH) more = 0;
			while (up_to_date == 0 && regexec(&reg_id, raw_html + offset_id, 1, match_id, 0) != REG_NOMATCH && regexec(&reg_date, raw_html + offset_date, 1, match_date, 0) != REG_NOMATCH) {
				memset(announcement_id, '\0', 16);
				memset(announcement_date, '\0', 16);
				strncpy(announcement_id, raw_html + offset_id + match_id[0].rm_so + 4, match_id[0].rm_eo - match_id[0].rm_so - 4);
				strncpy(announcement_date, raw_html + offset_date + match_date[0].rm_so, match_date[0].rm_eo - match_date[0].rm_so - 6);

				if (id_exists(code, announcement_id, conn) == 1) up_to_date = 1;
				else {
					store_announcement(code, announcement_id, announcement_date, conn);
					updates_count++;
				}

				offset_id += ++match_id[0].rm_so;
				offset_date += ++match_date[0].rm_so;
			}

			page++;
			raw_html_length = 0;
			html_index = 0;
			offset_id = 0;
			offset_date = 0;
			free(raw_html);
			strcpy(url, sina_api);
			strcat(url, code);
		}

		printf("%s: 更新了%d篇公告。\n", code, updates_count);

		up_to_date = 0;
		updates_count = 0;
		more = 1;
		page = 1;
	}

	mysql_close(&conn);
	curl_easy_cleanup(easy_handler);
	curl_global_cleanup();

	return 0;
}
