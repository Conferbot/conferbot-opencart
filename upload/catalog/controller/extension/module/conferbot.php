<?php
/**
 * Conferbot - storefront side.
 *
 * injectWidget() is wired to the 'catalog/view/common/header/after' event
 * (registered by the admin controller's install()), so it runs once per page
 * right after the common header view renders. It appends the Conferbot widget
 * loader before </head> with the logged-in customer identity plus store/cart
 * context.
 *
 * @author    Conferbot <support@conferbot.com>
 * @copyright 2023-2026 Conferbot
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0 or later
 */

require_once(DIR_SYSTEM . 'library/conferbot/loader.php');

class ControllerExtensionModuleConferbot extends Controller
{
    /**
     * Event handler for catalog/view/common/header/after.
     *
     * @param string $route rendered view route (common/header)
     * @param array $data view data
     * @param string $output rendered HTML (by reference - we append to it)
     */
    public function injectWidget(&$route, &$data, &$output)
    {
        if (!$this->config->get('module_conferbot_status')) {
            return;
        }

        $botId = (string) $this->config->get('module_conferbot_bot_id');

        if (!ConferbotLoader::isValidBotId($botId)) {
            return;
        }

        // Belt and braces: never inject twice even if the event fires again.
        if (strpos($output, ConferbotLoader::SCRIPT_ELEMENT_ID) !== false) {
            return;
        }

        $optionsJson = ConferbotLoader::toJson(ConferbotLoader::buildOptions(
            $this->buildSite(),
            $this->buildCustomer(),
            $this->buildCart(),
            (string) $this->config->get('module_conferbot_identity_secret')
        ));

        $snippet = ConferbotLoader::render(array(
            'bot_id' => $botId,
            'embed_type' => (string) $this->config->get('module_conferbot_embed_type'),
            'script_url' => (string) $this->config->get('module_conferbot_script_url'),
        ), $optionsJson, ConferbotLoader::toJson(ConferbotLoader::events($this->confirmedOrder())));

        // The common/header view renders <head>...</head> plus the opening of
        // <body>; inject just before </head>. If a heavily customized theme
        // moved </head> elsewhere, append at the end of the header output.
        $pos = stripos($output, '</head>');

        if ($pos !== false) {
            $output = substr($output, 0, $pos) . $snippet . "\n" . substr($output, $pos);
        } else {
            $output .= $snippet . "\n";
        }
    }

    /**
     * Event handler for catalog/controller/checkout/success/before: the
     * success page clears the order from the session before the header
     * renders, so keep its id for injectWidget() to report the purchase.
     */
    public function rememberOrder(&$route, &$args)
    {
        if (isset($this->session->data['order_id'])) {
            $this->session->data['conferbot_order_id'] = (int) $this->session->data['order_id'];
        }
    }

    /**
     * The order just placed, on the checkout success page only (once: the
     * kept id is cleared), and only the visitor's own.
     */
    private function confirmedOrder()
    {
        $route = isset($this->request->get['route']) ? (string) $this->request->get['route'] : '';
        if ($route !== 'checkout/success' || empty($this->session->data['conferbot_order_id'])) {
            return null;
        }
        $orderId = (int) $this->session->data['conferbot_order_id'];
        unset($this->session->data['conferbot_order_id']);

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($orderId);
        if (!$order) {
            return null;
        }
        if ((int) $order['customer_id'] !== 0 && (int) $order['customer_id'] !== (int) $this->customer->getId()) {
            return null;
        }

        return array(
            'id' => (int) $order['order_id'],
            'total' => (float) $order['total'] * (float) $order['currency_value'],
            'currency' => (string) $order['currency_code'],
        );
    }

    private function buildSite()
    {
        if (isset($this->session->data['language'])) {
            $language = (string) $this->session->data['language'];
        } else {
            $language = (string) $this->config->get('config_language');
        }

        if (isset($this->session->data['currency'])) {
            $currency = (string) $this->session->data['currency'];
        } else {
            $currency = (string) $this->config->get('config_currency');
        }

        $url = (string) $this->config->get('config_url');
        if ($url === '' && defined('HTTP_SERVER')) {
            $url = HTTP_SERVER;
        }

        return array(
            'name' => self::plain($this->config->get('config_name')),
            'url' => $url,
            'language' => $language,
            'currency' => $currency,
        );
    }

    private function buildCustomer()
    {
        if (!$this->customer->isLogged()) {
            return null;
        }

        return array(
            'id' => $this->customer->getId(),
            'firstname' => self::plain($this->customer->getFirstName()),
            'lastname' => self::plain($this->customer->getLastName()),
            'email' => self::plain($this->customer->getEmail()),
        );
    }

    /**
     * OpenCart stores form input HTML-escaped ("Tom &amp; Jerry"); the widget
     * wants the text itself. The JSON_HEX flags keep it safe in the page.
     */
    private static function plain($value)
    {
        return html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function buildCart()
    {
        if (!isset($this->cart)) {
            return null;
        }

        try {
            return array(
                'total' => $this->cart->getTotal(),
                'itemCount' => $this->cart->countProducts(),
            );
        } catch (Exception $e) {
            // Cart totals can fail in edge states; context data is optional.
            return null;
        }
    }
}
