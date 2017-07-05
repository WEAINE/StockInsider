#include <pthread.h>
#include <stdlib.h>
#include <stdio.h>
#include <string.h>
#include <unistd.h>

pthread_t tids[100];
char* basename = "stock_code_set/stock_code_set.";
char fullname[32];
char current_remainder[2];

void init_tids();
void* launcher(void* stock_code_set);
