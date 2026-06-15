# JSD Construction - cPanel go-live runbook

This is the step-by-step to take the site live on
**https://jsdconstruction.com.au** from GoDaddy Web Hosting Plus with cPanel.

It does not touch DNS or MX. Titan email receiving is unaffected. The site only
SENDS mail over SMTP.

## Target server layout

```
/home/<acct>/jsd_config/config.php          secrets, ABOVE the web root
/home/<acct>/public_html/                    contents of the repo's public_html
/home/<acct>/public_html/uploads/            client photos, created on server
    ongoing/  finished/  branding/  thumbs/
/home/<acct>/logs/                            mail logs, above the web root
```

`api/db.php` and `includes/bootstrap.php` resolve config with
`dirname(__DIR__, 2) . '/jsd_config/config.php'`, so `jsd_config` must sit
directly beside `public_html`.

---

## 1. Create the database

1. cPanel > **MySQL Databases**.
2. Create a database, e.g. `jsdcons_site`.
3. Create a database user with a strong password.
4. Add the user to the database and grant **All Privileges**.
5. Note the final database name, username and password (cPanel prefixes them
   with your account name).

## 2. Import the schema

1. cPanel > **phpMyAdmin** > select the database.
2. **Import** tab > choose `db/schema.sql` from the repo > Go.
3. Confirm the tables `enquiries`, `media`, `admin_users` now exist.

## 3. Create the secret config (above the web root)

1. cPanel > **File Manager** > go to the account home (the folder that contains
   `public_html`).
2. Create a folder `jsd_config`.
3. Inside it create `config.php`. Use `config.sample.php` from the repo as the
   template and fill in:
   - `APP_ENV` => `prod`
   - `DB_HOST` (usually `localhost`), `DB_NAME`, `DB_USER`, `DB_PASS`
   - Titan SMTP: `SMTP_HOST=smtp.titan.email`, `SMTP_PORT=465`,
     `SMTP_SECURE=ssl` (or `587`/`tls`), `SMTP_USER` = a full mailbox address,
     `SMTP_PASS`, `SMTP_FROM`, `SMTP_FROM_NAME`.
   - `QBCC_LICENCE`, `ABN`, `BUSINESS_HOURS` once supplied.
   - Leave `DB_DSN` unset (that is for testing only).
4. Leave reCAPTCHA keys empty unless you are enabling it.

> Never put `config.php` inside `public_html` and never commit it.

## 4. Deploy the code

**Option A - cPanel Git Version Control**
1. cPanel > **Git Version Control** > Create.
2. Clone `ghuggi`, then set the deployment so the repo's `public_html` maps to
   the server `public_html`. (Web Hosting Plus supports a `.cpanel.yml` deploy;
   if you prefer, use Option B which is simpler.)

**Option B - Zip upload (simplest)**
1. Download the repo as a zip from GitHub.
2. Extract locally, then upload the **contents of `public_html/`** into the
   server `public_html` (not the folder itself, its contents).
3. Upload `db/` and `docs/` only if you want them on the server (not required).

Either way, confirm `public_html/index.php`, `assets/`, `api/`, `admin/`,
`vendor/` and the two `.htaccess` files are present.

## 5. Create the uploads directory (must survive future pulls)

1. In `public_html`, create `uploads/` with sub-folders `ongoing`, `finished`,
   `branding`, `thumbs`.
2. Set them writable (typically `0755`; if uploads fail, try `0775`).
3. These are gitignored. A future `git pull` or re-upload of code must never
   delete `uploads/` or the database, so client photos and logins are safe.

> The admin also auto-creates these folders and a protective `uploads/.htaccess`
> on first upload, but creating them now avoids permission surprises.

## 6. Enable HTTPS

1. cPanel > **SSL/TLS Status** or **AutoSSL** > run AutoSSL for the domain.
2. Confirm `https://jsdconstruction.com.au` loads with a valid certificate.
3. The root `.htaccess` already forces HTTPS.

## 7. Create the admin account (one time)

1. Visit `https://jsdconstruction.com.au/admin/setup.php`.
2. Set the admin username and a strong password (10+ characters).
3. Submit. The page now locks; `setup.php` will redirect to login from here on.

## 8. Add your content

1. Log in at `https://jsdconstruction.com.au/admin`.
2. **Branding** tab: upload the logo. It appears in the header immediately and a
   favicon is generated.
3. **Ongoing** tab: upload current build photos (these feed the Photos carousel).
4. **Finished** tab: upload completed projects, add captions, and toggle the
   star on up to **3** to feature them on the homepage.
5. Drag to reorder, edit captions inline, hide/show or delete as needed.

## 9. Turn on real email and test the form

1. In the Titan webmail/admin for the **sending** mailbox (e.g. `contact@`):
   - Enable **third-party app access**.
   - **Disable two-factor auth** on that mailbox.
   (Without these, SMTP login fails. This does not affect mail receiving/MX.)
2. With `APP_ENV=prod` in `config.php`, submit the public contact form.
3. Confirm:
   - a new row appears in the `enquiries` table (phpMyAdmin), and
   - the chosen JSD mailbox receives the alert email, and
   - the customer address receives the branded thank-you.
   If sending fails, check `/home/<acct>/logs/mail-error.log`.

## 10. Final checks

1. Click the WhatsApp button - it should open `wa.me/61424475767`.
2. Resize/test the layout at 360, 768, 1280 and 1920px.
3. Run **Lighthouse** on the live URL (Chrome DevTools > Lighthouse, or
   `npx lighthouse https://jsdconstruction.com.au --view`). Aim for 90+ across
   Performance, Accessibility, Best Practices and SEO.

The site is live.

---

## Updating the site later

- Push code changes to `ghuggi`, then re-deploy `public_html` (Option A pull or
  Option B re-upload). **Do not touch** `uploads/`, `jsd_config/` or the
  database during updates - they hold client content and must persist.

## Backups (recommended)

- **Database**: phpMyAdmin > Export > Quick > SQL, on a schedule. This backs up
  enquiries and the media index so accidental deletions are recoverable.
- **Uploads**: periodically download `public_html/uploads/` (File Manager >
  Compress > download), or use cPanel Backup. The images themselves live only on
  the server.

## Troubleshooting

| Symptom | Likely cause / fix |
|---|---|
| Blank page or 500 | Check `config.php` exists above `public_html` and DB creds are correct. |
| Uploads fail | `uploads/` and sub-folders must be writable. Check PHP `upload_max_filesize`/`post_max_size` >= 8MB. |
| No WebP output | Hosting PHP lacks GD WebP. Enable the GD extension in cPanel **Select PHP Version > Extensions**. |
| Emails not sending | Enable third-party access and disable 2FA on the sending mailbox; verify SMTP host/port/secure. See `logs/mail-error.log`. |
| Logo not showing | Upload it in the admin **Branding** tab; the header falls back to the text wordmark until then. |
