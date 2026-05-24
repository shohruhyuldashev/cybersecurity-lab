# CTF Lab - 3-Machine Pivot Challenge

## Tarmoq Arxitekturasi

```
[Attacker]
    |
[Net1: 10.10.10.0/24]
    |
[VM1: DocuShare]        ← Entry point
 10.10.10.10
 172.16.0.10
    |
[Net2: 172.16.0.0/24]
    |
[VM2: InternalAPI]
 172.16.0.20
 192.168.100.20
    |
[Net3: 192.168.100.0/24]
    |
[VM3: AdminPanel]
 192.168.100.30
```

---

## O'rnatish

```bash
# Images yuklash
docker load -i ctf-vm1.tar
docker load -i ctf-vm2.tar
docker load -i ctf-vm3.tar

# Ishga tushirish (docker compose)
docker compose up -d

# Yoki alohida har birini deploy qilish (deploy.sh)
./deploy.sh ctf-vm1.tar
```

---

## Mashinalar

| VM | IP (internal) | Service | Port |
|----|---------------|---------|------|
| VM1 | 10.10.10.10 | DocuShare | 80 |
| VM2 | 172.16.0.20 | InternalAPI | 5000 |
| VM3 | 192.168.100.30 | AdminPanel | 80 |

> Haqiqiy CTF muhitida faqat VM1 tashqaridan ko'rinadi.

---

## Deploy Script

```bash
# Bitta mashinani deploy qilish:
./deploy.sh ctf-vm1.tar

# Ctrl+C bilan container avtomatik o'chiriladi
```
