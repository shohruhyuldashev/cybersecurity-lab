# 🦅 AttckchainsLab: Middle Vulnerable Lab

**Simulating a Real-World GovTech Breach**

This lab has been refactored to represent a realistic "Kill Chain" scenario. You cannot simply jump to any vulnerability; you must chain them together to reach the final objective.

## 🎯 Objective
Compromise the entire ecosystem, starting from external reconnaissance and ending with a **Container Escape** (Root access on Host).

## 🗺️ Architecture & Ports

| Service               | Static IP     | Port (External)       | Role               | Vulnerability                                   |
| :-------------------- | :------------ | :-------------------- | :----------------- | :---------------------------------------------- |
| **Storage Server**    | `172.20.0.50` | 9000/9001             | File Storage       | **Misconfiguration** (Info Leak)                |
| **Web Portal**        | `172.20.0.20` | 8080                  | Employee Dashboard | **RCE (Command Injection)**                     |
| **Data API**          | `172.20.0.30` | **Closed** (Internal) | Internal DB        | **SQL Injection**                               |
| **Central IDP**       | `172.20.0.10` | 3000                  | SSO Provider       | **Privilege Escalation** -> **Log4Shell Pivot** |
| **Legacy Logger**     | `172.20.0.40` | **Closed** (Internal) | Logging Service    | **Log4Shell** -> **Escape**                     |
| **Privileged Worker** | `172.20.0.60` | -                     | Target             | **Container Escape**                            |

## ⚔️ The Kill Chain (Spoiler Free)

1.  **Step 1**: Check the **Storage Server**. Are there any "Backups" left open?
2.  **Step 2**: Use what you found to access the **Web Portal**. Look for "Network Tools".
3.  **Step 3**: The Web Portal connects to a **Data API**. Can you extract "Admin" credentials from it?
4.  **Step 4**: Use Admin credentials to access the **Central IDP**. Look for "Diagnostic" tools.
5.  **Step 5**: The Diagnostic tool connects to a **Legacy Logger**. This logger is vulnerable to CVE-2021-44228.
6.  **Step 6**: The Logger is running as **Privileged**. Break out!

## 🚀 Deployment

```bash
docker-compose up --build -d
```

## 🛑 Cleanup

```bash
docker-compose down
```

---
*Created for Educational Purposes. Do NOT use on public networks.*
