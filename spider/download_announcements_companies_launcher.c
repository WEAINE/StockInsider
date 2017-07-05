#include "download_announcements_companies_launcher.h"

void init_tids() {
        for (int i = 0; i < 100; i++) {
                pthread_t thread;
                tids[i] = thread;
        }
}

void* launcher(void* stock_code_set) {
        char* filename = (char*)stock_code_set;
        char command[128];
        strcpy(command, "./download_announcements_companies_thread ");
        strcat(command, filename);
        system(command);
}

int main() {
        init_tids();

        for (int i = 0; i < 100; i++) {
                sprintf(current_remainder, "%02d", i);
                strcpy(fullname, basename);
                strcat(fullname, current_remainder);
                pthread_create(&tids[i], NULL, &launcher, &fullname);
                sleep(3);
        }

        for (int j = 0; j < 100; j++) pthread_join(tids[j], NULL);

        return 0;
}
