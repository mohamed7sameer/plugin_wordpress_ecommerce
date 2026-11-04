# BookApp — Agent Instructions

The product name is **BookApp**. Public API namespace is `bookapp/v1` (headers `X-BookApp-*`).

Remote WordPress + WooCommerce store. This workspace is **not** the WordPress codebase. Do not invent a local `wp-content` tree.

The job of this project is a **mobile app API** (products, cart, wishlist, compare, orders, customers). Inspect and change the live site through the right tool: **WordPress MCP** for WP content, **SSH + WP-CLI** for server/CLI, **Hostinger MCP** only if the user asks about this Hostinger account. Read `credential` for connection details. Never copy those secrets into `AGENTS.md`, commits, chat, plugins, or MCP config.

- **Site:** https://books.ay7aaga.com
- **GitHub backend (this workspace):** https://github.com/mohamed7sameer/plugin_wordpress_ecommerce
- **GitHub Flutter app:** https://github.com/MohamedSaidEbaid/books_ay7aaga
- **Admin (hidden login):** https://books.ay7aaga.com/beoneentrence/
- **Docroot:** `/home/u281506410/domains/books.ay7aaga.com/public_html`
- **Theme:** WoodMart + `woodmart-child` — treat parent as vendor code
- **Commerce:** WooCommerce (books). Wishlist + compare are **WoodMart built-in**, not separate plugins
- **Custom API code:** plugin `bookapp-mobile-api` only (create it when API work starts)
- **Languages:** Polylang — Arabic `ar` (default) + English `en`
- **Hosting:** Hostinger — user `u281506410` / `186.242.129.217`. SSH alias `books_ay7aaga`. Not the ProTech cPanel box
- **Cache:** WP-Optimize (`wp-optimize`) + Hostinger CDN — **purge after every change**
- **Credentials:** WordPress, SSH, WP-CLI, and the Application Password live only in `credential`
- **API docs (keep in sync):** `bookapp-api-docs.html` + `bookapp-api.http` at the project root
- **Auth token:** JWT from login, register, and password change lasts **1 year** (`31536000` seconds, `YEAR_IN_SECONDS`). `issue_token()` always uses that lifetime. A shorter `expires_in` from the app is ignored.

---

## Hard rules

1. Use **WordPress MCP** (`user-wordpress-mcp` from Cursor `mcp.json` → `wordpress-mcp`) for WordPress content read/write that MCP covers. Discover tools first. Do not guess endpoints or invent local theme/plugin files.
2. Use **SSH + WP-CLI** when the job needs files, cache, diagnostics, plugin/theme inspect, or CLI that WordPress MCP cannot do. Details are in **Server access** below. Connection secrets are only in `credential` and Cursor `mcp.json`.
3. **Never edit the WoodMart parent theme** — not `woodmart/`, not Theme File Editor, not parent `functions.php`.
4. **Do not dump API / business logic into `woodmart-child`.** A thin child theme already exists (`functions.php`, `style.css`). Leave it alone unless the user explicitly asks for a WoodMart display tweak that cannot live in a plugin.
5. **All mobile API code** (custom REST routes, wrappers around WooCommerce/WoodMart wishlist/compare, app-only logic) goes in a dedicated plugin: `wp-content/plugins/bookapp-mobile-api/`. Create that plugin when API work starts. Do not use WPCode, mu-plugins, or theme files for the API.
6. Prefer **existing APIs first**, then wrap gaps in `bookapp-mobile-api`:
   - WooCommerce REST `wc/v3` — products, orders, customers, coupons
   - WooCommerce Store API `wc/store/v1` — cart / checkout (session + Store API nonce / cart-token)
   - WoodMart wishlist + compare (no public first-party REST — wrap in the custom plugin)
   - JWT: `jwt-auth/v1` is NOT in the REST index on the new server (checked 2026-10-13); the app logs in via `bookapp/v1/auth/login`. Re-check before relying on `jwt-auth/v1`. Reuse it for the app; do not install a second JWT plugin unless the user asks
7. The site is **bilingual (Polylang)**. Default language is **Arabic**. Before content or product-facing API work, confirm `ar` / `en` and return/translate in the language the client asked for.
8. The Cursor **cPanel MCP** (`user-cpanel`) is wired to **another project** (`lbp-support.com`). Do **not** use it here. Hostinger WordPress MCP is hosting-level (installs, cache on Hostinger). Prefer SSH/WP-CLI for this site unless the user asks about Hostinger hosting.
9. **Purge cache after every change** (WP-Optimize + Hostinger cache if HTML/API responses look stale). Details in **Cache** below.
10. Do not activate/deactivate plugins, change permalinks, or change the hidden-login slug unless the user asked.
11. **Any change to this project’s API or how the app should call it is unfinished until both local docs are updated:** `bookapp-api-docs.html` and `bookapp-api.http`. Details in **API documentation** below.
12. **Dual publish (required):** any backend/plugin/docs change is unfinished until it is **both** (a) committed + pushed to the GitHub backend repo `plugin_wordpress_ecommerce`, and (b) deployed to the live server plugin path + cache flushed. Do not leave GitHub ahead of the server or the server ahead of GitHub.

---

## Cursor MCP servers (required)

This machine has the live servers in Cursor `mcp.json` (`C:\Users\Admin\.cursor\mcp.json`). **Use `wordpress-mcp` for this site.** Do not invent a second WordPress connection, and do not use the `cpanel` server (that is ProTech).

| Job | `mcp.json` key | Cursor namespace | Auth |
| --- | --- | --- | --- |
| WordPress content + MCP tools | `wordpress-mcp` | `user-wordpress-mcp` | Application Password in `mcp.json` / `credential` |
| Hosting (this Hostinger account, if asked) | project `.cursor/mcp.json` → `hostinger-hosting` / `hostinger-wordpress` (dest account) | `user-hostinger-*` / project MCP | Token in project `.cursor/mcp.json` (gitignored) + `credential` |
| Shell, files, WP-CLI | SSH `books_ay7aaga` | — | key `~/.ssh/my_hostinger` — see `credential` |

Site plugin: **WordPress MCP** (`wordpress-mcp-trunk`, Automattic, v0.2.5) — endpoint `/wp-json/wp/v2/wpmcp`. The local proxy is `@automattic/mcp-wordpress-remote`. `WP_API_URL` is the site root (`https://books.ay7aaga.com/`), not the hidden-login path.

```json
"wordpress-mcp": {
  "command": "npx",
  "args": [ "-y", "@automattic/mcp-wordpress-remote@latest" ],
  "env": {
    "WP_API_URL": "https://books.ay7aaga.com/",
    "WP_API_USERNAME": "mo-sa-@APP",
    "WP_API_PASSWORD": "(Application Password — credential / mcp.json only)",
    "OAUTH_ENABLED": "false"
  }
}
```

Do not paste the Application Password into this file, chat, plugins, or git.

## WordPress MCP (required)

Operate WP content through **`user-wordpress-mcp`**.

### Every session

1. Discover tools: `GetDynamicTools` namespace `user-wordpress-mcp` (or pattern `wordpress|wp_|wpmcp`).
2. Read the schema of the tool you will call before calling it.
3. Prefer MCP over REST-in-the-browser, wp-admin clicking, or invented local files.
4. If `user-wordpress-mcp` is missing, unauthorized, or has no content tools: call `mcp_auth` once. If it still fails, **stop and tell the user to reload the `wordpress-mcp` server in Cursor**. Do not improvise a parallel workflow.

### Safer writes

- Read the current item first. Patch only what you need.
- After product/content updates, check the Polylang translation when the change is customer-facing.
- Do not store Application Passwords or admin passwords in the repo.

---

## Server access — SSH, WP-CLI

Use these when WordPress MCP is not enough. Read `credential` for URLs, usernames, and the SSH host alias. Do not paste secrets here or in chat.

### When to use which

| Need | Use |
| --- | --- |
| Pages, posts, media, users, comments, MCP-covered settings | WordPress MCP |
| Custom mobile API routes, WooCommerce/WoodMart wrappers | Plugin `bookapp-mobile-api` via SSH / WP-CLI |
| Hosting files, one-off server commands | **SSH** (`books_ay7aaga`) |
| Plugin/theme/core inspect, search-replace, cache flush, bulk WP ops | **WP-CLI** over SSH |
| Hostinger account / DNS / websites list | Hostinger MCP **only if the user asked** |

Prefer the least-destructive layer. Do not SSH-edit WoodMart parent files or dump PHP into the child theme when the API plugin can hold it.

### SSH

- Host alias (local `~/.ssh/config`): `ssh books_ay7aaga` — user `u281506410`, host `186.242.129.217`, port `65002`, key `~/.ssh/my_hostinger`
- The alias `RemoteCommand` starts in `~/domains/books.ay7aaga.com/public_html` (books WP root)
- One-liners **must** disable `RemoteCommand` / TTY, or the remote `cd` eats the command:

```bash
ssh -o RequestTTY=no -o RemoteCommand=none books_ay7aaga 'cd ~/domains/books.ay7aaga.com/public_html && wp plugin list'
```

### WP-CLI

Already installed on the server: `/usr/local/bin/wp` — version 2.12.0. **Do not reinstall** unless `wp --version` fails.

Project config: `/home/u281506410/domains/books.ay7aaga.com/public_html/wp-cli.yml` if present (`path` + `url`). From the books docroot you do not need `--path`.

```bash
ssh books_ay7aaga
# already in books public_html via RemoteCommand
wp --version
wp core version
wp plugin list
wp cache flush
wp plugin activate bookapp-mobile-api
```

Do not drop a web-accessible `wp-cli.phar` in the docroot. Do not run destructive WP-CLI (`plugin delete`, `db reset`, `search-replace` on production URLs) unless the user asked.

---

## Cache — purge after every change

Plugin **WP-Optimize** (`wp-optimize`) is active. Hostinger also fronts the site (`hcdn`). Cached HTML (and sometimes REST) will hide new work until you purge.

**After every successful change**, flush before you verify:

```bash
ssh -o RequestTTY=no -o RemoteCommand=none books_ay7aaga 'cd ~/domains/books.ay7aaga.com/public_html && wp cache flush'
```

If WP-Optimize page cache still looks stale, purge it from the plugin / Hostinger cache tools. Then hit the API or front URL again. Do not deactivate WP-Optimize unless the user asked.

---

## WoodMart — do not touch the parent theme

WoodMart + `woodmart-core` store catalogue UI, wishlist, and compare in theme/plugin code and user meta. That is vendor code.

**Never:**

- Edit files under `wp-content/themes/woodmart/`
- Edit `woodmart-core` or `woodmart-images-optimizer`
- Use Appearance → Theme File Editor on the parent
- Copy WoodMart templates into the child theme unless the user explicitly orders a display change

**Allowed:**

- Read WoodMart / WooCommerce data via WP-CLI, REST, or MCP to design the mobile API
- Wrap wishlist / compare in `bookapp-mobile-api`
- WooCommerce settings and products via MCP / WP-CLI / REST — not by patching theme PHP

Active theme is **`woodmart-child` 1.0.0** (parent WoodMart). Keep the child empty of API logic.

---

## Mobile API — where code goes

This project exists to power a mobile app on top of a WooCommerce book store.

| Concern | Place |
| --- | --- |
| Products, categories, orders, customers, coupons | WooCommerce REST `wc/v3` first |
| Cart, checkout, coupons-on-cart | WooCommerce Store API `wc/store/v1` first |
| Login / tokens | Existing `jwt-auth/v1` (WordPress MCP JWT). Confirm contract before adding another auth plugin |
| Wishlist, compare | Custom routes in `bookapp-mobile-api` wrapping WoodMart |
| App-only fields, aggregation, versioning (`/wp-json/bookapp/v1/...`) | `bookapp-mobile-api` |
| WoodMart layout / CSS / header | Out of scope unless the user asks |

**Plugin conventions** (when you create it)

- Slug / folder: `bookapp-mobile-api`
- Namespace: `bookapp/v1`
- One plugin, versioned routes, capability / JWT checks on every write
- No secrets in the plugin. No admin passwords in responses
- Activate with `wp plugin activate bookapp-mobile-api`

Do **not** add a second custom plugin, mu-plugin, or child-theme API file for work this plugin can hold.

---

## Polylang — both languages when content is customer-facing

| Language | Code | Default | Direction |
| --- | --- | --- | --- |
| Arabic | `ar` | yes | RTL |
| English | `en` | no | LTR |

Do not assume these stay the only languages. At the start of a content/API-i18n task, list languages via MCP, Polylang, or WP-CLI.

Product titles, categories, and CMS pages have translations. API responses for the app must respect the requested language (`lang`, Accept-Language, or Polylang REST). Do not return English-only strings on Arabic clients or the reverse when a translation exists.

---

## Where each change goes

| Change | Place | Not here |
| --- | --- | --- |
| Mobile API routes / wishlist / compare / app auth glue | `bookapp-mobile-api` **and** `bookapp-api-docs.html` + `bookapp-api.http` | WoodMart, child theme, WPCode, wordpress-mcp-trunk |
| API docs / runnable examples after any API change | `bookapp-api-docs.html` + `bookapp-api.http` (both, same turn) | Chat-only notes, leaving one file stale |
| Products / orders / customers (standard) | WooCommerce REST or MCP | Custom SQL, theme PHP |
| Cart / checkout | Store API, then custom wrapper only if Store API is not enough | Invented session hacks in the theme |
| WP content (pages, media, users MCP covers) | WordPress MCP | Local files |
| Cache / plugins / search-replace | WP-CLI over SSH | Theme file edits |
| Server files, cron, PHP | SSH (Hostinger MCP only if asked) | Invented local files, ProTech cPanel MCP |
| WoodMart visual / CSS | User must ask; then child theme or WoodMart UI | Parent theme files |

---

## Site facts (as of last setup)

- WooCommerce 11.x, WoodMart child active, `woodmart-core` active
- Polylang `ar` + `en`
- Hidden login: WPS Hide Login → `/beoneentrence/`
- WordPress MCP plugin: `wordpress-mcp-trunk` (active) after `composer install` in that folder
- Hostinger Tools must **not** disable Application Passwords (`hostinger_tools.disable_authentication_password` = false) or MCP/REST app-password auth fails
- REST seen on the site: `wp/v2`, `wc/v3`, `wc/store/v1`, `jwt-auth/v1`, `pll/v1`, `yoast/v1`, `elementor/v1`, …
- Other plugins (do not remove): Elementor, Yoast, WP-Optimize, Hostinger, Limit Login Attempts, checkout/extra product options, etc.
- There is an existing `ek-plugin.php_` — treat as third-party. Do not edit it for the mobile API

---

## How to start a task

1. Discover **WordPress MCP** (`user-wordpress-mcp`) and confirm auth. Call `mcp_auth` if the namespace only shows that tool.
2. If the task needs files, SSH, or WP-CLI: read `credential`, then use `ssh books_ay7aaga` (one-liners: `RemoteCommand=none`).
3. Decide the layer: WooCommerce REST / Store API vs `bookapp-mobile-api` vs MCP content vs WP-CLI.
4. Implement in that layer only. No WoodMart parent edits.
5. If the change is customer-facing, consider `ar` and `en`.
6. Purge cache (`wp cache flush` + WP-Optimize / Hostinger if needed).
7. Verify with a real HTTP call to the API or the affected admin/front URL — not a single screenshot.
8. If the change affects the API (route, method, params, headers, auth, body, response, errors, or app usage): update **`bookapp-api-docs.html` and `bookapp-api.http`** in the same turn. Do not leave them for later.

---

## API documentation — update both files after every API change

Local files at the project root. They are the contract the mobile app (and the next agent) will follow.

| File | Role |
| --- | --- |
| `bookapp-api-docs.html` | Human-readable catalog: every endpoint, method, auth, headers, query/body, response shape, errors |
| `bookapp-api.http` | Runnable REST Client collection: same endpoints, ready to Send Request |

**After every successful API change**, update **both** files before you call the task done. A plugin/route change that is only on the server is unfinished.

Update them when you:

- Add, rename, remove, or version a route
- Change method, query params, headers, or body
- Change auth (JWT, guest header, cart token, nonce, Application Password)
- Change response fields, status codes, or error codes
- Change bootstrap / home / pagination / language behavior the app relies on

Keep the two files aligned with each other and with the live API. Add or edit the matching `###` request in `bookapp-api.http` when you add or change a section in `bookapp-api-docs.html`. Do not put admin passwords or the Cursor Application Password in either file. The REST Client test user already in `bookapp-api.http` is fine to keep there.

---

## Verification

- Purge cache **before** front/API checks when the response can be cached.
- For API work: hit the route (success, 401 without token, validation errors). A screenshot of wp-admin is not done.
- After an API change, confirm `bookapp-api-docs.html` and `bookapp-api.http` describe the new behavior. Then send the matching request from `bookapp-api.http` (or curl) against the live site.
- Check that a new plugin did not 500 the site (`wp plugin list`, error log if needed).
- Cart / wishlist / compare must stay consistent with the same customer/session the app will use.

---

## Credentials

All live-site credentials live **only** in `credential` at the project root: WordPress admin, SSH alias, WP-CLI, Application Password.

- Read `credential` when you need wp-admin, SSH, or WP-CLI.
- Do not ask the user to re-send passwords if `credential` is present.
- That file is local-only. Do not commit it. Do not paste its contents into `AGENTS.md`, MCP config, plugins, or chat.
- WordPress MCP is wired in Cursor `mcp.json` (`wordpress-mcp`). Use that server; do not recreate it.
- WordPress MCP uses the **Application Password** named `Cursor MCP` for user `mo-sa-@APP` (in `credential` / `mcp.json`) — not the raw admin password, unless the user says otherwise.
- Never hardcode API keys in the custom plugin; use WP options or ask the user for an env/admin setting.

