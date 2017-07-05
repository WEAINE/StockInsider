#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <regex.h>
#include <curl/curl.h>
#include <mysql/mysql.h>

char code[6];
char* basedir = "/home/ssd/StockInsider/text/announcements_companies/";
//char dir[64];
//char* basemkdir = "mkdir ";
//char mkdir[128];
char filename[128];
char* basecom = "java SingleTextIndexer -announcements_companies ";
char command[256];

MYSQL conn;
MYSQL_RES* res;
MYSQL_ROW row;
char SQL_select[128];
char* SQL_select_prefix = "SELECT announcement_id,link,date FROM announcements_";
char* SQL_select_postfix = " WHERE downloaded=0;";
char SQL_update[512];
char* SQL_update_prefix = "UPDATE announcements_";
char* SQL_update_mid_left_fix = " SET title='";
char* SQL_update_mid_right_fix = "',downloaded=1 WHERE announcement_id='";
char* SQL_update_postfix = "';";

int raw_html_length = 0;
int html_index = 0;
char* raw_html;

char* pattern_title = "<title>(.*)</title>";
char* pattern_content = "<pre>(.*?)</pre>";
char* pattern_br = "<br>";
char* title;
char* content;
regex_t reg_title, reg_content, reg_br;
regmatch_t match_title[1], match_content[1], match_br[1];

int content_write_length = 0;

int get_code(char* file, char* dest, int num);
void get_raw_html(CURL* handler, char* url);
size_t write_callback_get_length(char* ptr, size_t size, size_t nmemb, void* userdata);
size_t write_callback_get_raw(char* ptr, size_t size, size_t nmemb, void* userdata);
