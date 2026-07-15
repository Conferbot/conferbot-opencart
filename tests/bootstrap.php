<?php
/**
 * Test bootstrap: loads the loader library without an OpenCart runtime.
 * ConferbotLoader is a pure PHP class, so no framework stubs are needed.
 */

define('CONFERBOT_TEST_SUITE', true);

require_once __DIR__ . '/../upload/system/library/conferbot/loader.php';
