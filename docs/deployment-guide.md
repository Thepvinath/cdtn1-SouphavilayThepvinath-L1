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
