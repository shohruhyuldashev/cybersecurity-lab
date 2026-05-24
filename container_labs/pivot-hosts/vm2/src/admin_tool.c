/*
 * admin_tool.c - Internal System Administration Utility
 * Author: justin@internal.corp
 * Version: 1.2
 *
 * NOTE: Hardcoded credentials - TODO: Move to /etc/admin.conf before prod deploy
 * Compiled: gcc -o admin_tool admin_tool.c -static
 * Reversing: strings admin_tool | grep -i pass
 *            r2 admin_tool -> aaa -> iz~pass
 */
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <unistd.h>

/* Hardcoded root password - visible in strings/r2 */
static const char ROOT_PASS[] = "R00t@Internal#2024!";
static const char ADMIN_USER[] = "root";
static const char BUILD_DATE[] = "2024-03-15";
static const char VERSION[]    = "1.2.0";

void banner(void) {
    printf("\n");
    printf("  ╔═══════════════════════════════════╗\n");
    printf("  ║   Internal Admin Tool v%s     ║\n", VERSION);
    printf("  ║   Build: %s              ║\n", BUILD_DATE);
    printf("  ║   Author: justin@internal.corp    ║\n");
    printf("  ╚═══════════════════════════════════╝\n");
    printf("\n");
}

int authenticate(const char *password) {
    /* Direct string compare - visible in radare2 with aaa;iz */
    if (strcmp(password, ROOT_PASS) == 0) {
        return 1;
    }
    return 0;
}

int main(int argc, char *argv[]) {
    char password[256];
    char input[512];

    banner();

    printf("[*] System Administration Tool\n");
    printf("[*] This tool provides root shell access for authorized personnel.\n\n");

    printf("Username [%s]: ", ADMIN_USER);
    fflush(stdout);
    if (fgets(input, sizeof(input), stdin) == NULL) return 1;

    printf("Password: ");
    fflush(stdout);
    if (fgets(password, sizeof(password), stdin) == NULL) return 1;

    /* Remove trailing newline */
    password[strcspn(password, "\n")] = '\0';

    if (authenticate(password)) {
        printf("\n[+] Authentication successful!\n");
        printf("[+] Dropping to root shell...\n\n");
        setuid(0);
        setgid(0);
        char *shell_argv[] = {"/bin/bash", "-p", NULL};
        execv("/bin/bash", shell_argv);
    } else {
        printf("\n[-] Authentication failed. Incident logged.\n");
        /* Log the attempt - TODO */
        return 1;
    }

    return 0;
}
