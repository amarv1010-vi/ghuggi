# JSD Construction - cPanel go-live runbook

Full step-by-step is written in Phase 9. This stub records the deploy shape so
the architecture stays consistent across phases.

## Server layout (target)

```
/home/<acct>/jsd_config/config.php          secrets, above web root
/home/<acct>/public_html/                    repo public_html contents
/home/<acct>/public_html/uploads/            client photos, created on server
    ongoing/  finished/  branding/  thumbs/
```

## Outline (expanded in Phase 9)

1. cPanel MySQL: create database and user, grant all, note creds.
2. phpMyAdmin: import `db/schema.sql`.
3. Create `jsd_config/config.php` above public_html from `config.sample.php`,
   set DB and Titan SMTP creds, `APP_ENV=prod`.
4. Deploy code from `ghuggi` (cPanel Git Version Control, or zip extract of
   `public_html` contents).
5. Create `public_html/uploads/{ongoing,finished,branding,thumbs}`, make
   writable, confirm gitignored so future pulls never wipe them.
6. cPanel AutoSSL on, confirm HTTPS forced.
7. Visit `/admin/setup.php`, set the admin password, confirm setup locks.
8. Log in, upload logo in Branding, add Ongoing and Finished photos, feature 3.
9. Enable third-party access and disable 2FA on the Titan sending mailbox, then
   test the contact form end to end.
10. Confirm wa.me button, mobile layout and Lighthouse on the live URL.
