# Eduvixo

Eduvixo is an education digital experience and communication platform. This repository contains the multilingual Eduvixo product website, support pages and the secured Marketplace delivery layer.

## Public application

The web server document root must point to `public/`. Application code, translations, runtime state and Marketplace packages remain outside the public directory. The root `.htaccess` is a protected fallback for hosting environments whose document root cannot be changed.

## Local-only resources

Credentials under `.cfg/`, CMS distribution sources under `.cms/`, working documents under `.doc/`, runtime storage and release archives are intentionally excluded from version control. Marketplace packages are deployed to private server storage and are delivered only through the application download endpoints.

## Requirements

- PHP 8.2 or newer
- Apache with `mod_rewrite`, or an equivalent Nginx front-controller configuration
- PHP cURL and JSON extensions

Project decisions, deployment notes and verification records are maintained in `.wrk/`.
