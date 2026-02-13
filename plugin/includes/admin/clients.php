<?php
// Panel Klienci (Rodzice/Opiekunowie)
if (!defined('ABSPATH')) exit;

global $wpdb;

$clients = $wpdb->get_results("
    SELECT c.*,
           COUNT(DISTINCT cc.child_id) as children_count
    FROM {$wpdb->prefix}ssm_clients c
    LEFT JOIN {$wpdb->prefix}ssm_client_children cc ON c.id = cc.client_id
    GROUP BY c.id
    ORDER BY c.last_name, c.first_name
");
?>

<div class="wrap">
    <h1>Rodzice / Opiekunowie
        <button type="button" class="page-title-action" id="ssm-add-client-btn">Dodaj rodzica</button>
    </h1>
    
    <!-- Formularz dodawania/edycji -->
    <div id="ssm-client-form-container" style="display:none; margin:20px 0;">
        <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; max-width:600px;">
            <h2 id="ssm-form-title">Dodaj rodzica</h2>
            <form id="ssm-client-form">
                <input type="hidden" id="client-id" name="id" value="">
                
                <table class="form-table">
                    <tr>
                        <th><label for="client-first-name">Imię *</label></th>
                        <td><input type="text" id="client-first-name" name="first_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="client-last-name">Nazwisko *</label></th>
                        <td><input type="text" id="client-last-name" name="last_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="client-email">Email *</label></th>
                        <td><input type="email" id="client-email" name="email" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="client-phone">Telefon</label></th>
                        <td><input type="tel" id="client-phone" name="phone" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="client-notes">Notatki</label></th>
                        <td><textarea id="client-notes" name="notes" class="large-text" rows="3"></textarea></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">Zapisz</button>
                    <button type="button" class="button" id="ssm-cancel-client-btn">Anuluj</button>
                </p>
            </form>
        </div>
    </div>
    
    <!-- Lista klientów -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:50px;">ID</th>
                <th>Imię i nazwisko</th>
                <th>Email</th>
                <th>Telefon</th>
                <th>Dzieci</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($clients): ?>
                <?php foreach ($clients as $client): ?>
                <tr>
                    <td><?php echo $client->id; ?></td>
                    <td><strong><?php echo esc_html($client->first_name . ' ' . $client->last_name); ?></strong></td>
                    <td><?php echo esc_html($client->email); ?></td>
                    <td><?php echo esc_html($client->phone); ?></td>
                    <td>
                        <?php if ($client->children_count > 0): ?>
                            <span style="color:#2271b1;">👶 <?php echo $client->children_count; ?></span>
                        <?php else: ?>
                            <span style="color:#646970;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button button-small ssm-edit-client" 
                                data-id="<?php echo $client->id; ?>"
                                data-first-name="<?php echo esc_attr($client->first_name); ?>"
                                data-last-name="<?php echo esc_attr($client->last_name); ?>"
                                data-email="<?php echo esc_attr($client->email); ?>"
                                data-phone="<?php echo esc_attr($client->phone); ?>"
                                data-notes="<?php echo esc_attr($client->notes); ?>">
                            Edytuj
                        </button>
                        
                        <button class="button button-small button-link-delete ssm-delete-client" 
                                data-id="<?php echo $client->id; ?>">
                            Usuń
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Brak rodziców w bazie. Dodaj pierwszego rodzica!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="ssm-info-box" style="margin-top:20px;">
        <p><strong>💡 Wskazówka:</strong> Po dodaniu rodzica, przejdź do zakładki "Dzieci" aby przypisać mu dzieci. Jeden rodzic może mieć wiele dzieci, a jedno dziecko może mieć wielu rodziców/opiekunów!</p>
    </div>
</div>

<style>
.ssm-info-box {
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
    padding: 15px;
}
</style>
