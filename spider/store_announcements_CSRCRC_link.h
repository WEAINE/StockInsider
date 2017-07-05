#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <regex.h>
#include <curl/curl.h>
#include <mysql/mysql.h>

char url[128];
char* CSRCRC_api = "http://www.csrc.gov.cn/pub/newsite/ssgsjgb/bgczwgg/index";

MYSQL conn;
char* SQL_table = "CREATE TABLE IF NOT EXISTS `announcements_CSRCRC` (`announcement_id` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_bin NULL, `title` VARCHAR(1024) CHARACTER SET utf8 COLLATE utf8_bin NULL, `link` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_bin NULL, `date` DATE NULL , `downloaded` TINYINT(1) NULL DEFAULT '0');";
char* SQL_select_prefix = "SELECT announcement_id FROM announcements_CSRCRC WHERE announcement_id='";
char* SQL_insert_prefix = "INSERT INTO announcements_CSRCRC VALUES('";
char* SQL_insert_mid_left_fix = "', '', 'http://www.csrc.gov.cn/pub/zjhpublic/G00306207/";
char* SQL_insert_mid_right_fix = "', '";
char* SQL_insert_postfix = "', '0');";

int up_to_date = 0;
int updates_count = 0;
int more = 1;
int page = 0;
char page_string[4];

int raw_html_length = 0;
int html_index = 0;
char* raw_html;

int offset_id = 0;
int offset_date = 0;
char* pattern_id = "t[0-9]{8}_[0-9]+";
char* pattern_date = "<span>\\s*[0-9]{4}-[0-9]{2}-[0-9]{2}</span>";
char announcement_id[32];
char announcement_date[16];
regex_t reg_id;
regex_t reg_date;
regmatch_t match_id[1];
regmatch_t match_date[1];

void create_table_if_not_exists(MYSQL handler);
void get_raw_html(CURL* handler, char* url);
size_t write_callback_get_length(char* ptr, size_t size, size_t nmemb, void* userdata);
size_t write_callback_get_raw(char* ptr, size_t size, size_t nmemb, void* userdata);
int id_exists(char* id, MYSQL handler);
void store_announcement(char* id, char* date, MYSQL handler);
