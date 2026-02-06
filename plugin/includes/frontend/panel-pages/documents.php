<?php
// Dokumenty - pobieranie wzorów i upload dokumentów
if (!defined('ABSPATH')) exit;

$success_message = '';
$error_message = '';

// Obsługa uploadu dokumentu
if (isset($_POST['ssm_upload_document']) && isset($_FILES['document_file'])) {
    
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    
    $uploadedfile = $_FILES['document_file'];
    $upload_overrides = array('test_form' => false);
    
    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
    
    if ($movefile && !isset($movefile['error'])) {
        // Zapisz info o dokumencie w bazie
        $doc_type = sanitize_text_field($_POST['document_type']);
        $doc_notes = sanitize_textarea_field($_POST['document_notes']);
        
        $wpdb->insert($wpdb->prefix . 'ssm_documents', array(
            'client_id' => $client->id,
            'document_type' => $doc_type,
            'file_url' => $movefile['url'],
            'file_path' => $movefile['file'],
            'notes' => $doc_notes,
            'uploaded_at' => current_time('mysql')
        ));
        
        $success_message = '✅ Dokument został przesłany!';
    } else {
        $error_message = '❌ Błąd podczas przesyłania: ' . $movefile['error'];
    }
}

// Pobierz dokumenty klienta
$documents = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ssm_documents 
     WHERE client_id = %d 
     ORDER BY uploaded_at DESC",
    $client->id
));
?>

<div class="ssm-page-header">
    <h1>📄 Dokumenty</h1>
    <p>Pobierz wzory i prześlij podpisane dokumenty</p>
</div>

<?php if ($success_message): ?>
    <div class="ssm-notice ssm-notice-success"><?php echo $success_message; ?></div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="ssm-notice ssm-notice-error"><?php echo $error_message; ?></div>
<?php endif; ?>

<!-- Wzory do pobrania -->
<div class="ssm-section">
    <h2>📥 Wzory dokumentów do pobrania</h2>
    
    <div class="ssm-templates-grid">
        <?php 
        $contract_url = get_option('ssm_contract_template_url', '');
        $health_url = get_option('ssm_health_template_url', '');
        $consent_url = get_option('ssm_consent_template_url', '');
        ?>
        
        <?php if ($contract_url): ?>
        <div class="ssm-template-card">
            <div class="ssm-template-icon">📋</div>
            <h4>Umowa</h4>
            <p>Wzór umowy na udział w zajęciach</p>
            <a href="<?php echo esc_url($contract_url); ?>" class="ssm-btn ssm-btn-download" target="_blank" download>
                Pobierz PDF
            </a>
        </div>
        <?php endif; ?>
        
        <?php if ($health_url): ?>
        <div class="ssm-template-card">
            <div class="ssm-template-icon">🏥</div>
            <h4>Karta zdrowia</h4>
            <p>Formularz informacji o stanie zdrowia</p>
            <a href="<?php echo esc_url($health_url); ?>" class="ssm-btn ssm-btn-download" target="_blank" download>
                Pobierz PDF
            </a>
        </div>
        <?php endif; ?>
        
        <?php if ($consent_url): ?>
        <div class="ssm-template-card">
            <div class="ssm-template-icon">📝</div>
            <h4>Zgody</h4>
            <p>Zgody na przetwarzanie danych, wizerunek</p>
            <a href="<?php echo esc_url($consent_url); ?>" class="ssm-btn ssm-btn-download" target="_blank" download>
                Pobierz PDF
            </a>
        </div>
        <?php endif; ?>
        
        <?php if (!$contract_url && !$health_url && !$consent_url): ?>
            <p style="grid-column: 1/-1; text-align:center; color:#6c757d;">
                Brak dostępnych wzorów. Administrator jeszcze nie dodał dokumentów.
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Upload dokumentów -->
<div class="ssm-section">
    <h2>📤 Prześlij podpisany dokument</h2>
    
    <form method="post" enctype="multipart/form-data" class="ssm-upload-form">
        <div class="ssm-form-group">
            <label for="document_type">Typ dokumentu *</label>
            <select id="document_type" name="document_type" required class="ssm-input">
                <option value="">-- Wybierz --</option>
                <option value="contract">Umowa</option>
                <option value="health">Karta zdrowia</option>
                <option value="consent">Zgody</option>
                <option value="other">Inny</option>
            </select>
        </div>
        
        <div class="ssm-form-group">
            <label for="document_file">Plik *</label>
            <input type="file" id="document_file" name="document_file" required 
                   class="ssm-input" accept=".pdf,.jpg,.jpeg,.png">
            <small class="ssm-help-text">Dozwolone formaty: PDF, JPG, PNG. Max 5MB</small>
        </div>
        
        <div class="ssm-form-group">
            <label for="document_notes">Notatki (opcjonalne)</label>
            <textarea id="document_notes" name="document_notes" rows="3" 
                      class="ssm-input" placeholder="Dodatkowe informacje..."></textarea>
        </div>
        
        <button type="submit" name="ssm_upload_document" class="ssm-btn ssm-btn-primary">
            📤 Prześlij dokument
        </button>
    </form>
</div>

<!-- Przesłane dokumenty -->
<?php if ($documents): ?>
<div class="ssm-section">
    <h2>📁 Twoje dokumenty</h2>
    
    <table class="ssm-docs-table">
        <thead>
            <tr>
                <th>Typ</th>
                <th>Data</th>
                <th>Notatki</th>
                <th>Akcje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($documents as $doc): 
                $doc_types = array(
                    'contract' => 'Umowa',
                    'health' => 'Karta zdrowia',
                    'consent' => 'Zgody',
                    'other' => 'Inny'
                );
            ?>
                <tr>
                    <td><?php echo $doc_types[$doc->document_type] ?? 'Dokument'; ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($doc->uploaded_at)); ?></td>
                    <td><?php echo esc_html($doc->notes); ?></td>
                    <td>
                        <a href="<?php echo esc_url($doc->file_url); ?>" target="_blank" class="ssm-btn-small">
                            👁️ Zobacz
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<style>
.ssm-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.ssm-template-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    text-align: center;
    border: 2px dashed #dee2e6;
    transition: all 0.3s;
}

.ssm-template-card:hover {
    border-color: #3498db;
    transform: translateY(-3px);
}

.ssm-template-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.ssm-template-card h4 {
    margin: 0 0 8px 0;
    color: #2c3e50;
}

.ssm-template-card p {
    margin: 0 0 15px 0;
    color: #6c757d;
    font-size: 14px;
}

.ssm-btn-download {
    display: inline-block;
    padding: 10px 20px;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
    transition: all 0.3s;
}

.ssm-btn-download:hover {
    background: #2980b9;
}

.ssm-upload-form {
    background: white;
    padding: 25px;
    border-radius: 8px;
}

.ssm-docs-table {
    width: 100%;
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.ssm-docs-table thead {
    background: #2c3e50;
    color: white;
}

.ssm-docs-table th,
.ssm-docs-table td {
    padding: 12px;
    text-align: left;
}

.ssm-docs-table tbody tr:hover {
    background: #f8f9fa;
}

.ssm-btn-small {
    padding: 6px 12px;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 13px;
}

.ssm-btn-small:hover {
    background: #2980b9;
}
</style>
