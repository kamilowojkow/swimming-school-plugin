<?php
/**
 * Environment Settings Page
 *
 * Strona administracyjna do zarządzania konfiguracją środowiska.
 *
 * @package Swimming_School_Manager
 * @since 2.66
 */

if (!defined('ABSPATH')) exit;

// Pobierz instancję konfiguracji
$config = ssm_config();

// Zapisywanie ustawień
if (isset($_POST['ssm_save_environment']) && wp_verify_nonce($_POST['ssm_env_nonce'], 'ssm_save_environment')) {

    $values = array(
        // Environment
        'environment' => sanitize_text_field($_POST['environment']),
        'debug_mode' => isset($_POST['debug_mode']) ? true : false,
        'debug_log_api' => isset($_POST['debug_log_api']) ? true : false,

        // API Settings
        'ifirma_api_url' => esc_url_raw($_POST['ifirma_api_url']),
        'api_timeout' => intval($_POST['api_timeout']),

        // Security
        'token_expiry_days' => intval($_POST['token_expiry_days']),
        'max_login_attempts' => intval($_POST['max_login_attempts']),
        'login_lockout_minutes' => intval($_POST['login_lockout_minutes']),

        // Features
        'enable_gamification' => isset($_POST['enable_gamification']) ? true : false,
        'enable_referrals' => isset($_POST['enable_referrals']) ? true : false,
        'enable_notifications' => isset($_POST['enable_notifications']) ? true : false,
        'enable_ratings' => isset($_POST['enable_ratings']) ? true : false,
        'enable_mobile_api' => isset($_POST['enable_mobile_api']) ? true : false,

        // Uploads
        'max_upload_size_mb' => intval($_POST['max_upload_size_mb']),
        'allowed_document_types' => sanitize_text_field($_POST['allowed_document_types']),
        'allowed_image_types' => sanitize_text_field($_POST['allowed_image_types']),

        // Cache
        'cache_ttl_seconds' => intval($_POST['cache_ttl_seconds']),
        'enable_query_cache' => isset($_POST['enable_query_cache']) ? true : false,
    );

    $config->set_multiple($values);

    echo '<div class="notice notice-success is-dismissible"><p>Ustawienia zapisane pomyslnie.</p></div>';

    // Reload config
    $config = SSM_Config::get_instance();
}

// Reset to defaults
if (isset($_POST['ssm_reset_environment']) && wp_verify_nonce($_POST['ssm_env_nonce'], 'ssm_save_environment')) {
    $config->reset_to_defaults();
    echo '<div class="notice notice-warning is-dismissible"><p>Ustawienia zostaly zresetowane do wartosci domyslnych.</p></div>';
}

$all_config = $config->get_all();
?>

<div class="wrap">
    <h1>Konfiguracja srodowiska</h1>
    <p class="description">Ustawienia techniczne wtyczki. Zmien przed przeniesieniem na produkcje.</p>

    <form method="post" action="">
        <?php wp_nonce_field('ssm_save_environment', 'ssm_env_nonce'); ?>

        <!-- Environment -->
        <div class="ssm-env-section">
            <h2>Srodowisko</h2>

            <table class="form-table">
                <tr>
                    <th><label for="environment">Tryb srodowiska</label></th>
                    <td>
                        <select name="environment" id="environment">
                            <option value="development" <?php selected($all_config['environment'], 'development'); ?>>
                                Development (testowe)
                            </option>
                            <option value="staging" <?php selected($all_config['environment'], 'staging'); ?>>
                                Staging (przed-produkcyjne)
                            </option>
                            <option value="production" <?php selected($all_config['environment'], 'production'); ?>>
                                Production (produkcja)
                            </option>
                        </select>
                        <p class="description">Wybierz odpowiedni tryb dla obecnego srodowiska.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="debug_mode">Tryb debugowania</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="debug_mode" id="debug_mode" value="1"
                                   <?php checked($all_config['debug_mode']); ?>>
                            Wlacz logowanie bledow
                        </label>
                        <p class="description">Wlacza dodatkowe logowanie do error_log. Wylacz na produkcji!</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="debug_log_api">Loguj API</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="debug_log_api" id="debug_log_api" value="1"
                                   <?php checked($all_config['debug_log_api']); ?>>
                            Loguj requesty i response API
                        </label>
                        <p class="description">Loguje komunikacje z zewnetrznymi API (iFirma). Moze zawierac wrazliwe dane!</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- API Settings -->
        <div class="ssm-env-section">
            <h2>Ustawienia API</h2>

            <table class="form-table">
                <tr>
                    <th><label for="ifirma_api_url">iFirma.pl API URL</label></th>
                    <td>
                        <input type="url" name="ifirma_api_url" id="ifirma_api_url" class="regular-text"
                               value="<?php echo esc_attr($all_config['ifirma_api_url']); ?>">
                        <p class="description">Bazowy URL API iFirma.pl. Domyslnie: https://www.ifirma.pl/iapi</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="api_timeout">Timeout API (sekundy)</label></th>
                    <td>
                        <input type="number" name="api_timeout" id="api_timeout" class="small-text"
                               value="<?php echo esc_attr($all_config['api_timeout']); ?>" min="5" max="60">
                        <p class="description">Maksymalny czas oczekiwania na odpowiedz z zewnetrznych API.</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Security Settings -->
        <div class="ssm-env-section">
            <h2>Bezpieczenstwo</h2>

            <table class="form-table">
                <tr>
                    <th><label for="token_expiry_days">Waznosc tokena (dni)</label></th>
                    <td>
                        <input type="number" name="token_expiry_days" id="token_expiry_days" class="small-text"
                               value="<?php echo esc_attr($all_config['token_expiry_days']); ?>" min="1" max="365">
                        <p class="description">Ile dni token API pozostaje wazny.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="max_login_attempts">Max prob logowania</label></th>
                    <td>
                        <input type="number" name="max_login_attempts" id="max_login_attempts" class="small-text"
                               value="<?php echo esc_attr($all_config['max_login_attempts']); ?>" min="3" max="20">
                        <p class="description">Liczba nieudanych prob logowania przed blokada.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="login_lockout_minutes">Czas blokady (minuty)</label></th>
                    <td>
                        <input type="number" name="login_lockout_minutes" id="login_lockout_minutes" class="small-text"
                               value="<?php echo esc_attr($all_config['login_lockout_minutes']); ?>" min="5" max="1440">
                        <p class="description">Jak dlugo konto jest zablokowane po przekroczeniu limitu prob.</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Feature Flags -->
        <div class="ssm-env-section">
            <h2>Funkcje</h2>
            <p class="description">Wlacz lub wylacz poszczegolne moduly systemu.</p>

            <table class="form-table">
                <tr>
                    <th>Aktywne moduly</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="enable_mobile_api" value="1"
                                       <?php checked($all_config['enable_mobile_api']); ?>>
                                Mobile API (REST API dla aplikacji mobilnej)
                            </label><br>
                            <label>
                                <input type="checkbox" name="enable_gamification" value="1"
                                       <?php checked($all_config['enable_gamification']); ?>>
                                System nagrod i odznak
                            </label><br>
                            <label>
                                <input type="checkbox" name="enable_referrals" value="1"
                                       <?php checked($all_config['enable_referrals']); ?>>
                                Program polecen
                            </label><br>
                            <label>
                                <input type="checkbox" name="enable_notifications" value="1"
                                       <?php checked($all_config['enable_notifications']); ?>>
                                Powiadomienia push
                            </label><br>
                            <label>
                                <input type="checkbox" name="enable_ratings" value="1"
                                       <?php checked($all_config['enable_ratings']); ?>>
                                Oceny zajec
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Upload Settings -->
        <div class="ssm-env-section">
            <h2>Uploady</h2>

            <table class="form-table">
                <tr>
                    <th><label for="max_upload_size_mb">Max rozmiar pliku (MB)</label></th>
                    <td>
                        <input type="number" name="max_upload_size_mb" id="max_upload_size_mb" class="small-text"
                               value="<?php echo esc_attr($all_config['max_upload_size_mb']); ?>" min="1" max="50">
                        <p class="description">Maksymalny rozmiar uploadowanych plikow w MB.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="allowed_document_types">Dozwolone typy dokumentow</label></th>
                    <td>
                        <input type="text" name="allowed_document_types" id="allowed_document_types" class="regular-text"
                               value="<?php echo esc_attr($all_config['allowed_document_types']); ?>">
                        <p class="description">Rozszerzenia plikow oddzielone przecinkami (np. pdf,doc,docx)</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="allowed_image_types">Dozwolone typy obrazow</label></th>
                    <td>
                        <input type="text" name="allowed_image_types" id="allowed_image_types" class="regular-text"
                               value="<?php echo esc_attr($all_config['allowed_image_types']); ?>">
                        <p class="description">Rozszerzenia obrazow oddzielone przecinkami (np. jpg,png,gif)</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Cache Settings -->
        <div class="ssm-env-section">
            <h2>Cache</h2>

            <table class="form-table">
                <tr>
                    <th><label for="cache_ttl_seconds">TTL cache (sekundy)</label></th>
                    <td>
                        <input type="number" name="cache_ttl_seconds" id="cache_ttl_seconds" class="small-text"
                               value="<?php echo esc_attr($all_config['cache_ttl_seconds']); ?>" min="60" max="86400">
                        <p class="description">Jak dlugo dane sa cachowane (domyslnie 3600 = 1 godzina).</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="enable_query_cache">Cache zapytan</label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_query_cache" id="enable_query_cache" value="1"
                                   <?php checked($all_config['enable_query_cache']); ?>>
                            Wlacz cachowanie zapytan do bazy danych
                        </label>
                        <p class="description">Zmniejsza obciazenie bazy danych, ale moze pokazywac nieaktualne dane.</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- System Info -->
        <div class="ssm-env-section">
            <h2>Informacje o systemie</h2>

            <table class="form-table">
                <tr>
                    <th>Wersja wtyczki</th>
                    <td><code><?php echo defined('SSM_VERSION') ? SSM_VERSION : 'N/A'; ?></code></td>
                </tr>
                <tr>
                    <th>Wersja PHP</th>
                    <td><code><?php echo PHP_VERSION; ?></code></td>
                </tr>
                <tr>
                    <th>Wersja WordPress</th>
                    <td><code><?php echo get_bloginfo('version'); ?></code></td>
                </tr>
                <tr>
                    <th>URL strony</th>
                    <td><code><?php echo get_site_url(); ?></code></td>
                </tr>
                <tr>
                    <th>Sciezka wtyczki</th>
                    <td><code><?php echo defined('SSM_PLUGIN_DIR') ? SSM_PLUGIN_DIR : 'N/A'; ?></code></td>
                </tr>
                <tr>
                    <th>WP_DEBUG</th>
                    <td>
                        <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                            <span style="color: #d63638;">Wlaczony</span>
                        <?php else: ?>
                            <span style="color: #00a32a;">Wylaczony</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <button type="submit" name="ssm_save_environment" class="button button-primary button-large">
                Zapisz ustawienia
            </button>
            <button type="submit" name="ssm_reset_environment" class="button button-secondary"
                    onclick="return confirm('Czy na pewno chcesz zresetowac ustawienia do wartosci domyslnych?');">
                Resetuj do domyslnych
            </button>
        </p>
    </form>
</div>

<style>
.ssm-env-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.ssm-env-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #c3c4c7;
    font-size: 1.3em;
}

.ssm-env-section:first-of-type {
    border-left: 4px solid #2271b1;
}

.form-table th {
    width: 250px;
}
</style>
