#include "store_announcements_CSRCRC_link.h"

void create_table_if_not_exists(MYSQL handler) {
	mysql_real_query(&handler, SQL_table, (unsigned int)strlen(SQL_table));
}

void get_raw_html(CURL* handler, char* url) {
	curl_easy_setopt(handler, CURLOPT_URL, url);
	CURLcode retcode = curl_easy_perform(handler);

	if (retcode != CURLE_OK) more = 0;
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

int id_exists(char* id, MYSQL handler) {
	char SQL[256];
	strcpy(SQL, SQL_select_prefix);
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

void store_announcement(char* id, char* date, MYSQL handler) {
	char YM[6];
	strncpy(YM, date + 1, 4);
	strncat(YM, date + 6, 2);

	char SQL[512];
	strcpy(SQL, SQL_insert_prefix);
	strcat(SQL, id);
	strcat(SQL, SQL_insert_mid_left_fix);
	strcat(SQL, YM);
	strcat(SQL, "/");
	strcat(SQL, id);
	strcat(SQL, ".htm");
	strcat(SQL, SQL_insert_mid_right_fix);
	strcat(SQL, date);
	strcat(SQL, SQL_insert_postfix);

	mysql_real_query(&handler, SQL, (unsigned int)strlen(SQL));
}

int main(int argc, char** argv) {
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
	if (!mysql_real_connect(&conn, "localhost", "root", "wuhanA2214", "StockInsider_announcements_CSRCRC", 0, NULL, 0)) 
		return -4;

	strcpy(url, CSRCRC_api);
	create_table_if_not_exists(conn);

	while (up_to_date == 0 && more == 1) {
		if (page > 0) {
			sprintf(page_string, "_%d", page);
			strcat(url, page_string);
		}
		strcat(url, ".htm");

		curl_easy_setopt(easy_handler, CURLOPT_FAILONERROR, 1);
		curl_easy_setopt(easy_handler, CURLOPT_WRITEFUNCTION, write_callback_get_length);
		get_raw_html(easy_handler, url);

		if (more == 1) {
			raw_html = (char*)malloc(++raw_html_length);

			curl_easy_setopt(easy_handler, CURLOPT_WRITEFUNCTION, write_callback_get_raw);
			get_raw_html(easy_handler, url);

			while (up_to_date == 0 && regexec(&reg_id, raw_html + offset_id, 1, match_id, 0) != REG_NOMATCH && regexec(&reg_date, raw_html + offset_date, 1, match_date, 0) != REG_NOMATCH) {
				memset(announcement_id, '\0', 32);
				memset(announcement_date, '\0', 16);
				strncpy(announcement_id, raw_html + offset_id + match_id[0].rm_so, match_id[0].rm_eo - match_id[0].rm_so);
				strncpy(announcement_date, raw_html + offset_date + match_date[0].rm_so + 6, match_date[0].rm_eo - match_date[0].rm_so - 14);

				if (id_exists(announcement_id, conn) == 1) up_to_date = 1;
				else {
					store_announcement(announcement_id, announcement_date, conn);
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
			strcpy(url, CSRCRC_api);
		}
	}

	printf("更新了%d篇公告。\n", updates_count);

	mysql_close(&conn);
	curl_easy_cleanup(easy_handler);
	curl_global_cleanup();

	return 0;
}
