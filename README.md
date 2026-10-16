# Conferbot OpenCart Extension

Embeds the [Conferbot](https://conferbot.com) AI chat widget on an OpenCart 3.x store and passes the logged-in customer identity plus store/cart context to the bot.

## See it in action

Upload the extension, install it, paste the Chatbot ID, flip the switch, and the bot is live on the storefront (recorded on OpenCart 3.0.5.1, PHP 8.2). Click the GIF for the full-size MP4:

[![Conferbot on OpenCart: upload, install, paste the bot ID, save, open the store and chat](docs/media/install.gif)](https://github.com/Conferbot/conferbot-opencart/releases/download/v1.0.0/install.mp4)

## How it works

The extension registers two OpenCart events. `catalog/view/common/header/after` injects the standard Conferbot loader before `</head>` on every storefront page; `catalog/controller/checkout/success/before` keeps the order just placed so the success page can report it. The widget script loads without blocking the page, and `ConferbotWidget` is called once it has loaded:

```html
<script>
  (function (d, s, id) {
    var js = d.createElement(s); js.async = true; js.id = id;
    js.src = "https://cdn.conferbot.com/dist/v1/widget.min.js";
    d.head.appendChild(js);
    js.onload = function () {
      window.ConferbotWidget("<CHATBOT_ID>", "live_chat", {
        user:       { id: "oc-7", name, email, hash },   // logged-in customer, if any
        customData: { platform: "opencart", storeName, storeUrl,
                      storeLanguage, storeCurrency, cartTotal, cartItemCount }
      });
      // On the checkout success page, once per order:
      window.ConferbotWidget.track("purchase", { value, currency, order_id });
    };
  })(document, "script", "conferbot-js");
</script>
```

The only credential needed is the **Chatbot ID**. Everything visitors see is set per bot in the Conferbot dashboard and delivered at runtime, with nothing to configure in OpenCart: flows and AI agents, live chat with handover to your team (queue position, leave a message when nobody is free, read receipts, file sharing, post-chat rating), the home screen, themes and dark mode, colours, launcher and mascot, greetings, languages and disabled URLs. The extension does not duplicate those settings.

### Identity verification (optional)

With a secret from Conferbot (workspace settings > Security > Identity verification) pasted under Advanced settings, each logged-in customer is sent with `hash = HMAC-SHA256(secret, user.id)`, computed on your server. Conferbot then trusts their name and email and can link their chats to their contact. The secret is stored in your OpenCart settings and never sent to the browser. Without it, the customer's details are still passed but marked unverified, and if your workspace *requires* verification they are dropped.

### Sales tracking

The checkout success page reports the customer's own order (`purchase`, with value, currency and order number) once per order, and every AJAX add-to-cart as `add_to_cart`. Conferbot credits a sale to a chat that happened within the week before it. A store that upgraded in place gets the second event the next time the settings page is opened.

`options.user` and `options.customData` become the visitor's identity/context in Conferbot chats, so the bot and human agents see the customer's name, email, cart total, and store details.

## Requirements

- OpenCart 3.0.x (verified on 3.0.3.9 and 3.0.5.1). OpenCart 4 is not supported yet ([#6](https://github.com/Conferbot/conferbot-opencart/issues/6)).
- PHP as your OpenCart needs it (3.0.5.x needs PHP 8.1+); the extension itself runs on PHP 7.2+.
- The PHP `zip` extension, which OpenCart's Extension Installer uses to unpack uploads.
- A Conferbot account with a published bot ([app.conferbot.com](https://app.conferbot.com)), or the built-in demo bot to try it first.
- Visitors' browsers load `https://cdn.conferbot.com/dist/v1/widget.min.js` and connect to `wdt.conferbot.com`. If the store sends a Content Security Policy, allow both.

## Installation

### 1. Get the package

Download `conferbot.ocmod.zip` from the [latest release](https://github.com/Conferbot/conferbot-opencart/releases/latest).

To build it from a checkout instead (the zip must have the `upload/` folder at its top level, and the name must end in `.ocmod.zip`):

```bash
git archive --format=zip -o conferbot.ocmod.zip HEAD upload
```

### 2. Upload it

1. In the OpenCart admin go to **Extensions > Installer**.
2. Click **Upload** and choose `conferbot.ocmod.zip`. The progress bar finishes with *Success: You have modified extensions!*

No FTP settings are needed: the package only adds new files and makes no OCMOD changes, so there is nothing to refresh under Extensions > Modifications.

Without the installer: copy the contents of `upload/` into your OpenCart root, so that `admin/`, `catalog/` and `system/` merge with the existing folders.

### 3. Install the module

1. Go to **Extensions > Extensions** and choose **Modules** in the type list.
2. Find **Conferbot - AI Chatbot, Live Chat & Customer Support** and click the green **Install** (+) button. This registers the two storefront events.
3. Click the blue **Edit** (pencil) button to open the configure screen.

If your admin user group cannot see or save the module, give it access and modify permission for `extension/module/conferbot` under **System > Users > User Groups**.

### 4. Find your Chatbot ID

The Chatbot ID is a 24-character code such as `64a1b2c3d4e5f6a7b8c9d0e1`.

1. Sign in at [app.conferbot.com](https://app.conferbot.com) and open your bot.
2. Click **Share**, then **Embed into your website**.
3. The ID is the first value in the code: `window.ConferbotWidget("64a1b2c3d4e5f6a7b8c9d0e1", "live_chat")`. It is also in the browser's address bar: `.../chatbot/64a1b2c3d4e5f6a7b8c9d0e1/embed`.

You can paste the whole embed code or the dashboard URL instead; the extension keeps only the ID.

### 5. Turn it on

1. **Step 1:** paste the Chatbot ID.
2. **Step 2:** turn on **Show the chat bubble on my store**.
3. Click **Save - start chatting with my customers**. The green banner says *Saved! The chat is now live*.

## Configuration

**No account yet?** Click "Try our demo bot" on the configure screen. It fills in the public demo bot (`691c970890527a0468f9b2c9`), which works out of the box so you can see the widget live before signing up.

| Setting | Description |
|---|---|
| Chatbot ID | Required. Anything pasted is reduced to the 24-character ID; nothing is saved if no ID is found. |
| Turn the chat on | Master on/off switch. Off means no Conferbot code on the storefront. It cannot be turned on without an ID. |
| How the chat appears | **Floating chat bubble** (`live_chat`, default) or **Popup window** (`popup_chat`). *(Advanced)* |
| Identity verification secret | Optional. From Conferbot workspace settings > Security > Identity verification. Signs logged-in customers so Conferbot can trust them. Write-only: shown as saved, never displayed; leave blank to keep it, or tick remove. *(Advanced)* |
| Widget script URL | Leave as is for the Conferbot CDN. Only for staging or self-hosted widget builds; must be an `http(s)` URL. *(Advanced)* |

How the chat looks and behaves (colours, launcher, greeting, flows, AI agent, live chat, languages, pages to hide it on) is set per bot in the Conferbot dashboard and delivered at runtime.

## Architecture

The controllers only wire OpenCart (settings form, event handler); the logic lives in a small, individually tested library class:

```
conferbot-opencart/
├── upload/
│   ├── admin/
│   │   ├── controller/extension/module/conferbot.php   # Settings screen + install()/uninstall() event wiring
│   │   ├── language/en-gb/extension/module/conferbot.php
│   │   ├── view/template/extension/module/conferbot.twig # Two-step configure UI
│   │   └── view/image/conferbot/logo.png
│   ├── catalog/
│   │   └── controller/extension/module/conferbot.php   # injectWidget() event handler (header/after)
│   └── system/
│       └── library/conferbot/loader.php                # Bot ID parsing/validation, widget options, loader snippet
├── tests/                                              # PHPUnit suite (not shipped in the zip)
├── phpunit.xml
├── LICENSE
└── README.md
```

Storefront injection uses OpenCart's event system (`setting/event` model, code `conferbot`, trigger `catalog/view/common/header/after`, action `extension/module/conferbot/injectWidget`) - no OCMOD file modifications, so it never conflicts with theme or vQmod/OCMOD edits to `header.php`.

Settings are stored under the `module_conferbot` code: `module_conferbot_status`, `module_conferbot_bot_id`, `module_conferbot_embed_type`, `module_conferbot_script_url`.

## Tests

20 PHPUnit tests (73 assertions) cover bot ID extraction (bare ID, full snippet, dashboard URL, garbage, empty), strict validation, widget option building (guest/customer/cart variants, type casting), the identity hash and write-only secret, the purchase event, script-injection safety of the inlined JSON, and loader snippet rendering. The suite runs without an OpenCart installation - `ConferbotLoader` is a pure PHP class.

```bash
# any PHP 7.2+ with phpunit 9:
phpunit

# or containerized:
docker run --rm -v "$PWD":/mod -v /path/to/phpunit-9.phar:/phpunit.phar:ro \
  -w /mod php:8.2-cli php /phpunit.phar
```

## Check that it works

1. Open the storefront in a private window. The chat bubble appears bottom right (unless the bot's dashboard settings delay it or hide it on that URL).
2. Click it, start the chat and send a message. It shows up in the Conferbot dashboard under the bot's conversations.
3. Log in as a customer and chat again: the conversation shows the customer's name and email and the cart total (marked verified when the identity secret is set).
4. To check the code is on the page: view the page source and search for `conferbot-js`. It appears once, just before `</head>`.

## Troubleshooting

| Symptom | Cause and fix |
|---|---|
| The upload fails | The file name must end in `.ocmod.zip` and contain `upload/` at the top. Check that PHP's `zip` extension is installed and `system/storage/upload` is writable. |
| No `conferbot-js` in the page source | The module is not installed (green + in Extensions > Extensions > Modules), the switch is off, or the `conferbot` event is disabled under **Extensions > Events**. Then refresh the theme cache: **Dashboard > blue gear (Developer Settings) > Theme > Refresh**, and any full-page cache extension or CDN. |
| `conferbot-js` is there but no bubble | A Content Security Policy without `cdn.conferbot.com` / `wdt.conferbot.com`, a script blocker, or the bot is unpublished or hides itself on that URL. Check the browser console. |
| A theme without a normal `<head>` | The loader is then added at the end of the header output instead; it still works. |
| Logged-in customers are unverified | Paste the identity verification secret and make sure it matches the one in your Conferbot workspace. |

Still stuck? Write to support@conferbot.com.

## Uninstall

- **Pause it:** turn off the switch on the configure screen and save. The settings are kept.
- **Uninstall:** **Extensions > Extensions > Modules**, click the red **Uninstall** (-) button next to Conferbot. This removes both events and all `module_conferbot` settings, including the identity secret.
- **Remove the files** (optional): on **Extensions > Installer**, click the red delete button next to `conferbot.ocmod.zip` under Install History; OpenCart deletes the files it installed. By hand, they are `admin/controller/extension/module/conferbot.php`, `admin/language/*/extension/module/conferbot.php`, `admin/view/template/extension/module/conferbot.twig`, `admin/view/image/conferbot/`, `catalog/controller/extension/module/conferbot.php` and `system/library/conferbot/`.

Your bot and its conversations stay in your Conferbot account.

## Notes & limitations

- No API key or workspace ID is required - the widget resolves everything server-side from the Chatbot ID over Socket.IO (`wdt.conferbot.com`).
- Outbound webhooks (order status, product updates, abandoned cart) are **not** part of this version; the Conferbot backend does not yet expose OpenCart webhook endpoints.

## License

GNU General Public License v3.0 or later (GPL-3.0-or-later) - see [LICENSE](LICENSE). This follows the OpenCart marketplace convention (OpenCart itself is GPLv3).
