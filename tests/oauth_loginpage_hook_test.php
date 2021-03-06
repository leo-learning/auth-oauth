<?php

use \Mockery as m;

require_once __DIR__ . '/../auth.php';

defined('MOODLE_INTERNAL') || die();

class oauth_loginpage_hook_test extends advanced_testcase {

    /**
     * setUp
     * @global moodle_database $DB
     */
    protected function setUp() {
        global $DB;
        $this->resetAfterTest();
        $DB = m::mock('moodle_database');
        $DB->shouldIgnoreMissing();
    }

    /**
     * tearDown
     */
    protected function tearDown() {
        m::close();
    }

    /**
     * default plugin configuration
     * @return array
     */
    protected function get_config() {
        $config = [
            'oauth_provider' => json_encode(new stdClass()),
            'oauth_redirect' => true,
        ];
        return $config;
    }

    /**
     * test that request_authorization_code is called
     */
    public function test_loginpage_hook() {
        global $SESSION;
        $request = m::mock('oauth_request');
        $request->shouldReceive('request_authorization_code')->once()
            ->with(
                m::type('stdClass'),
                m::type('moodle_functions'),
                m::type('string')
            );
        $auth = new auth_plugin_oauth();
        $auth->config = (object)$this->get_config();
        $auth->loginpage_hook($request);
        $this->assertTrue(isset($SESSION->oauth_state));
    }

    /**
     * test that request_authorization_code is not called when using the "backdoor"
     */
    public function test_loginpage_hook_backdoor() {
        global $SESSION;

        $request = m::mock('oauth_request');
        $request->shouldReceive('request_authorization_code')->never();

        $auth = new auth_plugin_oauth();
        $auth->set_backdoor();
        $auth->config = (object)$this->get_config();
        $auth->loginpage_hook($request);

        $this->assertFalse(isset($SESSION->oauth_state));
    }

    /**
     * test that request_authorization_code is not called when the oauth_redirect option is false
     */
    public function test_loginpage_hook_no_redirect() {
        global $SESSION;

        $request = m::mock('oauth_request');
        $request->shouldReceive('request_authorization_code')->never();

        $config = $this->get_config();
        $config['oauth_redirect'] = false;

        $auth = new auth_plugin_oauth();
        $auth->config = (object)$config;
        $auth->loginpage_hook($request);

        $this->assertFalse(isset($SESSION->oauth_state));
    }

    /**
     * test that request_provider_logout is called
     */
    public function test_logoutpage_hook() {
        global $USER;

        $USER->auth = 'oauth';
        $request = m::mock('oauth_request');
        $request->shouldReceive('request_provider_logout')->once()
            ->with(
                m::type('stdClass'),
                m::type('moodle_functions')
            );

        $auth = new auth_plugin_oauth();
        $auth->config = (object) $this->get_config();
        $auth->logoutpage_hook($request);
    }

    /**
     * test that request_provider_logout is not called when the oauth_redirect option is false
     */
    public function test_logoutpage_hook_no_redirect() {
        $request = m::mock('oauth_request');
        $request->shouldReceive('request_provider_logout')->never();

        $config = $this->get_config();
        $config['oauth_redirect'] = false;

        $auth = new auth_plugin_oauth();
        $auth->config = (object)$config;
        $auth->logoutpage_hook($request);
    }

    /**
     * test that request_provider_logout is not called when the logged-in user has manual auth
     */
    public function test_logoutpage_hook_no_redirect_manual_auth() {
        global $USER;

        $USER->auth = 'manual';
        $request = m::mock('oauth_request');
        $request->shouldReceive('request_provider_logout')->never();
        $config = $this->get_config();

        $auth = new auth_plugin_oauth();
        $auth->config = (object)$config;
        $auth->logoutpage_hook($request);
    }

}
