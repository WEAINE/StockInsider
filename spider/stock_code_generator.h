#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <curl/curl.h>

char* prefix[6] = { "000", "001", "002", "300", "600", "601" };
char* basename = "stock_code_set/stock_code_set.";
char current_remainder[2];
char current_file[32];
char current_code[6];
char sina_api[128] = "http://hq.sinajs.cn/list=";

void three_digit_constructor(int num, char* output);
size_t write_callback(char* ptr, size_t size, size_t nmemb, void* userdata);
