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

        $optionsJson = ConferbotLoader::toJson(
            ConferbotLoader::buildOptions($this->buildSite(), $this->buildCustomer(), $this->buildCart())
        );

        $snippet = ConferbotLoader::render(array(
            'bot_id' => $botId,
            'embed_type' => (string) $this->config->get('module_conferbot_embed_type'),
            'script_url' => (string) $this->config->get('module_conferbot_script_url'),
        ), $optionsJson);

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
            'name' => (string) $this->config->get('config_name'),
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
            'firstname' => $this->customer->getFirstName(),
            'lastname' => $this->customer->getLastName(),
            'email' => $this->customer->getEmail(),
        );
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
