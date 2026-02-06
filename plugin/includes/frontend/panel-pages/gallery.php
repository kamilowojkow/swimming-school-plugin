<?php
// Galeria - zdjęcia z zajęć
if (!defined('ABSPATH')) exit;

// Pobierz zdjęcia (zakładam nową tabelę ssm_gallery)
$gallery_items = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}ssm_gallery 
     WHERE status = 'published'
     ORDER BY created_at DESC 
     LIMIT 50"
);
?>

<div class="ssm-page-header">
    <h1>📷 Galeria</h1>
    <p>Zdjęcia z zajęć na basenie</p>
</div>

<?php if ($gallery_items): ?>
    <div class="ssm-gallery-grid">
        <?php foreach ($gallery_items as $item): ?>
            <div class="ssm-gallery-item">
                <img src="<?php echo esc_url($item->image_url); ?>" 
                     alt="<?php echo esc_attr($item->title); ?>">
                <div class="ssm-gallery-overlay">
                    <h4><?php echo esc_html($item->title); ?></h4>
                    <p><?php echo date('d.m.Y', strtotime($item->created_at)); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="ssm-empty-state">
        <p>📷 Galeria jest pusta</p>
        <p><small>Zdjęcia z zajęć będą tu wkrótce</small></p>
    </div>
<?php endif; ?>

<style>
.ssm-gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.ssm-gallery-item {
    position: relative;
    aspect-ratio: 4/3;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.3s;
}

.ssm-gallery-item:hover {
    transform: scale(1.05);
}

.ssm-gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ssm-gallery-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    color: white;
    padding: 15px;
    opacity: 0;
    transition: opacity 0.3s;
}

.ssm-gallery-item:hover .ssm-gallery-overlay {
    opacity: 1;
}

.ssm-gallery-overlay h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.ssm-gallery-overlay p {
    margin: 0;
    font-size: 13px;
    opacity: 0.9;
}
</style>
