#include "stock_code_generator.h"
#include "write_file.h"

void three_digit_constructor(int num, char* output) {
	if (num < 0 || num > 999) output = "000";
	else {
		sprintf(output, "%03d", num);
	}
}

size_t write_callback(char* ptr, size_t size, size_t nmemb, void* userdata) {
	printf("发现有效代码%s。\n", current_code);

	if (size * nmemb > 25) {
		write_file("stock_code.set", "a", current_code, 6);
		write_file(current_file, "a", current_code, 6);
	}
	
	return size * nmemb;
}	

int main() {
	remove("stock_code.set");

	CURLcode init_ret = curl_global_init(CURL_GLOBAL_ALL);
	if (init_ret != CURLE_OK) return -1;

	CURL* easy_handler = curl_easy_init();
	if (easy_handler == NULL) {
		curl_global_cleanup();
		return -2;
	}

	curl_easy_setopt(easy_handler, CURLOPT_WRITEFUNCTION, write_callback);

	int i, j;
	char postfix[3], url[128];
	for (i = 0; i < 1000; i++) {
		sprintf(current_remainder, "%02d", i % 100);
		strcpy(current_file, basename);
		strcat(current_file, current_remainder);
		if (i < 100) remove(current_file);

		for (j = 0; j < 6; j++) {
			memset(current_code, 0, 6);
			strcpy(url, sina_api);
			three_digit_constructor(i, postfix);

			strcat(current_code, prefix[j]);
			strcat(current_code, postfix);

			if (j < 4) strcat(url, "sz");
			else strcat(url, "sh");
			strcat(url, current_code);

			curl_easy_setopt(easy_handler, CURLOPT_URL, url);
			curl_easy_perform(easy_handler);
		}
	}

	printf("\n");

	curl_easy_cleanup(easy_handler);
	curl_global_cleanup();

	return 0;
}
