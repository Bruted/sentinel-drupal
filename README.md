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

Grab a **Site Key** and **Secret Key** from the Redeyed Lab:
**Sentinel → Sites**. The Secret Key is shown only once, when you create the
site — copy it then.

Go to **Administration → Configuration → People → Redeyed Sentinel CAPTCHA**
(`/admin/config/people/redeyed-captcha`) and enter:

| Field        | Purpose |
|--------------|---------|
| Site key     | Public key that renders the widget. Safe to expose. |
| Secret key   | Secret key used only for server-side verification. Keep private. |
| Base URL     | Sentinel service URL. Defaults to `https://redeyed.com`. |

> Until **both** the site key and secret key are set the module is **inert**:
> nothing renders and verification passes automatically (fail open). Forms are
> never blocked by missing configuration.

### Widget customization (optional)

Under **Widget customization (optional)** you can fine-tune how the widget looks
and behaves. Every field is optional and backward-compatible — leave any at its
default to use the Sentinel widget defaults. Each renders as a `data-*` attribute
on the `sentinel-captcha` div, but **only when it is non-default**: `Auto` theme,
`Default` scheme, `Server default` widget, `Adaptive` difficulty and empty
width/form are omitted from the markup entirely.

| Field        | Attribute         | Purpose |
|--------------|-------------------|---------|
| Widget type  | `data-widget`     | Which challenge to render: `behavioral`, `pow`, `text_math`, `image_puzzle`, `rotate_align`, `press_hold` (or *Server default*). |
| Theme        | `data-theme`      | `auto` (default), `light` or `dark`. |
| Colour scheme| `data-scheme`     | `default`, `ocean`, `forest`, `sunset`, `graphite`, `royalty`, `ruby`, `hacker`, `monochrome`, `midnight`, `aurora`. |
| Difficulty   | `data-difficulty` | Minimum challenge strength: *Adaptive* (default), `easy`, `medium`, `hard`, `max`. |
| Width        | `data-width`      | Widget width, e.g. `full`, `100%` or `340px`. |
| Form key     | `data-form`       | Optional form identifier passed to Sentinel for per-form analytics. |

> **Difficulty only raises challenge strength above the adaptive baseline.** It
> never lowers it — Sentinel still escalates on its own when it sees risk, so a
> low difficulty will not weaken protection for suspicious traffic.

## How it works

| Step   | Detail |
|--------|--------|
| Render | `hook_form_alter()` adds `{base_url}/sentinel.js` and a `sentinel-captcha` div (using your site key) to the login and registration forms. |
| Submit | The Sentinel widget injects a hidden `sentinel-token` field. |
| Verify | A validation handler POSTs to `{base_url}/sentinel/siteverify` via `\Drupal::httpClient()` with body `{"secret": "...", "response": "...", "remoteip": "..."}` (the `remoteip` is the client IP and is optional). |
| Pass   | Only when the JSON response has top-level `success === true` (the response also carries `outcome` and `score`); otherwise the form shows *"Human verification failed — please try again."* |

The secret key is sent only in the request body — never rendered to the page or
written to logs. This is a reCAPTCHA/Turnstile-style flow: your site's own
secret key authenticates the verify call, so no developer API key is required.

## Requirements

- Drupal 10 or 11
- PHP 8.1+ (per Drupal core requirements)

## License

MIT © 2026 Redeyed Corporation
