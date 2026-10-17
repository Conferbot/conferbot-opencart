# Changelog

## 1.1.0 - 2026-10-17

- Identity verification: with the workspace secret under Advanced settings, the logged-in customer is sent with `hash = HMAC-SHA256(secret, user.id)`, computed on the server. The secret is write-only and never sent to the browser.
- Sales tracking: the order is kept before checkout success and reported to Conferbot once per order.
- The order event is registered on install, repaired on upgrade and removed on uninstall.
- Store and customer names are sent as text, not HTML-escaped.
- Field help points to Share > Embed into your website in the Conferbot dashboard.
- Verified on OpenCart 3.0.5.1 (PHP 8.2).

## 1.0.0 - 2026-07-15

- Initial release: module that injects the Conferbot widget through storefront events, with the logged-in customer and store/cart context, an admin settings panel and the public demo bot.
