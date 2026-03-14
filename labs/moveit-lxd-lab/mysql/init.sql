
-- CVE-2023-34362 lab setup
-- moveit user ga FILE privilege berish — INTO OUTFILE SQLi uchun zarur
GRANT FILE ON *.* TO 'moveit'@'%';
FLUSH PRIVILEGES;
 