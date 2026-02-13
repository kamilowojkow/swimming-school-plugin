<?php
/**
 * SSM Configuration Class
 *
 * Centralizuje ustawienia środowiskowe wtyczki.
 * Ułatwia przenoszenie między środowiskami (dev/staging/production).
 *
 * @package Swimming_School_Manager
 * @since 2.66
 */

if (!defined('ABSPATH')) exit;

class SSM_Config {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Cache for options
     */
    private $options_cache = array();

    /**
     * Environment constants
     */
    const ENV_DEVELOPMENT = 'development';
    const ENV_STAGING = 'staging';
    const ENV_PRODUCTION = 'production';

    /**
     * Default configuration values
     */
    private static $defaults = array(
        // Environment
        'environment' => self::ENV_PRODUCTION,
        'debug_mode' => false,
        'debug_log_api' => false,

        // API URLs (zewnętrzne serwisy)
        'ifirma_api_url' => 'https://www.ifirma.pl/iapi',

        // Limits and timeouts
        'api_timeout' => 15,
        'token_expiry_days' => 30,
        'max_login_attempts' => 5,
        'login_lockout_minutes' => 15,

        // Features toggle
        'enable_gamification' => true,
        'enable_referrals' => true,
        'enable_notifications' => true,
        'enable_ratings' => true,
        'enable_mobile_api' => true,

        // Uploads
        'max_upload_size_mb' => 5,
        'allowed_document_types' => 'pdf,doc,docx,jpg,jpeg,png',
        'allowed_image_types' => 'jpg,jpeg,png,gif,webp',

        // Cache
        'cache_ttl_seconds' => 3600,
        'enable_query_cache' => true,
    );

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor
     */
    private function __construct() {
        $this->load_options();
    }

    /**
     * Load options from database
     */
    private function load_options() {
        $saved = get_option('ssm_config', array());
        $this->options_cache = wp_parse_args($saved, self::$defaults);
    }

    /**
     * Get configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function get($key, $default = null) {
        if (isset($this->options_cache[$key])) {
            return $this->options_cache[$key];
        }

        if (isset(self::$defaults[$key])) {
            return self::$defaults[$key];
        }

        return $default;
    }

    /**
     * Set configuration value
     *
     * @param string $key Configuration key
     * @param mixed $value Value to set
     * @return bool
     */
    public function set($key, $value) {
        $this->options_cache[$key] = $value;
        return $this->save();
    }

    /**
     * Set multiple configuration values
     *
     * @param array $values Key-value pairs
     * @return bool
     */
    public function set_multiple($values) {
        foreach ($values as $key => $value) {
            $this->options_cache[$key] = $value;
        }
        return $this->save();
    }

    /**
     * Save options to database
     *
     * @return bool
     */
    private function save() {
        return update_option('ssm_config', $this->options_cache);
    }

    /**
     * Get all configuration
     *
     * @return array
     */
    public function get_all() {
        return $this->options_cache;
    }

    /**
     * Get default values
     *
     * @return array
     */
    public static function get_defaults() {
        return self::$defaults;
    }

    /**
     * Reset to defaults
     *
     * @return bool
     */
    public function reset_to_defaults() {
        $this->options_cache = self::$defaults;
        return $this->save();
    }

    // ============ ENVIRONMENT HELPERS ============

    /**
     * Check if running in development mode
     *
     * @return bool
     */
    public function is_development() {
        return $this->get('environment') === self::ENV_DEVELOPMENT;
    }

    /**
     * Check if running in production mode
     *
     * @return bool
     */
    public function is_production() {
        return $this->get('environment') === self::ENV_PRODUCTION;
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function is_debug() {
        return (bool) $this->get('debug_mode');
    }

    /**
     * Check if API logging is enabled
     *
     * @return bool
     */
    public function should_log_api() {
        return $this->is_debug() && (bool) $this->get('debug_log_api');
    }

    // ============ API CONFIGURATION ============

    /**
     * Get iFirma API base URL
     *
     * @return string
     */
    public function get_ifirma_api_url() {
        return rtrim($this->get('ifirma_api_url'), '/');
    }

    /**
     * Get API timeout in seconds
     *
     * @return int
     */
    public function get_api_timeout() {
        return (int) $this->get('api_timeout');
    }

    // ============ FEATURE FLAGS ============

    /**
     * Check if feature is enabled
     *
     * @param string $feature Feature name (without 'enable_' prefix)
     * @return bool
     */
    public function is_feature_enabled($feature) {
        return (bool) $this->get('enable_' . $feature, false);
    }

    // ============ SECURITY SETTINGS ============

    /**
     * Get token expiry in days
     *
     * @return int
     */
    public function get_token_expiry_days() {
        return (int) $this->get('token_expiry_days');
    }

    /**
     * Get max login attempts before lockout
     *
     * @return int
     */
    public function get_max_login_attempts() {
        return (int) $this->get('max_login_attempts');
    }

    /**
     * Get login lockout duration in minutes
     *
     * @return int
     */
    public function get_login_lockout_minutes() {
        return (int) $this->get('login_lockout_minutes');
    }

    // ============ UPLOAD SETTINGS ============

    /**
     * Get max upload size in bytes
     *
     * @return int
     */
    public function get_max_upload_size() {
        return (int) $this->get('max_upload_size_mb') * 1024 * 1024;
    }

    /**
     * Get allowed document types as array
     *
     * @return array
     */
    public function get_allowed_document_types() {
        $types = $this->get('allowed_document_types');
        return array_map('trim', explode(',', $types));
    }

    /**
     * Get allowed image types as array
     *
     * @return array
     */
    public function get_allowed_image_types() {
        $types = $this->get('allowed_image_types');
        return array_map('trim', explode(',', $types));
    }

    // ============ STATIC HELPERS ============

    /**
     * Quick access to get a config value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed
     */
    public static function value($key, $default = null) {
        return self::get_instance()->get($key, $default);
    }

    /**
     * Log message if debug mode is enabled
     *
     * @param string $message Message to log
     * @param string $level Log level (info, warning, error)
     */
    public static function log($message, $level = 'info') {
        $config = self::get_instance();

        if (!$config->is_debug()) {
            return;
        }

        $prefix = '[SSM ' . strtoupper($level) . '] ';
        error_log($prefix . $message);
    }

    /**
     * Log API request/response if API logging is enabled
     *
     * @param string $endpoint API endpoint
     * @param mixed $data Data to log (will be JSON encoded if array)
     * @param string $type 'request' or 'response'
     */
    public static function log_api($endpoint, $data, $type = 'request') {
        $config = self::get_instance();

        if (!$config->should_log_api()) {
            return;
        }

        $message = sprintf(
            '[API %s] %s: %s',
            strtoupper($type),
            $endpoint,
            is_array($data) ? json_encode($data) : $data
        );

        error_log('[SSM] ' . $message);
    }
}

/**
 * Helper function to get config instance
 *
 * @return SSM_Config
 */
function ssm_config() {
    return SSM_Config::get_instance();
}
