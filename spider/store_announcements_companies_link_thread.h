#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <regex.h>
#include <curl/curl.h>
#include <mysql/mysql.h>

char code[6];
char url[128];
char* sina_api = "http://vip.stock.finance.sina.com.cn/corp/view/vCB_AllBulletin.php?stockid=";

MYSQL conn;
char* SQL_table_prefix = "CREATE TABLE IF NOT EXISTS `announcements_";
char* SQL_table_postfix = "` (`announcement_id` VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_bin NULL, `title` VARCHAR(1024) CHARACTER SET utf8 COLLATE utf8_bin NULL, `link` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_bin NULL, `date` DATE NULL , `downloaded` TINYINT(1) NULL DEFAULT '0');";
char* SQL_select_prefix = "SELECT announcement_id FROM announcements_";
char* SQL_select_postfix = " WHERE announcement_id='";
char* SQL_insert_prefix = "INSERT INTO announcements_";
char* SQL_insert_mid_left_fix = " VALUES('";
char* SQL_insert_mid_mid_fix = "', '', 'http://vip.stock.finance.sina.com.cn/corp/view/vCB_AllBulletinDetail.php?stockid=";
char* SQL_insert_mid_right_fix = "', '";
char* SQL_insert_postfix = "', '0');";

int up_to_date = 0;
int updates_count = 0;
int more = 1;
int page = 1;
char page_string[4];

int raw_html_length = 0;
int html_index = 0;
char* raw_html;

int offset_id = 0;
int offset_date = 0;
char* pattern_id = "&id=[0-9]+";
char* pattern_date = "[0-9]{4}-[0-9]{2}-[0-9]{2}&nbsp;";
char announcement_id[16];
char announcement_date[16];
regex_t reg_id;
regex_t reg_date;
regmatch_t match_id[1];
regmatch_t match_date[1];

int get_code(char* file, char* dest, int num);
void create_table_if_not_exists(char* stock_code, MYSQL handler);
void get_raw_html(CURL* handler, char* url);
size_t write_callback_get_length(char* ptr, size_t size, size_t nmemb, void* userdata);
size_t write_callback_get_raw(char* ptr, size_t size, size_t nmemb, void* userdata);
int id_exists(char* stock_code, char* id, MYSQL handler);
void store_announcement(char* stock_code, char* id, char* date, MYSQL handler);
