<?php
/**
 * Conferbot widget loader - pure PHP helper, no OpenCart dependencies.
 *
 * Bot ID parsing/validation, widget option building for
 * window.ConferbotWidget(botId, embedType, options), and rendering of the
 * loader <script> snippet. Kept free of framework code so it is fully
 * unit-testable outside an OpenCart installation.
 *
 * A Conferbot bot ID is the chatbot's MongoDB ObjectId: 24 hex characters.
 * Merchants paste anything from a bare ID to the full embed snippet, so
 * extraction is deliberately forgiving while validation stays strict.
 *
 * @author    Conferbot <support@conferbot.com>
 * @copyright 2023-2026 Conferbot
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0 or later
 */

class ConferbotLoader
{
    /** Public demo bot - works out of the box so merchants can try the widget instantly. */
    const DEMO_BOT_ID = '691c970890527a0468f9b2c9';

    /** Conferbot CDN build of the widget. */
    const DEFAULT_SCRIPT_URL = 'https://cdn.conferbot.com/dist/v1/widget.min.js';

    /** DOM id of the injected script element. */
    const SCRIPT_ELEMENT_ID = 'conferbot-js';

    /** Supported widget embed types. */
    public static $embedTypes = array('live_chat', 'popup_chat');

    /**
     * Pulls the first 24-hex token out of whatever was pasted
     * (bare ID, full embed snippet, dashboard URL, extra whitespace).
     *
     * @param string $text
     *
     * @return string extracted ID, or '' when none found
     */
    public static function extractBotId($text)
    {
        if (preg_match('/[a-f0-9]{24}/i', (string) $text, $m)) {
            return $m[0];
        }

        return '';
    }

    /**
     * Strict validation of a stored bot ID.
     *
     * @param string $id
     *
     * @return bool
     */
    public static function isValidBotId($id)
    {
        // D modifier: $ must match at the very end (no trailing newline pass).
        return (bool) preg_match('/^[a-f0-9]{24}$/iD', (string) $id);
    }

    /**
     * Builds the third argument for window.ConferbotWidget(botId, embedType, options).
     *
     * options.user carries the logged-in customer identity; options.customData
     * carries store and cart context. Both become the visitor's context in
     * Conferbot conversations.
     *
     * @param array $site keys: name, url, language, currency
     * @param array|null $customer keys: id, firstname, lastname, email (null = guest)
     * @param array|null $cart keys: total, itemCount (null = no cart context)
     *
     * @return array widget options
     */
    public static function buildOptions(array $site, $customer = null, $cart = null)
    {
        $options = array('customData' => array(
            'platform' => 'opencart',
            'storeName' => isset($site['name']) ? (string) $site['name'] : '',
            'storeUrl' => isset($site['url']) ? (string) $site['url'] : '',
            'storeLanguage' => isset($site['language']) ? (string) $site['language'] : '',
            'storeCurrency' => isset($site['currency']) ? (string) $site['currency'] : '',
        ));

        if (is_array($customer) && !empty($customer['id'])) {
            $name = trim(
                (isset($customer['firstname']) ? $customer['firstname'] : '')
                . ' '
                . (isset($customer['lastname']) ? $customer['lastname'] : '')
            );
            $options['user'] = array(
                'id' => 'oc-' . (int) $customer['id'],
                'name' => $name,
                'email' => isset($customer['email']) ? (string) $customer['email'] : '',
            );
        }

        if (is_array($cart)) {
            if (isset($cart['total'])) {
                $options['customData']['cartTotal'] = (float) $cart['total'];
            }
            if (isset($cart['itemCount'])) {
                $options['customData']['cartItemCount'] = (int) $cart['itemCount'];
            }
        }

        return $options;
    }

    /**
     * JSON safe to inline inside a <script> block: JSON_HEX_* neutralize
     * user-controlled values (customer name/email) in the HTML context.
     *
     * @param array $options
     *
     * @return string
     */
    public static function toJson(array $options)
    {
        return json_encode($options, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    /**
     * Renders the loader snippet - the canonical script from the Conferbot
     * dashboard, with customer/store context passed as the third
     * ConferbotWidget argument.
     *
     * @param array $settings keys: bot_id, embed_type, script_url
     * @param string $optionsJson output of self::toJson()
     *
     * @return string HTML <script> block
     */
    public static function render(array $settings, $optionsJson)
    {
        $botId = isset($settings['bot_id']) ? (string) $settings['bot_id'] : '';

        $embedType = isset($settings['embed_type']) ? (string) $settings['embed_type'] : '';
        if (!in_array($embedType, self::$embedTypes, true)) {
            $embedType = 'live_chat';
        }

        $scriptUrl = isset($settings['script_url']) ? trim((string) $settings['script_url']) : '';
        if ($scriptUrl === '') {
            $scriptUrl = self::DEFAULT_SCRIPT_URL;
        }

        $jsFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;

        return "<script type=\"text/javascript\">\n"
            . "(function (d, s, id) {\n"
            . "  var js, el = d.getElementsByTagName(s)[0];\n"
            . "  if (d.getElementById(id)) return;\n"
            . "  js = d.createElement(s);\n"
            . "  js.async = true;\n"
            . '  js.src = ' . json_encode($scriptUrl, $jsFlags) . ";\n"
            . "  js.id = id;\n"
            . "  js.charset = 'UTF-8';\n"
            . "  if (el && el.parentNode) { el.parentNode.insertBefore(js, el); } else { (d.head || d.body).appendChild(js); }\n"
            . "  js.onload = function () {\n"
            . '    var options = ' . $optionsJson . ";\n"
            . '    window.ConferbotWidget('
            . json_encode($botId, $jsFlags) . ', '
            . json_encode($embedType, $jsFlags) . ", options);\n"
            . "  };\n"
            . "})(document, 'script', " . json_encode(self::SCRIPT_ELEMENT_ID, $jsFlags) . ");\n"
            . '</script>';
    }
}
