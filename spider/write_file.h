void write_file(char* path, char* flag, char* content, int length) {
	FILE* file_ptr = fopen(path, flag);
	fwrite(content, length, 1, file_ptr);
	fflush(file_ptr);
	fclose(file_ptr);
}

	
