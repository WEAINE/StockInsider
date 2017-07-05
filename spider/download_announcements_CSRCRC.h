#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <regex.h>
#include <curl/curl.h>
#include <mysql/mysql.h>

char* basedir = "/home/ssd/StockInsider/text/announcements_CSRCRC/";
char filename[128];
char* basecom = "java SingleTextIndexer -announcements_CSRCRC ";
char command[256];

MYSQL conn;
MYSQL_RES* res;
MYSQL_ROW row;
char* SQL_select = "SELECT announcement_id,link FROM announcements_CSRCRC WHERE downloaded=0;";
char SQL_update[512];
char* SQL_update_prefix = "UPDATE announcements_CSRCRC SET title='";
char* SQL_update_mid_fix = "',downloaded=1 WHERE announcement_id='";
char* SQL_update_postfix = "';";

int raw_html_length = 0;
int html_index = 0;
char* raw_html;

int offset = 0;
char* pattern_title = "<title>(.*)</title>";
char* pattern_content = "<SPAN style=\"FONT-SIZE: 10.5pt\">\\S+?</SPAN>";
regex_t reg_title, reg_content;
regmatch_t match_title[1], match_content[1];

void get_raw_html(CURL* handler, char* url);
size_t write_callback_get_length(char* ptr, size_t size, size_t nmemb, void* userdata);
size_t write_callback_get_raw(char* ptr, size_t size, size_t nmemb, void* userdata);
