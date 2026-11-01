# All Saints' Anglican Church, Yaba — Paystack Donation System

A PHP + MySQL donation platform with Paystack Inline checkout, a printable
receipt, email confirmation, and an admin dashboard for viewing, filtering,
and exporting donations.

## Folder structure

```
assets/css/style.css      Blue/white/gold theme, mobile responsive
assets/js/script.js       Form validation + Paystack Inline trigger
assets/images/            Put church-logo.png here
includes/config.php       DB + Paystack keys + church settings (EDIT THIS)
includes/db.php           PDO connection
includes/functions.php    Sanitization, CSRF, email, reference generator
includes/header.php       Shared page header/nav
includes/footer.php       Shared page footer
payments/donate.php       Public donation form
payments/verify.php       Server-side Paystack verification + DB save
payments/receipt.php      Printable receipt
admin/login.php           Admin login
admin/logout.php          Admin logout
admin/dashboard.php       Summary stats
admin/view_donations.php  Search/filter/table
admin/export_excel.php    CSV export (opens in Excel)
admin/export_pdf.php      Print-to-PDF report
database/schema.sql       Full DB schema + seed admin user
```

## Setup

1. **Create the database.**
   ```
   mysql -u root -p < database/schema.sql
   ```
   This creates the `allsaints_donations` database, the `donations` and
   `admin_users` tables, and a seed admin account.

2. **Edit `includes/config.php`:**
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` — your MySQL credentials.
   - `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY` — from your
     [Paystack dashboard](https://dashboard.paystack.com/#/settings/developers).
     Use **test** keys first; switch to **live** keys only when you go live.
   - `SITE_URL`, `CHURCH_EMAIL` — your live domain and sender address.

3. **Seed admin login.** Default username `admin`, password `ChangeMe123!`.
   **Log in once and change this password immediately** — either add a
   "change password" form, or generate a new hash with:
   ```php
   php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT);"
   ```
   and update the `admin_users.password_hash` column directly.

4. **Add your logo** at `assets/images/church-logo.png` (48x48 or larger,
   square works best).

5. **Point your web server's document root** at this project folder (or a
   subfolder if hosting under a path), and make sure `includes/.htaccess`
   is respected (Apache with `AllowOverride All`, or add an equivalent
   Nginx `location` block denying access to `/includes/`).

6. **Enable HTTPS.** Paystack requires your live site to be served over
   HTTPS before you can use live keys.

7. Visit `/payments/donate.php` to test a donation (use Paystack's test
   card numbers in test mode), and `/admin/login.php` for the dashboard.

## Security notes already built in

- All SQL uses **PDO prepared statements** — no string-concatenated queries.
- All user-supplied output is passed through `htmlspecialchars()` before
  being echoed, to prevent Cross-Site Scripting (XSS).
- Forms include a **CSRF token** validated server-side.
- Every payment is **re-verified server-side** against Paystack's API
  using the secret key — the browser's "success" message is never trusted
  on its own.
- Admin passwords are stored as **bcrypt hashes**, never plain text.
- The `includes/` folder (DB credentials, secret key) is blocked from
  direct browser access via `.htaccess`.
- Donation categories are validated against a fixed whitelist server-side,
  not just filtered in the dropdown.

## Production TODO

- Replace PHP's `mail()` in `functions.php` with PHPMailer/SMTP or a
  transactional email API for reliable delivery.
- Consider adding rate limiting / a CAPTCHA on the donation form to deter
  automated abuse.
- Optionally add Paystack webhook handling (`payments/webhook.php`) as a
  second, asynchronous confirmation channel in addition to `verify.php`.
