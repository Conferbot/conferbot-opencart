<?php
/**
 * Conferbot - AI Chatbot, Live Chat & Customer Support for OpenCart 3.x.
 *
 * Admin side: the two-step settings screen (paste the Chatbot ID, flip the
 * switch) plus install()/uninstall() that register/remove the catalog view
 * event used to inject the widget loader on every storefront page.
 *
 * The only credential is the Chatbot ID; appearance and behavior are managed
 * in the Conferbot dashboard and delivered at runtime.
 *
 * @author    Conferbot <support@conferbot.com>
 * @copyright 2023-2026 Conferbot
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0 or later
 */

require_once(DIR_SYSTEM . 'library/conferbot/loader.php');

class ControllerExtensionModuleConferbot extends Controller
{
    const EVENT_CODE = 'conferbot';
    const EVENT_TRIGGER = 'catalog/view/common/header/after';
    const EVENT_ACTION = 'extension/module/conferbot/injectWidget';
    const ORDER_EVENT_CODE = 'conferbot_order';
    const ORDER_EVENT_TRIGGER = 'catalog/controller/checkout/success/before';
    const ORDER_EVENT_ACTION = 'extension/module/conferbot/rememberOrder';

    private $error = array();

    public function index()
    {
        $this->load->language('extension/module/conferbot');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        // A store that upgraded in place has no order event yet: add it here.
        $this->ensureOrderEvent();

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $saved = $this->save();

            if ($saved) {
                $this->response->redirect($this->url->link(
                    'extension/module/conferbot',
                    'user_token=' . $this->session->data['user_token'],
                    true
                ));
            }
        }

        $this->response->setOutput($this->load->view(
            'extension/module/conferbot',
            $this->buildTemplateData()
        ));
    }

    /**
     * Processes the settings form. Returns true when saved (caller redirects),
     * false when a validation message was queued instead.
     *
     * @return bool
     */
    private function save()
    {
        $pasted = isset($this->request->post['module_conferbot_bot_id'])
            ? (string) $this->request->post['module_conferbot_bot_id']
            : '';
        $botId = ConferbotLoader::extractBotId($pasted);
        $status = !empty($this->request->post['module_conferbot_status']) ? 1 : 0;

        if ($botId === '' && trim($pasted) !== '') {
            $this->error['warning'] = $this->language->get('error_bot_id');

            return false;
        }

        if ($botId === '' && $status) {
            $this->error['warning'] = $this->language->get('error_enable_without_id');

            return false;
        }

        $scriptUrl = isset($this->request->post['module_conferbot_script_url'])
            ? trim((string) $this->request->post['module_conferbot_script_url'])
            : '';
        if ($scriptUrl === '') {
            $scriptUrl = ConferbotLoader::DEFAULT_SCRIPT_URL;
        }
        if (!preg_match('~^https?://~i', $scriptUrl) || filter_var($scriptUrl, FILTER_VALIDATE_URL) === false) {
            $this->error['warning'] = $this->language->get('error_script_url');

            return false;
        }

        $embedType = isset($this->request->post['module_conferbot_embed_type'])
            ? (string) $this->request->post['module_conferbot_embed_type']
            : '';
        if (!in_array($embedType, ConferbotLoader::$embedTypes, true)) {
            $embedType = 'live_chat';
        }

        $this->model_setting_setting->editSetting('module_conferbot', array(
            'module_conferbot_status' => $status,
            'module_conferbot_bot_id' => $botId,
            'module_conferbot_embed_type' => $embedType,
            'module_conferbot_script_url' => $scriptUrl,
            'module_conferbot_identity_secret' => ConferbotLoader::nextIdentitySecret(
                (string) $this->config->get('module_conferbot_identity_secret'),
                isset($this->request->post['module_conferbot_identity_secret']) ? (string) $this->request->post['module_conferbot_identity_secret'] : '',
                !empty($this->request->post['module_conferbot_identity_secret_clear'])
            ),
        ));

        if ($status && $botId !== '') {
            $this->session->data['success'] = $this->language->get('text_success_live');
        } else {
            $this->session->data['success'] = $this->language->get('text_success_off');
        }

        return true;
    }

    private function buildTemplateData()
    {
        $data = array();

        // Standard OpenCart admin chrome.
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['breadcrumbs'] = array(
            array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            ),
            array(
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
            ),
            array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/conferbot', 'user_token=' . $this->session->data['user_token'], true),
            ),
        );

        $data['action'] = $this->url->link(
            'extension/module/conferbot',
            'user_token=' . $this->session->data['user_token'],
            true
        );

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        // Current values: re-display the submitted values on validation errors,
        // stored settings otherwise.
        $fields = array(
            'module_conferbot_status' => 0,
            'module_conferbot_bot_id' => '',
            'module_conferbot_embed_type' => 'live_chat',
            'module_conferbot_script_url' => ConferbotLoader::DEFAULT_SCRIPT_URL,
        );

        foreach ($fields as $key => $default) {
            if (isset($this->request->post[$key])) {
                $data[$key] = $this->request->post[$key];
            } else {
                $stored = $this->config->get($key);
                $data[$key] = ($stored === null || $stored === '') ? $default : $stored;
            }
        }

        $data['module_conferbot_status'] = (int) $data['module_conferbot_status'];
        // Write-only: the page only says whether one is saved.
        $data['identity_secret_saved'] = (string) $this->config->get('module_conferbot_identity_secret') !== '';

        $data['demo_bot_id'] = ConferbotLoader::DEMO_BOT_ID;
        $data['default_script_url'] = ConferbotLoader::DEFAULT_SCRIPT_URL;
        $data['logo'] = 'view/image/conferbot/logo.png';
        $data['store_url'] = defined('HTTP_CATALOG') ? HTTP_CATALOG : '';

        return $data;
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/conferbot')) {
            $this->error['warning'] = $this->language->get('error_permission');

            return false;
        }

        return true;
    }

    /**
     * Called by OpenCart when the module is installed from
     * Extensions > Modules. Registers the catalog view event that appends
     * the widget loader to the rendered storefront header.
     */
    public function install()
    {
        $this->load->model('setting/event');

        // Clean up any leftover registration before adding a fresh one.
        $this->deleteEvent();

        $this->model_setting_event->addEvent(self::EVENT_CODE, self::EVENT_TRIGGER, self::EVENT_ACTION);
        $this->model_setting_event->addEvent(self::ORDER_EVENT_CODE, self::ORDER_EVENT_TRIGGER, self::ORDER_EVENT_ACTION);
    }

    private function ensureOrderEvent()
    {
        $this->load->model('setting/event');
        if (self::byCode() && !$this->model_setting_event->getEventByCode(self::ORDER_EVENT_CODE)) {
            $this->model_setting_event->addEvent(self::ORDER_EVENT_CODE, self::ORDER_EVENT_TRIGGER, self::ORDER_EVENT_ACTION);
        }
    }

    /**
     * Called by OpenCart when the module is uninstalled. Removes the event
     * and the stored settings.
     */
    public function uninstall()
    {
        $this->load->model('setting/event');
        $this->load->model('setting/setting');

        $this->deleteEvent();

        $this->model_setting_setting->deleteSetting('module_conferbot');
    }

    private function deleteEvent()
    {
        // OpenCart 3.0.2+ uses deleteEventByCode(); 3.0.0/3.0.1 used deleteEvent().
        // (Models are proxies here, so method_exists() is always false: go by version.)
        foreach (array(self::EVENT_CODE, self::ORDER_EVENT_CODE) as $code) {
            if (self::byCode()) {
                $this->model_setting_event->deleteEventByCode($code);
            } else {
                $this->model_setting_event->deleteEvent($code);
            }
        }
    }

    /** OpenCart 3.0.2+ finds and deletes events by code. */
    private static function byCode()
    {
        return !defined('VERSION') || version_compare(VERSION, '3.0.2.0', '>=');
    }
}
