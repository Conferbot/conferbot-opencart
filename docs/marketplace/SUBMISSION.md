# OpenCart Marketplace: submission kit

Everything needed to list **Conferbot - AI Chatbot, Live Chat & Customer Support** 1.1.0 (OpenCart 3.x, `conferbot.ocmod.zip`) on the OpenCart Marketplace. Checked against opencart.com on 2026-10-17. The seller dashboard is behind a login, so its form fields could not be checked.

## 1. Where to submit

| What | Where |
| --- | --- |
| Account | A free **opencart.com account** (https://www.opencart.com/index.php?route=account/register), then **seller status**: Account > Extensions > *Apply to become a seller*. Seller rules and payouts are at https://www.opencart.com/index.php?route=cms/information&information_id=2. Approval "normally takes up to 48 hours". |
| Submission | Logged in as a seller: **Account > Extensions > Add extension**, at https://www.opencart.com/index.php?route=account/extension |
| Terms | https://www.opencart.com/index.php?route=cms/terms |
| Fees | None for a **free** extension. Commission (50% down to 25%) applies only to paid sales. The seller application asks for payout details (PayPal or Payoneer) anyway. |

## 2. Current requirements and where we stand

| Requirement | Status |
| --- | --- |
| Zip with an `upload/` folder (OCMOD-style); OpenCart 3.0+ for the Cloud Marketplace | OK. `conferbot.ocmod.zip` contains `upload/admin`, `upload/catalog` and `upload/system`, and installs with Extensions > Installer. No `install.xml` is needed because nothing is modified. |
| Your own work, tested, with support kept up for as long as it is listed | OK. Verified on OpenCart 3.0.5.1 / PHP 8.2 (install recording). |
| Licence | GPL v3 (`LICENSE`). OK for the Marketplace. |
| OpenCart versions | **3.0.x only.** OpenCart 4.x (current major, 4.1.0.4) is not supported yet: **#6**. List the extension for the 3.0.x versions only. Most new stores run 4.x, so #6 limits reach but does not block the listing. |

Review time is not published. Expect a few days.

## 3. Blockers

1. None for a 3.0.x listing.
2. #6 (OpenCart 4 support) should come next. Add it to the same listing as a second download when it ships.
3. Seller account and payout details: owner only.

## 4. Listing copy (ready to paste)

- **Name:** `Conferbot - AI Chatbot, Live Chat & Customer Support`
- **Short description:** `AI chatbot and live chat for your OpenCart store: answer shoppers 24/7, capture leads and hand over to your team. One ID, no code.`
- **Description:**

  > Conferbot adds an AI-powered chat assistant to every page of your OpenCart store. It answers product and order questions instantly, day and night, collects leads, and hands the conversation over to someone on your team when a shopper needs a person.
  >
  > Setup takes a minute. Upload the extension, install it under Extensions > Modules, paste your Chatbot ID from the Conferbot dashboard, turn the chat on and save. The widget's look and behaviour (flows or an AI agent trained on your content, live chat, colours, launcher, greetings, languages) are set in the Conferbot dashboard and apply straight away. Nothing else needs configuring in OpenCart.
  >
  > Logged-in customers are recognised: their name, email and cart value reach the bot and your agents as context, and an order placed after a chat is credited to that chat. An optional identity verification secret lets Conferbot trust who the customer is. The secret is used on your server and never sent to the browser.
  >
  > The extension is free. You need a Conferbot account to run your own bot, and the free plan is enough to start. Click "Try our demo bot" in the settings to see the widget before signing up.

- **Key benefits:**
  - Answers repetitive shopper questions 24/7, with flows or an AI agent trained on your content
  - Live chat handover to your team (queue position, file sharing, read receipts, rating); offline visitors leave a message
  - Lead capture and qualification
  - Sees the logged-in customer's name, email and cart total; sales are credited to the chats that led to them
  - Optional server-side identity verification
  - Two-step setup, no theme edits; the widget loads asynchronously from a CDN
- **Category:** *Modules > Customer Support* (or the closest "Live chat / Support" category the form offers)
- **Tags / keywords:** `chatbot, live chat, ai chatbot, customer support, chat widget, helpdesk, lead generation, whatsapp alternative`
- **Price:** Free. **Pricing text:** "Free extension. Requires a Conferbot account; a free plan is available (no card needed)."
- **OpenCart versions:** 3.0.0.0 to 3.0.5.x (tick each 3.0.x version the form lists)
- **Support:** `support@conferbot.com` (the address used in the extension and on conferbot.com/contact). Documentation: https://github.com/Conferbot/conferbot-opencart#readme
- **Demo:** none hosted. Use the reviewer steps below. Video: https://github.com/Conferbot/conferbot-opencart/releases/download/v1.1.0/install.mp4 (upload it to YouTube if the form wants a link).
- **Privacy policy:** https://www.conferbot.com/privacy-policy (HTTP 200). **Terms:** https://www.conferbot.com/terms-of-service (HTTP 200)

### Data and GDPR statement

This statement matches `upload/system/library/conferbot/loader.php` and `upload/catalog/controller/extension/module/conferbot.php`.

- The extension stores only its settings in OpenCart's `setting` table under `module_conferbot`: the bot ID, the on/off switch, the embed type, the script URL and the optional secret. It also registers two events. Uninstalling removes both.
- On every storefront page, once the chat is on with a valid ID, the shopper's browser loads `https://cdn.conferbot.com/dist/v1/widget.min.js`. The page passes the widget:
  - the Chatbot ID
  - `platform: "opencart"`, plus the store name, URL, language and currency
  - for **logged-in customers only**: the customer ID (`oc-<id>`), name and email. If a secret is set, it also passes an HMAC-SHA256 of the ID; the secret is never sent.
  - the cart total and item count
  - on the checkout success page, once per order: the order total, currency and order ID
- Whatever the shopper types or uploads in the chat goes to Conferbot as the conversation.
- The extension sets no cookies of its own.

## 5. Reviewer test instructions

> 1. Extensions > Installer > Upload `conferbot.ocmod.zip` (https://github.com/Conferbot/conferbot-opencart/releases/latest).
> 2. Extensions > Extensions > choose **Modules** > "Conferbot - AI Chatbot, Live Chat & Customer Support" > Install (green +), then Edit (pencil).
> 3. Click "Try our demo bot", or paste the public demo Chatbot ID `691c970890527a0468f9b2c9` (a live "Startup Consultation Chatbot"; no account needed). Turn the chat on and click Save.
> 4. Open the storefront. A chat bubble appears bottom right. Click it and reply to the bot.
> 5. Optional: log in as a customer and add a product. The bot receives the name, email and cart total. Uninstalling the module removes the widget and its settings.

## 6. Assets (in `docs/marketplace/assets/`)

The Marketplace does not publish image sizes. These files are large enough to be scaled down:

| File | Size | Content |
| --- | --- | --- |
| `icon-512x512.png` | 512x512 | Conferbot logo (from the landing site) on white, for the extension image |
| `screenshot-1.png` | 1280x720 | Settings page: Chatbot ID pasted, chat switched on, saved (real frame, OpenCart 3.0.5.1) |
| `screenshot-2.png` | 1280x720 | The chat open on the default storefront |
| `screenshot-3.png` | 1280x720 | A conversation with the bot |

## 7. Owner checklist

- [ ] Create the opencart.com account and apply to become a seller (country, phone, PayPal/Payoneer for payouts).
- [ ] Add extension: paste the copy above, upload `conferbot.ocmod.zip` from the v1.1.0 release, the icon and the screenshots, tick the 3.0.x versions, set the price to Free.
- [ ] Accept the Marketplace terms (support for as long as it is listed).
- [ ] When #6 ships, add the 4.x download to the same listing.

Owner time: about 40 minutes, plus waiting up to 48 hours for seller approval. Review: a few days (not published). Fees: none for a free extension.
