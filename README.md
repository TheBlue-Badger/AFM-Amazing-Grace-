# AFM Amazing Grace Center — Website

A PHP + MySQL church website for AFM Amazing Grace Center (Eersteriver, Cape Town),
built with a live-editable store, an admin dashboard, and a real-time chat widget.

## What's included

- **Public pages:** `index.php` (home), `about.php`, `events.php` (events, calendar & year planner),
  `gallery.php` (photo booth), `livestream.php` (live video + Twitch-style comment wall),
  `shop.php` (Give & Shop store), `contact.php`
- **OBS overlay:** `stream-overlay.php`, a transparent page that shows live viewer comments.
  Add it as a Browser Source in OBS/Streamlabs so comments appear on your broadcast itself.
- **Admin area:** `/admin` — store products (with images), events (with posters), gallery
  (bulk photo upload), livestream control and comment moderation, live chat inbox, contact
  form inbox, giving/order log, admin account management, and password management
- **Developer tab:** whoever logs in with the email set in `DEVELOPER_EMAIL`
  (`includes/config.php`) automatically sees an extra tab with site performance stats
  (self-hosted, no third-party analytics) and system diagnostics
- **APIs:** `/api` — cart (add/update/remove/checkout) and live chat (start/send/poll)
- **Data:** MySQL/MariaDB database, tables created and seeded automatically on first run
- **Uploads:** `uploads/products`, `uploads/events`, `uploads/gallery` for admin-uploaded images

## Requirements

- PHP 8.1 or newer
- A MySQL or MariaDB database (every shared host, including cPanel/free hosts, provides this)
- The `pdo_mysql` and `fileinfo` PHP extensions (both installed by default on almost every host)
- Apache with `.htaccess` support (`AllowOverride All`) **or** an equivalent Nginx config (see below)

## Quick start (local testing)

1. Create a database and a database user (or use an existing local MySQL/MariaDB install):
   ```sql
   CREATE DATABASE church_test;
   CREATE USER 'church_user'@'localhost' IDENTIFIED BY 'church_pass';
   GRANT ALL PRIVILEGES ON church_test.* TO 'church_user'@'localhost';
   ```
2. Update the `DB_*` constants near the top of `includes/config.php` to match.
3. Run the built-in PHP server:
   ```bash
   cd church
   php -S localhost:8000
   ```

Visit `http://localhost:8000/index.php`. Tables and an admin account are created
automatically the first time any page connects to the database.

## Deploying to a real host (e.g. cPanel / shared hosting)

1. In your host's control panel (e.g. cPanel's "MySQL Databases"), create a database, a
   database user, and grant that user access to the database. Note the four values it
   gives you: host (usually `localhost`), database name, username, and password.
2. Enter those four values into the `DB_*` constants near the top of `includes/config.php`.
3. Upload the entire `church/` folder's **contents** to your web root (e.g. `public_html`).
4. Make sure the `data/` and `uploads/` folders (and their subfolders) are writable by the
   web server (`chmod -R 770 data uploads` is usually enough; ask your host if you're not
   sure how permissions work there).
5. Visit your site once, this creates the database tables and your first admin account.
6. Open `data/admin_credentials.txt` (via your host's file manager or FTP) to get your
   one-time admin username and password.
7. Log in at `yoursite.com/admin/login.php`, then **immediately** change your password
   from the Account tab.
8. Delete `data/admin_credentials.txt` from the server once you've logged in.
9. Edit the remaining constants at the top of `includes/config.php`, your real address,
   phone, email, social links, and banking details for EFT giving.

### Nginx note

`.htaccess` files only work on Apache. If you're on Nginx, add this to your server block
instead, so the database and includes folders can never be requested directly:

```nginx
location ~ ^/(includes|data)/ {
    deny all;
    return 403;
}
```

## Security notes (please read before launch)

- **No shared default password.** Each install generates its own random admin password
  at first run — there's no hardcoded "admin/admin" anywhere in this codebase.
- **Passwords are hashed** with PHP's `password_hash()` (bcrypt/argon2 depending on your
  PHP build) — never stored or logged in plain text.
- **CSRF tokens** are required on every form submission and API call that changes data.
- **Login attempts are rate-limited** per IP address (6 attempts per 15 minutes).
- **All database queries use prepared statements** (PDO) — no string-concatenated SQL.
- **All dynamic output is escaped** with `htmlspecialchars()` before being printed.
- The `includes/` and `data/` folders are blocked two ways: a `.htaccess` deny rule
  (Apache) and a PHP-level check that refuses to run those files if requested directly,
  so you're still protected even if `.htaccess` support is misconfigured on your host.
- For extra safety, keep `includes/config.php` out of version control if you ever push this
  to a public git repo, since it holds your database password. Consider environment
  variables instead if your host supports them.
- Always serve the live site over HTTPS. Session cookies are automatically marked
  `Secure` when PHP detects an HTTPS connection.

## Admin accounts and developer access

- The **Admins** tab lets any logged-in admin add or remove staff accounts (username,
  email, password). At least one admin always has to remain, and nobody can remove
  their own account while logged in as it.
- **`DEVELOPER_EMAIL`** in `includes/config.php` is the one thing that's hardcoded rather
  than managed from the UI, on purpose, so you always keep access no matter what happens
  in the Admins tab. Whichever admin account has this email automatically sees an extra
  **Developer** tab with site performance stats and system diagnostics, nobody has to
  grant that access manually, and that account can't be deleted through the UI.
- To hand developer access to a different email later, just change `DEVELOPER_EMAIL` in
  the config file and make sure an admin account exists with that email.
- Log in with either a username or an email, both work in the same login field.

## Going further

This template is deliberately backend-complete but **does not process live payments**.
At checkout, visitors choose "pay online" (shown your EFT/banking details) or "pay in
person" (a reference number to bring to church) — both are logged in the admin Orders
tab for you to reconcile manually. When you're ready to accept real card payments,
popular South African options are **PayFast**, **Yoco**, and **PayGate** — ask me and
I can wire one into `api/checkout.php` when you have a merchant account.

## Editing content

Most page copy, service times, and leadership names live directly in the `.php` files
(`index.php`, `about.php`, `contact.php`) as plain HTML — search for the text you want
to change and edit it directly. Leadership cards marked **[Add Name]** on the About page
are placeholders for roles that don't have a name yet.

Store products, prices, and giving options are **not** hardcoded — manage them live from
`/admin` → Store products.

## Folder structure

```
church/
├── index.php, about.php, shop.php, contact.php   # public pages
├── admin/                                        # staff-only dashboard (session-gated)
├── api/                                          # cart + chat endpoints (CSRF-protected)
├── includes/                                     # config, db, shared header/footer (blocked from direct access)
├── assets/css, assets/js                         # stylesheet + client-side JS
├── images/                                       # logo, photos
└── data/                                         # one-time admin credentials file (blocked from direct access)
```
