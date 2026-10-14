<?php
/**
 * Unit tests for ConferbotLoader (bot ID parsing/validation, widget option
 * building, JSON script-safety, loader snippet rendering).
 */

use PHPUnit\Framework\TestCase;

class ConferbotLoaderTest extends TestCase
{
    // -----------------------------------------------------------------
    // extractBotId()
    // -----------------------------------------------------------------

    public function testExtractsBareId()
    {
        $this->assertSame(
            '64a1b2c3d4e5f6a7b8c9d0e1',
            ConferbotLoader::extractBotId('64a1b2c3d4e5f6a7b8c9d0e1')
        );
    }

    public function testExtractsIdSurroundedByWhitespace()
    {
        $this->assertSame(
            '64a1b2c3d4e5f6a7b8c9d0e1',
            ConferbotLoader::extractBotId("  64a1b2c3d4e5f6a7b8c9d0e1\n")
        );
    }

    public function testExtractsIdFromFullEmbedSnippet()
    {
        $snippet = '<script>(function (d, s, id) { var js; js.src = '
            . '"https://cdn.conferbot.com/dist/v1/widget.min.js"; js.onload = function () {'
            . 'window.ConferbotWidget("64a1b2c3d4e5f6a7b8c9d0e1", "live_chat");'
            . '};})(document, "script", "conferbot-js");</script>';

        $this->assertSame('64a1b2c3d4e5f6a7b8c9d0e1', ConferbotLoader::extractBotId($snippet));
    }

    public function testExtractsIdFromDashboardUrl()
    {
        $this->assertSame(
            '64a1b2c3d4e5f6a7b8c9d0e1',
            ConferbotLoader::extractBotId('https://app.conferbot.com/bots/64a1b2c3d4e5f6a7b8c9d0e1/share')
        );
    }

    public function testExtractReturnsEmptyStringForGarbage()
    {
        $this->assertSame('', ConferbotLoader::extractBotId('hello world, no id here 123'));
    }

    public function testExtractReturnsEmptyStringForEmptyInput()
    {
        $this->assertSame('', ConferbotLoader::extractBotId(''));
        $this->assertSame('', ConferbotLoader::extractBotId('   '));
    }

    public function testExtractIsCaseInsensitive()
    {
        $this->assertSame(
            '64A1B2C3D4E5F6A7B8C9D0E1',
            ConferbotLoader::extractBotId('ID: 64A1B2C3D4E5F6A7B8C9D0E1')
        );
    }

    // -----------------------------------------------------------------
    // isValidBotId()
    // -----------------------------------------------------------------

    public function testValidatesCorrectId()
    {
        $this->assertTrue(ConferbotLoader::isValidBotId('64a1b2c3d4e5f6a7b8c9d0e1'));
        $this->assertTrue(ConferbotLoader::isValidBotId('64A1B2C3D4E5F6A7B8C9D0E1'));
    }

    public function testValidationIsStrict()
    {
        $this->assertFalse(ConferbotLoader::isValidBotId(''), 'empty');
        $this->assertFalse(ConferbotLoader::isValidBotId('64a1b2c3d4e5f6a7b8c9d0e'), '23 chars');
        $this->assertFalse(ConferbotLoader::isValidBotId('64a1b2c3d4e5f6a7b8c9d0e12'), '25 chars');
        $this->assertFalse(ConferbotLoader::isValidBotId('g4a1b2c3d4e5f6a7b8c9d0e1'), 'non-hex char');
        $this->assertFalse(ConferbotLoader::isValidBotId(' 64a1b2c3d4e5f6a7b8c9d0e1'), 'leading space');
        $this->assertFalse(ConferbotLoader::isValidBotId("64a1b2c3d4e5f6a7b8c9d0e1\n"), 'trailing newline');
    }

    public function testDemoBotIdIsValid()
    {
        $this->assertSame('691c970890527a0468f9b2c9', ConferbotLoader::DEMO_BOT_ID);
        $this->assertTrue(ConferbotLoader::isValidBotId(ConferbotLoader::DEMO_BOT_ID));
    }

    // -----------------------------------------------------------------
    // buildOptions()
    // -----------------------------------------------------------------

    private function site()
    {
        return array(
            'name' => 'My Store',
            'url' => 'https://shop.example.com/',
            'language' => 'en-gb',
            'currency' => 'USD',
        );
    }

    public function testGuestOptionsHaveNoUserKey()
    {
        $options = ConferbotLoader::buildOptions($this->site(), null, null);

        $this->assertArrayNotHasKey('user', $options);
        $this->assertSame('opencart', $options['customData']['platform']);
        $this->assertSame('My Store', $options['customData']['storeName']);
        $this->assertSame('https://shop.example.com/', $options['customData']['storeUrl']);
        $this->assertSame('en-gb', $options['customData']['storeLanguage']);
        $this->assertSame('USD', $options['customData']['storeCurrency']);
        $this->assertArrayNotHasKey('cartTotal', $options['customData']);
        $this->assertArrayNotHasKey('cartItemCount', $options['customData']);
    }

    public function testLoggedInCustomerBecomesUser()
    {
        $options = ConferbotLoader::buildOptions($this->site(), array(
            'id' => 42,
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'email' => 'jane@example.com',
        ));

        $this->assertSame('oc-42', $options['user']['id']);
        $this->assertSame('Jane Doe', $options['user']['name']);
        $this->assertSame('jane@example.com', $options['user']['email']);
    }

    public function testCustomerWithoutIdIsTreatedAsGuest()
    {
        $options = ConferbotLoader::buildOptions($this->site(), array(
            'firstname' => 'Jane',
            'email' => 'jane@example.com',
        ));

        $this->assertArrayNotHasKey('user', $options);
    }

    public function testCartTotalsAreCast()
    {
        $options = ConferbotLoader::buildOptions($this->site(), null, array(
            'total' => '19.99',
            'itemCount' => '3',
        ));

        $this->assertSame(19.99, $options['customData']['cartTotal']);
        $this->assertSame(3, $options['customData']['cartItemCount']);
    }

    public function testMissingFieldsFallBackToEmptyStrings()
    {
        $options = ConferbotLoader::buildOptions(array(), array(
            'id' => 7,
        ), array());

        $this->assertSame('', $options['customData']['storeName']);
        $this->assertSame('', $options['customData']['storeUrl']);
        $this->assertSame('', $options['customData']['storeLanguage']);
        $this->assertSame('', $options['customData']['storeCurrency']);
        $this->assertSame('oc-7', $options['user']['id']);
        $this->assertSame('', $options['user']['name']);
        $this->assertSame('', $options['user']['email']);
        $this->assertArrayNotHasKey('cartTotal', $options['customData']);
        $this->assertArrayNotHasKey('cartItemCount', $options['customData']);
    }

    // -----------------------------------------------------------------
    // toJson() script-safety
    // -----------------------------------------------------------------

    public function testJsonIsSafeToInlineInScriptBlock()
    {
        $options = ConferbotLoader::buildOptions($this->site(), array(
            'id' => 9,
            'firstname' => '</script><script>alert(1)',
            'lastname' => "O'Hara & \"Sons\"",
            'email' => 'evil@example.com',
        ));

        $json = ConferbotLoader::toJson($options);

        $this->assertStringNotContainsString('<', $json);
        $this->assertStringNotContainsString('>', $json);
        $this->assertStringNotContainsString("'", $json);
        $this->assertStringNotContainsString('&', $json);
        $this->assertStringContainsString('\\u003C', $json);
        $this->assertStringContainsString('\\u0027', $json);
        $this->assertStringContainsString('\\u0026', $json);

        $decoded = json_decode($json, true);
        $this->assertSame(
            "</script><script>alert(1) O'Hara & \"Sons\"",
            $decoded['user']['name']
        );
    }

    // -----------------------------------------------------------------
    // render()
    // -----------------------------------------------------------------

    public function testRenderIncludesBotIdEmbedTypeScriptUrlAndElementId()
    {
        $html = ConferbotLoader::render(array(
            'bot_id' => '64a1b2c3d4e5f6a7b8c9d0e1',
            'embed_type' => 'popup_chat',
            'script_url' => 'https://cdn.example.com/widget.js',
        ), '{"customData":{"platform":"opencart"}}');

        $this->assertStringContainsString('64a1b2c3d4e5f6a7b8c9d0e1', $html);
        $this->assertStringContainsString('popup_chat', $html);
        $this->assertStringContainsString('https:\/\/cdn.example.com\/widget.js', $html);
        $this->assertStringContainsString('conferbot-js', $html);
        $this->assertStringContainsString('window.ConferbotWidget', $html);
        $this->assertStringContainsString('{"customData":{"platform":"opencart"}}', $html);
        $this->assertStringContainsString('js.async = true;', $html);
        $this->assertStringStartsWith('<script', $html);
        $this->assertStringEndsWith('</script>', $html);
    }

    public function testRenderFallsBackToDefaults()
    {
        $html = ConferbotLoader::render(array(
            'bot_id' => ConferbotLoader::DEMO_BOT_ID,
            'embed_type' => 'not-a-real-type',
            'script_url' => '   ',
        ), '{}');

        $this->assertStringContainsString('live_chat', $html);
        $this->assertStringContainsString('cdn.conferbot.com\/dist\/v1\/widget.min.js', $html);
    }

    public function testLoggedInCustomerIsSignedWithTheWorkspaceSecret()
    {
        $site = array('name' => 'Shop', 'url' => 'https://s.example/', 'language' => 'en-gb', 'currency' => 'GBP');
        $customer = array('id' => 9, 'firstname' => 'Jane', 'lastname' => 'Doe', 'email' => 'jane@example.com');
        $options = ConferbotLoader::buildOptions($site, $customer, null, 's3cret');
        $this->assertSame(hash_hmac('sha256', 'oc-9', 's3cret'), $options['user']['hash']);
        $this->assertArrayNotHasKey('hash', ConferbotLoader::buildOptions($site, $customer)['user']);
        $this->assertStringNotContainsString('s3cret', ConferbotLoader::toJson($options));
        $this->assertSame('old', ConferbotLoader::nextIdentitySecret('old', ' ', false));
        $this->assertSame('new', ConferbotLoader::nextIdentitySecret('old', 'new', false));
        $this->assertSame('', ConferbotLoader::nextIdentitySecret('old', 'new', true));
    }

    public function testSuccessPageReportsThePurchaseOnce()
    {
        $events = ConferbotLoader::events(array('id' => 31, 'total' => 49.5, 'currency' => 'GBP'));
        $this->assertSame('purchase', $events[0]['name']);
        $this->assertSame(array('value' => 49.5, 'currency' => 'GBP', 'order_id' => '31'), $events[0]['props']);
        $this->assertSame('order-31', $events[0]['once']);
        $this->assertSame(array(), ConferbotLoader::events(null));

        $html = ConferbotLoader::render(array('bot_id' => '691c970890527a0468f9b2c9'), '{}', ConferbotLoader::toJson($events));
        $this->assertStringContainsString('var events = [{"name":"purchase"', $html);
        $this->assertStringContainsString("window.ConferbotWidget.track(e.name, e.props || {})", $html);
        $this->assertStringContainsString("route=checkout\\/cart\\/add", $html);
    }
}
