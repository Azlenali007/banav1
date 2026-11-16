# ApexSMM Panel — cPanel Deployment, Setup & API Guide

## 1. cPanel Hosting Deployment Instructions
1. **Requirements**: PHP 8.1+ (with `pdo`, `pdo_mysql`, `curl`, `openssl`, `json`, `mbstring`, `fileinfo` extensions) and MySQL 8.0+ / MariaDB 10.4+. No Node.js, npm, or Composer required.
2. **Upload Files**: Upload all root directories (`admin/`, `user/`, `api/`, `themes/`, `config/`, `core/`, `includes/`, `modules/`, `cron/`, `storage/`, `database/`, `install/`) and root files (`index.php`, `.htaccess`, etc.) directly into `public_html/` (or your addon domain document root).
3. **Directory Permissions**: Ensure `config/` and `storage/` (including `logs/`, `backups/`, `cache/`, `uploads/`, `temp/`) are writable (`0755` or `0775`).
4. **Create MySQL Database**: In cPanel -> **MySQL Databases**, create a database, a database user, and grant **All Privileges**.
5. **Run Web Installer**: Visit `https://yourdomain.com/install/` in your browser:
   - Verify server requirements & folder permissions.
   - Enter MySQL credentials and click **Test Connection**.
   - Enter your Panel Brand Name and initial **Super Admin** credentials, then click **Install & Lock Setup**.
   - The installer automatically executes `database/install.sql` & `database/seed.sql`, generates `config/database.local.php` and `config/security.local.php` with random AES-256 encryption and cron tokens, and creates `storage/installed.lock`.
6. **Post-Install Security**: Optionally remove or restrict `/install/` after setup completes.
7. **Existing Installations (Upgrades)**: Apply any new SQL files under `database/updates/` (such as `database/updates/002_seed_default_pages.sql`, which safely uses `INSERT IGNORE`) without resetting or overwriting existing production data.

## 2. Admin Setup Instructions
1. Sign in at `https://yourdomain.com/admin/login.php` using the Super Admin account created during installation.
2. **Website Settings** (`Admin -> Settings`): Set brand name, currency code/symbol, timezone, SEO metadata, landing content/FAQs, and SMTP email configuration (test email delivery using the built-in test form).
3. **Payment Gateways** (`Admin -> Payments & Gateways`):
   - **Razorpay**: Enter your Razorpay Key ID & Key Secret, set min/max limits, and enable Razorpay.
   - **Manual Payments**: Enter your UPI ID / Bank Transfer instructions, set min/max limits, and enable Manual Deposits.
4. **Providers & Services** (`Admin -> Categories`, `Providers`, `Services`):
   - Add categories first.
   - Add external SMM v2 API providers (API URL + API Key, encrypted at rest), click **Test & Sync**, then **Import Services** with percentage or fixed markup.
5. **Staff & Roles** (`Admin -> Roles & Permissions`, `Staff Admins`): Create restricted staff roles with granular module permissions.

## 3. cPanel Cron Job Setup
Configure the following cron jobs in cPanel -> **Cron Jobs** (adjust PHP binary path if your host uses `/usr/local/bin/php` or `ea-php82`):
- `* * * * * /usr/local/bin/php /home/YOURUSER/public_html/cron/process-orders.php >/dev/null 2>&1`
- `*/2 * * * * /usr/local/bin/php /home/YOURUSER/public_html/cron/sync-orders.php >/dev/null 2>&1`
- `*/15 * * * * /usr/local/bin/php /home/YOURUSER/public_html/cron/sync-providers.php >/dev/null 2>&1`
- `*/5 * * * * /usr/local/bin/php /home/YOURUSER/public_html/cron/process-refills.php >/dev/null 2>&1`
- `0 3 * * * /usr/local/bin/php /home/YOURUSER/public_html/cron/cleanup.php >/dev/null 2>&1`

## 4. User Setup & Ordering Flow
1. Register at `/register.php` or sign in at `/login.php`.
2. Visit **Add Funds** (`/user/add-funds.php`) to fund your wallet via Razorpay or Manual Payment.
3. Visit **New Order** (`/user/new-order.php`) or **Bulk Order** (`/user/bulk-order.php`) to place orders.
4. Track order statuses, refill requests, and automated refunds in **Order History** (`/user/orders.php`) and **Transactions** (`/user/transactions.php`).

## 5. Reseller API v1 Documentation
- **Endpoint**: `POST https://yourdomain.com/api/v1/`
- **Parameters** (`application/x-www-form-urlencoded` or `application/json`):
  - `key`: User API key generated in `/user/api-access.php`
  - `action`: `balance` | `services` | `add` | `status` | `refill`
  - For `add`: `service`, `link`, `quantity`, optional `idempotency_key`
  - For `status` / `refill`: `order` (numeric order ID or `order_uid`)
