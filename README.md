# Redeyed Sentinel CAPTCHA (Drupal 10/11)

Add friendly, privacy-respecting human verification to your Drupal **login**
and **registration** forms with [Redeyed Sentinel](https://redeyed.com). This is
a standalone module — it does **not** require the contrib CAPTCHA module.

**Free to install.** The module stays inert until you add your keys. With no
keys the widget renders nothing and verification fails open, so your forms keep
working while you get set up.

## Install

1. Copy the `redeyed_sentinel` folder into your site's
   `modules/custom/` directory:

   ```
   web/modules/custom/redeyed_sentinel/
   ```

   (Or require it with Composer if you publish it to a package repository.)

2. Enable the module:

   ```bash
   drush en redeyed_sentinel
   ```

   Or via the UI at **Extend** (`/admin/modules`).

## Configure

Grab a **Site Key** and **API Key** from the Redeyed Lab:
**Developer → Sentinel → Sites + API Keys**.

Go to **Administration → Configuration → People → Redeyed Sentinel CAPTCHA**
(`/admin/config/people/redeyed-captcha`) and enter:

| Field        | Purpose |
|--------------|---------|
| Site key     | Public key that renders the widget. Safe to expose. |
| API key      | Secret key used only for server-side verification. Keep private. |
| Base URL     | Sentinel service URL. Defaults to `https://redeyed.com`. |

> Until **both** the site key and API key are set the module is **inert**:
> nothing renders and verification passes automatically (fail open). Forms are
> never blocked by missing configuration.

## How it works

| Step   | Detail |
|--------|--------|
| Render | `hook_form_alter()` adds `{base_url}/sentinel.js` and a `sentinel-captcha` div (using your site key) to the login and registration forms. |
| Submit | The Sentinel widget injects a hidden `sentinel-token` field. |
| Verify | A validation handler POSTs to `{base_url}/api/v1/verify` via `\Drupal::httpClient()` with header `X-Api-Key: {api_key}` and body `{"site_key": "...", "token": "..."}`. |
| Pass   | Only when the JSON response has `data.success === true` or top-level `success === true`; otherwise the form shows *"Human verification failed — please try again."* |

The secret API key is sent only in the `X-Api-Key` request header — it is never
rendered to the page or written to logs.

## Requirements

- Drupal 10 or 11
- PHP 8.1+ (per Drupal core requirements)

## License

MIT © 2026 Redeyed Corporation
