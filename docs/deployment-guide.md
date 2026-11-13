# Deployment Guide – Smart CRM · Mekong Mobile (BT3, tag `v1.0`)

**Flow:** L1 – Create, search and merge duplicate customer profiles · **Track:** SE  
**Author:** Thepvinath Souphavilay (237480201IS03)

The system runs as three Docker containers started by **two commands**:

| Service | Image | Role | Port on the host |
|---|---|---|---|
| `db` | `mysql:8.0` | MySQL database (data in the named volume `smartcrm_dbdata`) | none – internal network only |
| `api` | built from `Dockerfile` (`php:8.3-cli-alpine`) | PHP API; on start-up runs the migration and the demo seeder, then serves `/api` | none – reached through `web` |
| `web` | built from `frontend/Dockerfile` (`node:22-alpine` build → `nginx:1.27-alpine`) | React UI; proxies `/api/` to `api` | `WEB_PORT` (default **8080**) |

Start order is enforced with healthchecks: `api` waits until `db` is healthy, `web` waits until `api` is healthy.

A public trial copy (optional bonus) is described in section 6.

---

## 1. Requirements

| Item | Requirement |
|---|---|
| Operating system | Windows 10/11 (with WSL 2), macOS or Linux |
| Docker | Docker Desktop (Windows/macOS) or Docker Engine 24+ with the **Compose v2** plugin (`docker compose`, not `docker-compose`). Tested with Docker 29.9.1 on Windows 11. |
| Git | Any recent version, to clone the repository |
| Disk space | About **2 GB** free for the images and the database volume |
| Network | Internet access the first time, to download the base images from Docker Hub and the npm packages (first build took about 6 minutes) |
| Free port | **8080** on the host (change with `WEB_PORT` in `.env` if it is used) |

Nothing else needs to be installed: PHP, Node.js and MySQL run inside the containers.

**Docker must be running** before step 3 (on Windows/macOS: open Docker Desktop and wait for "Engine running"). Check with:

```bash
docker info --format "{{.ServerVersion}}"
```

---

## 2. Setup steps

Run these commands in an **empty folder**. Each step is one command.

**Step 1 – Clone the release tag**

```bash
git clone --branch v1.0 https://github.com/Thepvinath/cdtn1-SouphavilayThepvinath-L1.git smartcrm
```

**Step 2 – Enter the project folder**

```bash
cd smartcrm
```

**Step 3 – Create the configuration file from the sample** (works in PowerShell, bash and zsh)

```bash
cp .env.example .env
```

**Step 4 – Build and start all services**

```bash
docker compose up -d --build
```

The command ends when all containers are started; the output shows `db` and `api` as `Healthy`. The `api` container creates the tables and the demo data automatically – there is **no separate migration or seed command**.

Then open **http://localhost:8080** (or `http://localhost:<WEB_PORT>`).

**Stop / reset**

```bash
docker compose down        # stop and remove containers, keep the data
docker compose down -v     # stop and also delete the database volume (fresh start next time)
```

---

## 3. Environment variables

All variables are read from the **`.env` file** in the project folder (`env_file: .env` in `docker-compose.yml`). Variables already defined in the host shell do **not** override the file (see BUG-07 in `docs/bug-log.md`). The values in `.env.example` are sample values for a local demo and work as they are.

| Variable | Meaning | Sample value (`.env.example`) | Required |
|---|---|---|---|
| `DB_HOST` | MySQL host for the API. **Docker overrides it to `db`** (service name); used only for the non-Docker setup | `localhost` | Yes (non-Docker) |
| `DB_PORT` | MySQL port. Docker overrides it to `3306` | `3306` | Yes (non-Docker) |
| `DB_NAME` | Database created by the `db` container and used by the API. **Must start with `smartcrm_bt2_`** (the migration and seeder refuse other names) | `smartcrm_bt2_docker` | Yes |
| `DB_USER` | Application database user created by the `db` container. **Must not be `root`** | `smartcrm` | Yes |
| `DB_PASSWORD` | Password of `DB_USER` | `smartcrm_local_db_password` | Yes |
| `DB_ROOT_PASSWORD` | MySQL root password inside the `db` container (used by its healthcheck and initial setup only) | `smartcrm_local_root_password` | Yes (Docker) |
| `APP_ENV` | Environment label | `development` | No |
| `APP_PORT` | API port of the non-Docker setup (`php -S 127.0.0.1:8000`); not used by Docker | `8000` | No |
| `WEB_PORT` | Host port of the web interface | `8080` | No (default 8080) |
| `DEMO_SALES_PASSWORD` | Password given to `demo.sales@smartcrm.local` when the seeder **first** creates it (≥ 8 characters). Empty → random password printed once in the `api` log | `Demo-Sales-2026` | No |
| `DEMO_MANAGER_PASSWORD` | Same, for `demo.manager@smartcrm.local` | `Demo-Manager-2026` | No |

`SESSION_SAVE_PATH` (PHP session folder) is set inside the `api` image to `/tmp/smartcrm-sessions` and does not need to be configured.

**Before any real use**, change every password in `.env`. The real `.env` is excluded by `.gitignore` and `.dockerignore` and must never be committed. Demo passwords are applied only when the accounts are created; to change them later, run `docker compose down -v` and start again (this deletes the data).

---

## 4. Sample data

The `api` container runs, on **every start-up**:

1. `php database/migrate.php $DB_NAME` – creates 5 tables (`roles`, `users`, `customers`, `merge_operations`, `merge_items`) + `schema_migrations`; prints `SKIP: 001_initial already applied.` when they exist.
2. `php database/seed_bt2.php $DB_NAME` – inserts the demo data only if it is missing; never duplicates it.

After the first start the database contains:

| Table | Rows | Content |
|---|---:|---|
| `roles` | 3 | `SALES_STAFF`, `STORE_MANAGER`, `MARKETING` |
| `users` | 2 | `demo.sales@smartcrm.local` (`SALES_STAFF`), `demo.manager@smartcrm.local` (`STORE_MANAGER`) |
| `customers` | 3 | `BT2 DEMO MASTER` and `BT2 DEMO SOURCE` (same phone `0888802026` → **one duplicate pair** to try the merge), `BT2 DEMO NORMAL` (`0888802027`) |
| `merge_operations`, `merge_items` | 0 | filled when a merge is done |

Check the counts:

```bash
docker compose exec db sh -c 'mysql -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e "SELECT (SELECT COUNT(*) FROM roles) roles, (SELECT COUNT(*) FROM users) users, (SELECT COUNT(*) FROM customers) customers"'
```

Expected on a fresh start: `roles 3 · users 2 · customers 3` (MySQL also prints a warning about using a password on the command line – this is normal).

**Demo logins** (with the sample `.env`):

| Email | Password | Can do |
|---|---|---|
| `demo.sales@smartcrm.local` | `Demo-Sales-2026` | Customers: create, search, view, update, delete, duplicates, merge |
| `demo.manager@smartcrm.local` | `Demo-Manager-2026` | Everything above + employee management (page "Nhân viên") |

---

## 5. Verify it works

Run from the project folder after step 4.

| # | Command / action | Expected result |
|---:|---|---|
| 1 | `docker compose ps` | 3 services `db`, `api`, `web`, each with STATUS `Up … (healthy)`; only `web` publishes a port (`0.0.0.0:8080->80/tcp`). The `web` healthcheck may need ~10 s after start-up. |
| 2 | `curl -s http://localhost:8080/api/health` (Windows PowerShell: `curl.exe -s http://localhost:8080/api/health`) | `{"status":"ok","database":"ok"}` |
| 3 | Open **http://localhost:8080**, log in as `demo.sales@smartcrm.local` / `Demo-Sales-2026` | Page "Danh sách khách hàng" lists KH1–KH3 with masked phones (`08******26`, `08******27`) |
| 4 | Click "Hồ sơ trùng" | The pair `BT2 DEMO MASTER` / `BT2 DEMO SOURCE` is listed; selecting master + source and confirming shows "Đã gộp … thành công!" |
| 5 | `docker compose exec api php tests/run_all.php` | Ends with `[PASS] Unit tests`, `[PASS] HTTP integration tests`, `[PASS] BT3 test cases (TC-01..TC-12)` and **`ALL TEST SUITES PASSED`** (about 1 minute). The tests create and drop their own temporary databases `smartcrm_bt2_users_*` / `smartcrm_bt2_tc_*`; the demo data is not modified. |
| 6 | Run one test case only: `docker compose exec api php tests/run_bt3_cases.php TC-05` | `Ket qua : PASS` and `BT3 test cases: 1 passed, 0 failed, 1 total` |
| 7 | Restart test (re-run migration + seeder): `docker compose restart api`, then `docker compose logs api --tail 20` | Log shows `SKIP: 001_initial already applied.` and `SKIP: … already exists`; the counts of section 4 do not change |

### Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `failed to connect to the docker API … dockerDesktopLinuxEngine` | Docker Desktop is not running | Start Docker Desktop, wait for "Engine running", run step 4 again |
| `Bind for 0.0.0.0:8080 failed: port is already allocated` | Port 8080 used by another program | Set `WEB_PORT=8081` (any free port) in `.env`, run step 4 again, open `http://localhost:8081` |
| `dependency failed to start: container smartcrm-db-1 exited (1)` | Invalid database settings in `.env` (e.g. `DB_USER=root`, or `.env` missing) | `docker compose logs db --tail 40`; fix `.env`; `docker compose down -v`; step 4 again |
| `api` stays `unhealthy` | Migration/seeder error | `docker compose logs api --tail 40` (e.g. `DB_NAME` not starting with `smartcrm_bt2_`, demo password shorter than 8 characters) |
| Login fails with the demo password after changing `.env` | The accounts already existed; the seeder never changes existing passwords | `docker compose down -v`, then step 4 again |

**Without Docker:** the manual Windows/XAMPP setup is still documented in `README.md` section 3.

---

## 6. Trial deployment on Railway (optional, bonus)

A public trial copy of the same three services runs on Railway (free trial: USD 5 of credit, 30 days from 13 Nov 2026):

**https://web-production-b5267204.up.railway.app** · health check: `/api/health` → `{"status":"ok","database":"ok"}`

It is built from this repository (branch `dev`, the same `Dockerfile` and `frontend/Dockerfile`); no file is specific to Railway. The demo logins of the trial copy use **different passwords** from `.env.example`; they are written in the BT3 PDF only and are not stored in this repository. The database contains demo data only.

### Steps that were done

| # | Step (Railway dashboard) | Detail |
|---:|---|---|
| 1 | New project → **Docker Image** `mysql:8.0`, service name `db` | Volume mounted at `/var/lib/mysql`. No public domain |
| 2 | Variables of `db` | `MYSQL_DATABASE=smartcrm_bt2_railway`, `MYSQL_USER=smartcrm`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` (two random values typed only into Railway) |
| 3 | New service → **GitHub Repo** (this repository), name `api`, branch `dev`, root directory `/` | Railway builds the root `Dockerfile`. No public domain: the API is reachable only on the private network |
| 4 | Variables of `api` | See the table below |
| 5 | New service → **GitHub Repo** (same repository), name `web`, branch `dev`, **root directory `frontend`** | Railway builds `frontend/Dockerfile` |
| 6 | Variables of `web` | `API_UPSTREAM=${{api.RAILWAY_PRIVATE_DOMAIN}}:8000`, `PORT=80` |
| 7 | `web` → Settings → Networking → **Generate Domain**, target port `80` | Gives the public HTTPS address above; TLS is terminated by Railway |

Variables of the `api` service (set in Railway → Variables; secrets are never written to the repository or baked into an image):

| Variable | Value on Railway | Why |
|---|---|---|
| `DB_HOST` | `${{db.RAILWAY_PRIVATE_DOMAIN}}` | Reference to the private host name of the `db` service |
| `DB_PORT` | `3306` | |
| `DB_NAME` | `smartcrm_bt2_railway` | Must start with `smartcrm_bt2_` |
| `DB_USER` | `smartcrm` | Same as `MYSQL_USER` of `db` |
| `DB_PASSWORD` | `${{db.MYSQL_PASSWORD}}` | Reference – the password is stored once, in the `db` service |
| `API_BIND` | `[::]` | Listen on IPv6 + IPv4; Railway's private network uses IPv6 (default `0.0.0.0` for docker compose) |
| `APP_ENV` | `production` | |
| `DEMO_SALES_PASSWORD`, `DEMO_MANAGER_PASSWORD` | new values (≥ 12 characters) | Not the sample values of `.env.example`, because this copy is public |

Two small changes made the same images usable on a platform without `docker compose` (TD-17 in `docs/thay-doi-thiet-ke.md`): the `web` image reads `PORT` and `API_UPSTREAM` (defaults `80` and `api:8000`), and the `api` start-up script waits for the database (max. 60 s) and reads `API_BIND`.

### Verify the trial copy

| # | Action | Expected result |
|---:|---|---|
| 1 | `curl -s -i https://web-production-b5267204.up.railway.app/api/health` | `HTTP/1.1 200 OK`, body `{"status":"ok","database":"ok"}`, no `X-Powered-By` header |
| 2 | Railway → `api` → Deploy Logs | `SUCCESS: 001_initial applied.`, `SEED SUCCESS: smartcrm_bt2_railway`, `[api] Starting PHP server on [::]:8000` |
| 3 | Open the address, log in as `demo.sales@smartcrm.local` (password: see the BT3 PDF) | "Danh sách khách hàng" lists KH1–KH3 with masked phones |
| 4 | Log in as `demo.manager@smartcrm.local` | The menu "Nhân viên" is available |

### Known limits of the trial copy

- The address stops working when the trial credit or the 30 days run out (about 13 Dec 2026).
- Chrome may show a "Dangerous / deceptive site" label after a password is typed: Google Safe Browsing distrusts login forms on new shared sub-domains (`*.up.railway.app`). The site is not listed in the Safe Browsing site status ("No available data", checked 13 Nov 2026). Use only the demo accounts; never type a real password.
- Security checklist item 3 (store-based access, QT-14) is not implemented, so every logged-in demo user sees all demo customers.
- The session cookie has `HttpOnly` and `SameSite=Strict` but not `Secure`; a real deployment should set `session.cookie_secure=1` behind HTTPS.
- Sessions are stored in the container file system and are lost on every redeploy (users must log in again).
