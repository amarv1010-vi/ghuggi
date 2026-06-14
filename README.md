# JSD Construction website

Production marketing website for JSD Construction Pty Ltd, a Brisbane builder,
plus a hardened client admin portal for managing photos and branding.

## Stack

Static HTML, CSS and vanilla JS frontend. PHP and MySQL backend. PHPMailer over
Titan SMTP. No WordPress, no React, no Node, no build step. Runs on GoDaddy Web
Hosting Plus with cPanel.

## Architecture at a glance

- Single page site at `public_html/index.php` with a sticky nav.
- Client admin at `/admin` to upload and manage images and the logo with no
  cPanel or FTP. The public site reads the same media store live.
- Three media folders, each a server directory plus `media` rows: `ongoing`
  feeds the Photos carousel, `finished` feeds the gallery and the Featured
  block (max 3 featured), `branding` feeds the header logo and favicon.

## Hard rules

- Secrets live in `jsd_config/config.php` ABOVE `public_html`, never in the
  repo. Only `config.sample.php` is committed.
- `public_html/uploads/` and the `media` plus `admin_users` tables live on the
  server and are gitignored. A `git pull` must never wipe client photos or
  logins.
- No hardcoded image filenames. Everything renders from an empty state and
  auto-populates from the media store.

## Layout

```
ghuggi/
├── config.sample.php      template, copy to jsd_config/config.php on server
├── .gitignore
├── README.md
├── db/schema.sql          enquiries, media, admin_users
├── docs/DEPLOY.md         cPanel go-live runbook
└── public_html/
    ├── index.php
    ├── .htaccess
    ├── robots.txt, sitemap.xml
    ├── assets/{css,js,fonts}/
    ├── api/{db,mailer,submit,gallery}.php
    ├── includes/{header,footer}.php
    ├── admin/
    └── vendor/            PHPMailer
```

Server-only, gitignored, created during deploy:

```
jsd_config/config.php
public_html/uploads/{ongoing,finished,branding,thumbs}/
```

## Deploy

See `docs/DEPLOY.md` for the full cPanel go-live runbook.

## Status

Built in gated phases. See commit history.
