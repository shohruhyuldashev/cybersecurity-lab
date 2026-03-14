# O'zgarishlar (Changelog)

Ushbu faylda loyiha bo'yicha barcha muhim o'zgarishlar qayd etiladi.

Format [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) asosida ishlab chiqilgan,
loyiha esa [Semantic Versioning](https://semver.org/spec/v2.0.0.html) qoidalariga rioya qiladi.

## [1.0.0] - 2026-03-13

### Qo'shilganlar
- MOVEit LXD Lab penetratsiya testi muhitining birinchi versiyasi
- Flask veb-ilovasi bilan Docker konteyneri konfiguratsiyasi
- Yuklash metadata boshqaruvchisida SQL injeksiya zaifligi
- Yuklangan fayllar orqali webshell ishga tushirish imkoniyati
- LXD orqali imtiyozli eskalatsiya zanjiri
- Bootstrap asosida korporativ uslubdagi foydalanuvchi interfeysi
- Versiya ma'lumotlarini oshkor qiluvchi endpoint (/readme.txt)
- Foydalanuvchi ro'yxatdan o'tkazish va bcrypt bilan autentifikatsiya
- Fayl yuklash, boshqarish va admin interfeyslar
- CTF maqsadlari uchun konteyner va host bayroqlari (flag) yaratildi
- Avtomatlashtirilgan joylashtirish uchun setup skripti
- Arxitektura va ekspluatatsiya bosqichlari bilan to'liq README
- Foydalanishga ruxsat berilgan fayl tizimi bilan konteynerni mustahkamlash va imkoniyatlarni cheklash

### Xavfsizlik
- Ta'lim maqsadida CVE-2023-34362 ga o'xshash zaiflik simulyatsiyasi
- Bittagina maqsadli hujum zanjiri: foydalanuvchi ro'yxatdan o'tishi → versiya aniqlanishi → SQLi → webshell → konteyner shell → LXD eskalatsiyasi → host rootga kirish

### Infratuzilma
- Ubuntu 22.04 host, Docker va LXD bilan
- Konteyner www-data foydalanuvchisi nomidan ishlaydi, ruxsatlar cheklangan
- LXD soketi imtiyozlarni eskalatsiya demo uchun montaj qilingan
- Ilova ma'lumotlari uchun SQLite ma'lumotlar bazasi