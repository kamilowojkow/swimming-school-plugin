<?php
// Panel Admin - Galeria
if (!defined('ABSPATH')) exit;

global $wpdb;

// Pobierz wszystkie zdjęcia
$gallery_items = $wpdb->get_results("
    SELECT g.*, c.name as class_name
    FROM {$wpdb->prefix}ssm_gallery g
    LEFT JOIN {$wpdb->prefix}ssm_classes c ON g.class_id = c.id
    ORDER BY g.created_at DESC
");

// Pobierz kursy dla selecta
$classes = $wpdb->get_results("
    SELECT id, name FROM {$wpdb->prefix}ssm_classes
    WHERE status = 'active'
    ORDER BY name
");
?>

<div class="wrap">
    <h1>📷 Galeria</h1>
    <p>Zarządzaj zdjęciami z zajęć widocznymi dla rodziców</p>
    
    <button type="button" class="button button-primary" id="ssm-add-gallery-btn">
        ➕ Dodaj zdjęcie
    </button>
    
    <!-- Formularz dodawania/edycji -->
    <div id="ssm-gallery-form-container" style="display:none; margin-top:20px;">
        <div style="background:#fff; padding:20px; border:1px solid #ccc; border-radius:4px;">
            <h2 id="ssm-form-title">Dodaj zdjęcie</h2>
            
            <form id="ssm-gallery-form" enctype="multipart/form-data">
                <input type="hidden" id="gallery-id" name="id">
                
                <table class="form-table">
                    <tr>
                        <th><label for="gallery-title">Tytuł *</label></th>
                        <td>
                            <input type="text" id="gallery-title" name="title" class="regular-text" required 
                                   placeholder="np. Zajęcia grupy młodszej">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="gallery-image">Zdjęcie *</label></th>
                        <td>
                            <input type="file" id="gallery-image" name="image" accept="image/*" class="regular-text">
                            <p class="description">Tylko dla nowych zdjęć. Zostaw puste przy edycji.</p>
                            <div id="current-image-preview" style="margin-top:10px;"></div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="gallery-class">Kurs (opcjonalnie)</label></th>
                        <td>
                            <select id="gallery-class" name="class_id" class="regular-text">
                                <option value="">-- Brak --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class->id; ?>">
                                        <?php echo esc_html($class->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="gallery-description">Opis</label></th>
                        <td>
                            <textarea id="gallery-description" name="description" class="large-text" rows="3"
                                      placeholder="Opcjonalny opis zdjęcia..."></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="gallery-status">Status</label></th>
                        <td>
                            <select id="gallery-status" name="status" class="regular-text">
                                <option value="published">Opublikowane (widoczne dla rodziców)</option>
                                <option value="draft">Szkic (ukryte)</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">💾 Zapisz zdjęcie</button>
                    <button type="button" class="button" id="ssm-cancel-gallery-btn">Anuluj</button>
                </p>
            </form>
        </div>
    </div>
    
    <!-- Lista zdjęć -->
    <div style="margin-top:30px;">
        <?php if ($gallery_items): ?>
            <div class="ssm-gallery-grid">
                <?php foreach ($gallery_items as $item): ?>
                    <div class="ssm-gallery-admin-item">
                        <div class="ssm-gallery-admin-image" 
                             style="background-image:url('<?php echo esc_url($item->image_url); ?>')">
                            <?php if ($item->status == 'draft'): ?>
                                <span class="ssm-draft-badge">SZKIC</span>
                            <?php endif; ?>
                        </div>
                        <div class="ssm-gallery-admin-content">
                            <h3><?php echo esc_html($item->title); ?></h3>
                            <?php if ($item->class_name): ?>
                                <p><strong>Kurs:</strong> <?php echo esc_html($item->class_name); ?></p>
                            <?php endif; ?>
                            <?php if ($item->description): ?>
                                <p><?php echo esc_html($item->description); ?></p>
                            <?php endif; ?>
                            <p class="ssm-gallery-date">
                                📅 <?php echo date('d.m.Y H:i', strtotime($item->created_at)); ?>
                            </p>
                        </div>
                        <div class="ssm-gallery-admin-actions">
                            <button class="button ssm-edit-gallery" 
                                    data-id="<?php echo $item->id; ?>"
                                    data-title="<?php echo esc_attr($item->title); ?>"
                                    data-description="<?php echo esc_attr($item->description); ?>"
                                    data-class-id="<?php echo $item->class_id; ?>"
                                    data-status="<?php echo $item->status; ?>"
                                    data-image-url="<?php echo esc_url($item->image_url); ?>">
                                Edytuj
                            </button>
                            <button class="button button-link-delete ssm-delete-gallery" 
                                    data-id="<?php echo $item->id; ?>">
                                Usuń
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Brak zdjęć w galerii. Dodaj pierwsze zdjęcie!</p>
        <?php endif; ?>
    </div>
</div>

<style>
.ssm-gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.ssm-gallery-admin-item {
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
    overflow: hidden;
}

.ssm-gallery-admin-image {
    height: 200px;
    background-size: cover;
    background-position: center;
    position: relative;
}

.ssm-draft-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #f39c12;
    color: white;
    padding: 5px 12px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.ssm-gallery-admin-content {
    padding: 15px;
}

.ssm-gallery-admin-content h3 {
    margin: 0 0 10px 0;
    font-size: 16px;
}

.ssm-gallery-admin-content p {
    margin: 5px 0;
    font-size: 14px;
}

.ssm-gallery-date {
    color: #666;
    font-size: 13px;
}

.ssm-gallery-admin-actions {
    padding: 10px 15px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Pokaż formularz dodawania
    $('#ssm-add-gallery-btn').on('click', function() {
        $('#ssm-form-title').text('Dodaj zdjęcie');
        $('#ssm-gallery-form')[0].reset();
        $('#gallery-id').val('');
        $('#current-image-preview').html('');
        $('#gallery-image').prop('required', true);
        $('#ssm-gallery-form-container').slideDown();
        $('html, body').animate({ scrollTop: $('#ssm-gallery-form-container').offset().top - 50 }, 500);
    });
    
    $('#ssm-cancel-gallery-btn').on('click', function() {
        $('#ssm-gallery-form-container').slideUp();
    });
    
    // Edycja
    $('.ssm-edit-gallery').on('click', function() {
        var btn = $(this);
        $('#ssm-form-title').text('Edytuj zdjęcie');
        $('#gallery-id').val(btn.data('id'));
        $('#gallery-title').val(btn.data('title'));
        $('#gallery-description').val(btn.data('description'));
        $('#gallery-class').val(btn.data('class-id'));
        $('#gallery-status').val(btn.data('status'));
        $('#gallery-image').prop('required', false);
        
        // Pokaż obecne zdjęcie
        $('#current-image-preview').html('<img src="' + btn.data('image-url') + '" style="max-width:300px; border:1px solid #ccc;">');
        
        $('#ssm-gallery-form-container').slideDown();
        $('html, body').animate({ scrollTop: $('#ssm-gallery-form-container').offset().top - 50 }, 500);
    });
    
    // Submit formularza
    $('#ssm-gallery-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'ssm_save_gallery');
        formData.append('nonce', ssmAdmin.nonce);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('✅ ' + response.data);
                    location.reload();
                } else {
                    alert('❌ ' + response.data);
                }
            }
        });
    });
    
    // Usuwanie
    $('.ssm-delete-gallery').on('click', function() {
        if (!confirm('Czy na pewno usunąć to zdjęcie?')) return;
        
        var id = $(this).data('id');
        $.post(ajaxurl, {
            action: 'ssm_delete_gallery',
            nonce: ssmAdmin.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                alert('✅ Usunięto');
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
});
</script>
