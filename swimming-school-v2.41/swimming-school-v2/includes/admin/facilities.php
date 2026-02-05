<?php
// Panel Obiekty (Baseny)
if (!defined('ABSPATH')) exit;

global $wpdb;

$facilities = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}ssm_facilities 
    ORDER BY name
");
?>

<div class="wrap">
    <h1>Obiekty (Baseny)
        <button type="button" class="page-title-action" id="ssm-add-facility-btn">Dodaj obiekt</button>
    </h1>
    
    <!-- Formularz -->
    <div id="ssm-facility-form-container" style="display:none; margin:20px 0;">
        <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; max-width:600px;">
            <h2 id="ssm-form-title">Dodaj obiekt</h2>
            <form id="ssm-facility-form">
                <input type="hidden" id="facility-id" name="id" value="">
                
                <table class="form-table">
                    <tr>
                        <th><label for="facility-name">Nazwa *</label></th>
                        <td><input type="text" id="facility-name" name="name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="facility-address">Adres</label></th>
                        <td><input type="text" id="facility-address" name="address" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="facility-city">Miasto</label></th>
                        <td><input type="text" id="facility-city" name="city" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="facility-url">Link do opisu</label></th>
                        <td>
                            <input type="url" id="facility-url" name="url" class="regular-text">
                            <p class="description">Klikalna nazwa obiektu w tabelach (otwiera w nowej karcie)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="facility-description">Opis</label></th>
                        <td><textarea id="facility-description" name="description" class="large-text" rows="4"></textarea></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">Zapisz obiekt</button>
                    <button type="button" class="button" id="ssm-cancel-facility-btn">Anuluj</button>
                </p>
            </form>
        </div>
    </div>
    
    <!-- Lista -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:50px;">ID</th>
                <th>Nazwa</th>
                <th>Lokalizacja</th>
                <th>Opis</th>
                <th>Link</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($facilities): ?>
                <?php foreach ($facilities as $facility): ?>
                <tr>
                    <td><?php echo $facility->id; ?></td>
                    <td><strong><?php echo esc_html($facility->name); ?></strong></td>
                    <td>
                        <?php if ($facility->address): ?>
                            <?php echo esc_html($facility->address); ?><br>
                        <?php endif; ?>
                        <?php if ($facility->city): ?>
                            <?php echo esc_html($facility->city); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html(mb_substr($facility->description, 0, 100)); ?><?php echo strlen($facility->description) > 100 ? '...' : ''; ?></td>
                    <td>
                        <?php if ($facility->url): ?>
                            <a href="<?php echo esc_url($facility->url); ?>" target="_blank">🔗 Zobacz</a>
                        <?php else: ?>
                            <span style="color:#646970;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button button-small ssm-edit-facility" 
                                data-id="<?php echo $facility->id; ?>"
                                data-name="<?php echo esc_attr($facility->name); ?>"
                                data-address="<?php echo esc_attr($facility->address); ?>"
                                data-city="<?php echo esc_attr($facility->city); ?>"
                                data-url="<?php echo esc_attr($facility->url); ?>"
                                data-description="<?php echo esc_attr($facility->description); ?>">
                            Edytuj
                        </button>
                        
                        <button class="button button-small button-link-delete ssm-delete-facility" 
                                data-id="<?php echo $facility->id; ?>">
                            Usuń
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Brak obiektów. Dodaj pierwszy basen!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
