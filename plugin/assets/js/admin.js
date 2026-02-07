// Swimming School Manager v2.0 - Admin JavaScript
// Pełna implementacja AJAX

jQuery(document).ready(function($) {
    
    // ========================================
    // KLIENCI (Rodzice)
    // ========================================
    
    $('#ssm-add-client-btn').on('click', function() {
        $('#ssm-form-title').text('Dodaj rodzica');
        $('#ssm-client-form')[0].reset();
        $('#client-id').val('');
        $('#ssm-client-form-container').slideDown();
    });
    
    $('#ssm-cancel-client-btn').on('click', function() {
        $('#ssm-client-form-container').slideUp();
    });
    
    $('.ssm-edit-client').on('click', function() {
        var btn = $(this);
        $('#ssm-form-title').text('Edytuj rodzica');
        $('#client-id').val(btn.data('id'));
        $('#client-first-name').val(btn.data('first-name'));
        $('#client-last-name').val(btn.data('last-name'));
        $('#client-email').val(btn.data('email'));
        $('#client-phone').val(btn.data('phone'));
        $('#client-notes').val(btn.data('notes'));
        $('#ssm-client-form-container').slideDown();
        $('html, body').animate({ scrollTop: $('#ssm-client-form-container').offset().top - 50 }, 500);
    });
    
    $('#ssm-client-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=ssm_save_client';
        formData += '&nonce=' + ssmAdmin.nonce;
        
        $.post(ssmAdmin.ajax_url, formData, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ Błąd: ' + response.data);
            }
        });
    });
    
    $('.ssm-delete-client').on('click', function() {
        if (!confirm('Czy na pewno usunąć tego rodzica?')) return;
        
        var id = $(this).data('id');
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_delete_client',
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
    
    // ========================================
    // DZIECI
    // ========================================
    
    $('#ssm-add-child-btn').on('click', function() {
        $('#ssm-form-title').text('Dodaj dziecko');
        $('#ssm-child-form')[0].reset();
        $('#child-id').val('');
        $('#child-active').prop('checked', true);
        $('#ssm-child-form-container').slideDown();
    });
    
    $('#ssm-cancel-child-btn').on('click', function() {
        $('#ssm-child-form-container').slideUp();
    });
    
    $('.ssm-edit-child').on('click', function() {
        var btn = $(this);
        $('#ssm-form-title').text('Edytuj dziecko');
        $('#child-id').val(btn.data('id'));
        $('#child-first-name').val(btn.data('first-name'));
        $('#child-last-name').val(btn.data('last-name'));
        $('#child-dob').val(btn.data('dob'));
        $('#child-medical').val(btn.data('medical'));
        $('#child-skills').val(btn.data('skills'));
        $('#child-active').prop('checked', btn.data('active') == 1);
        $('#ssm-child-form-container').slideDown();
        $('html, body').animate({ scrollTop: $('#ssm-child-form-container').offset().top - 50 }, 500);
    });
    
    $('#ssm-child-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=ssm_save_child';
        formData += '&nonce=' + ssmAdmin.nonce;
        
        $.post(ssmAdmin.ajax_url, formData, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    $('.ssm-delete-child').on('click', function() {
        if (!confirm('Czy na pewno usunąć to dziecko?')) return;
        
        var id = $(this).data('id');
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_delete_child',
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
    
    // Modal przypisywania rodziców
    $('.ssm-assign-parent-btn').on('click', function() {
        var childId = $(this).data('child-id');
        var childName = $(this).data('child-name');
        
        $('#modal-child-id').val(childId);
        $('#modal-child-name').text(childName);
        $('#ssm-assign-parent-modal').show();
        
        // Załaduj obecnych rodziców
        loadCurrentParents(childId);
    });
    
    $('.ssm-modal-close').on('click', function() {
        $('#ssm-assign-parent-modal').hide();
    });
    
    $('#ssm-assign-parent-save').on('click', function() {
        var childId = $('#modal-child-id').val();
        var parentId = $('#modal-parent-select').val();
        var relationship = $('#modal-relationship').val();
        var primary = $('#modal-primary').is(':checked') ? 1 : 0;
        
        if (!parentId) {
            alert('Wybierz rodzica!');
            return;
        }
        
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_assign_parent',
            nonce: ssmAdmin.nonce,
            child_id: childId,
            client_id: parentId,
            relationship: relationship,
            primary_contact: primary
        }, function(response) {
            if (response.success) {
                alert('✅ Rodzic przypisany!');
                loadCurrentParents(childId);
                $('#modal-parent-select').val('');
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    function loadCurrentParents(childId) {
        $('#modal-current-parents').html('<p>Ładowanie...</p>');
        
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_get_child_parents',
            nonce: ssmAdmin.nonce,
            child_id: childId
        }, function(response) {
            if (response.success) {
                var html = '';
                if (response.data.length > 0) {
                    response.data.forEach(function(parent) {
                        var primaryClass = parent.primary_contact == 1 ? 'primary' : '';
                        var primaryLabel = parent.primary_contact == 1 ? ' ⭐ Główny kontakt' : '';
                        html += '<div class="parent-item ' + primaryClass + '">';
                        html += '<div>';
                        html += '<strong>' + parent.name + '</strong>' + primaryLabel + '<br>';
                        html += '<small>' + parent.relationship + '</small>';
                        html += '</div>';
                        html += '<button class="button button-small ssm-remove-parent" data-id="' + parent.id + '">Usuń</button>';
                        html += '</div>';
                    });
                } else {
                    html = '<p style="color:#d63638;">Brak przypisanych rodziców</p>';
                }
                $('#modal-current-parents').html(html);
            }
        });
    }
    
    $(document).on('click', '.ssm-remove-parent', function() {
        if (!confirm('Usunąć tego rodzica?')) return;
        
        var id = $(this).data('id');
        var childId = $('#modal-child-id').val();
        
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_remove_parent',
            nonce: ssmAdmin.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                alert('✅ Usunięto');
                loadCurrentParents(childId);
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    // ========================================
    // OBIEKTY
    // ========================================
    
    $('#ssm-add-facility-btn').on('click', function() {
        $('#ssm-form-title').text('Dodaj obiekt');
        $('#ssm-facility-form')[0].reset();
        $('#facility-id').val('');
        $('#ssm-facility-form-container').slideDown();
    });
    
    $('#ssm-cancel-facility-btn').on('click', function() {
        $('#ssm-facility-form-container').slideUp();
    });
    
    $('.ssm-edit-facility').on('click', function() {
        var btn = $(this);
        $('#ssm-form-title').text('Edytuj obiekt');
        $('#facility-id').val(btn.data('id'));
        $('#facility-name').val(btn.data('name'));
        $('#facility-address').val(btn.data('address'));
        $('#facility-city').val(btn.data('city'));
        $('#facility-url').val(btn.data('url'));
        $('#facility-description').val(btn.data('description'));
        $('#ssm-facility-form-container').slideDown();
        $('html, body').animate({ scrollTop: $('#ssm-facility-form-container').offset().top - 50 }, 500);
    });
    
    $('#ssm-facility-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=ssm_save_facility';
        formData += '&nonce=' + ssmAdmin.nonce;
        
        $.post(ssmAdmin.ajax_url, formData, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    $('.ssm-delete-facility').on('click', function() {
        if (!confirm('Czy na pewno usunąć ten obiekt?')) return;
        
        var id = $(this).data('id');
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_delete_facility',
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
    
    // ========================================
    // INSTRUKTORZY
    // ========================================
    
    $('#ssm-add-instructor-btn').on('click', function() {
        $('#ssm-form-title').text('Dodaj instruktora');
        $('#ssm-instructor-form')[0].reset();
        $('#instructor-id').val('');
        $('#instructor-active').prop('checked', true);
        $('#ssm-instructor-form-container').slideDown();
    });
    
    $('#ssm-cancel-instructor-btn').on('click', function() {
        $('#ssm-instructor-form-container').slideUp();
    });
    
    $('.ssm-edit-instructor').on('click', function() {
        var btn = $(this);
        $('#ssm-form-title').text('Edytuj instruktora');
        $('#instructor-id').val(btn.data('id'));
        $('#instructor-first-name').val(btn.data('first-name'));
        $('#instructor-last-name').val(btn.data('last-name'));
        $('#instructor-email').val(btn.data('email'));
        $('#instructor-phone').val(btn.data('phone'));
        $('#instructor-specialization').val(btn.data('specialization'));
        $('#instructor-hourly-rate').val(btn.data('hourly-rate'));
        $('#instructor-photo').val(btn.data('photo'));
        $('#instructor-url').val(btn.data('url'));
        $('#instructor-bio').val(btn.data('bio'));
        $('#instructor-active').prop('checked', btn.data('active') == 1);
        
        // Sprawdź czy ma konto WordPress
        var userId = btn.data('user-id');
        if (userId && userId > 0) {
            $('#create-user-row').hide();
            $('#existing-user-row').show();
            $('#existing-user-details').html('User ID: ' + userId + '<br>Email: ' + btn.data('email'));
        } else {
            $('#create-user-row').show();
            $('#existing-user-row').hide();
            $('#create-wordpress-user').prop('checked', false);
            $('#username-row, #password-row, #send-email-row').hide();
        }
        
        $('#ssm-instructor-form-container').slideDown();
        $('html, body').animate({ scrollTop: $('#ssm-instructor-form-container').offset().top - 50 }, 500);
    });
    
    $('#ssm-instructor-form').on('submit', function(e) {
        e.preventDefault();
        
        console.log('Wysyłam formularz instruktora...');
        
        var formData = $(this).serialize();
        formData += '&action=ssm_save_instructor';
        formData += '&nonce=' + ssmAdmin.nonce;
        
        console.log('Form data:', formData);
        
        $.post(ssmAdmin.ajax_url, formData, function(response) {
            console.log('Response:', response);
            
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', xhr, status, error);
            alert('❌ Błąd AJAX: ' + error);
        });
    });
    
    $('.ssm-delete-instructor').on('click', function() {
        if (!confirm('Czy na pewno usunąć tego instruktora?')) return;
        
        var id = $(this).data('id');
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_delete_instructor',
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
    
    // ========================================
    // KURSY
    // ========================================
    
    $('#ssm-add-class-btn').on('click', function() {
        $('#ssm-form-title').text('Dodaj kurs');
        $('#ssm-class-form')[0].reset();
        $('#class-id').val('');
        $('#class-status').val('active');
        $('#class-total-price').text('500.00');
        $('#ssm-class-form-container').slideDown();
    });
    
    $('#ssm-cancel-class-btn').on('click', function() {
        $('#ssm-class-form-container').slideUp();
    });
    
    $('.ssm-edit-class').on('click', function() {
        var btn = $(this);
        $('#ssm-form-title').text('Edytuj kurs');
        $('#class-id').val(btn.data('id'));
        $('#class-name').val(btn.data('name'));
        $('#class-facility').val(btn.data('facility-id'));
        $('#class-instructor').val(btn.data('instructor-id'));
        $('#class-day').val(btn.data('day'));
        $('#class-time-start').val(btn.data('time-start'));
        $('#class-time-end').val(btn.data('time-end'));
        $('#class-start-date').val(btn.data('start-date'));
        $('#class-session-count').val(btn.data('session-count'));
        $('#class-price-session').val(btn.data('price-session'));
        $('#class-level').val(btn.data('level'));
        $('#class-max-participants').val(btn.data('max'));
        $('#class-max-absences').val(btn.data('max-absences') || 2);
        $('#class-allow-makeups').prop('checked', btn.data('allow-makeups') == 1);
        $('#class-url').val(btn.data('url'));
        $('#class-description').val(btn.data('description'));
        $('#class-status').val(btn.data('status'));

        // Przelicz cenę
        var total = parseFloat(btn.data('session-count')) * parseFloat(btn.data('price-session'));
        $('#class-total-price').text(total.toFixed(2));

        $('#ssm-class-form-container').slideDown();
        $('html, body').animate({ scrollTop: $('#ssm-class-form-container').offset().top - 50 }, 500);
    });
    
    $('#ssm-class-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        formData += '&action=ssm_save_class';
        formData += '&nonce=' + ssmAdmin.nonce;

        // Oblicz total_price
        var sessionCount = $('#class-session-count').val();
        var pricePerSession = $('#class-price-session').val();
        var totalPrice = parseFloat(sessionCount) * parseFloat(pricePerSession);
        formData += '&total_price=' + totalPrice.toFixed(2);

        // Obsługa checkboxa allow_makeups (wysyłamy 0 jeśli nie zaznaczony)
        if (!$('#class-allow-makeups').is(':checked')) {
            formData += '&allow_makeups=0';
        }

        var isNew = !$('#class-id').val();
        
        $.post(ssmAdmin.ajax_url, formData, function(response) {
            if (response.success) {
                if (isNew) {
                    alert('✅ Kurs dodany!\n📅 Harmonogram został automatycznie wygenerowany.\n\n' + response.data);
                    location.reload();
                } else {
                    // Przy edycji - pokaż info o harmonogramie
                    alert('✅ Kurs zaktualizowany!\n\nℹ️ Harmonogram NIE został automatycznie zmieniony.\nMożesz go przelić ręcznie klikając "🔄 Harmonogram".');
                    
                    // Pokaż info box z przyciskiem
                    var classId = $('#class-id').val();
                    $('#ssm-regenerate-from-form').attr('data-class-id', classId);
                    $('#ssm-regenerate-info').slideDown();
                }
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    // Przycisk regeneracji z formularza
    $('#ssm-regenerate-from-form').on('click', function() {
        var classId = $(this).attr('data-class-id');
        
        if (!confirm('Przelić harmonogram?\n\nUWAGA: Obecny harmonogram zostanie usunięty i utworzony od nowa!')) return;
        
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_regenerate_schedule',
            nonce: ssmAdmin.nonce,
            class_id: classId
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    // Przycisk regeneracji z listy kursów
    $('.ssm-regenerate-class').on('click', function() {
        var classId = $(this).data('id');
        var className = $(this).data('name');
        
        if (!confirm('Przelić harmonogram dla kursu "' + className + '"?\n\nUWAGA: Obecny harmonogram zostanie usunięty i utworzony od nowa na podstawie aktualnych danych kursu!')) return;
        
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_regenerate_schedule',
            nonce: ssmAdmin.nonce,
            class_id: classId
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    $('.ssm-delete-class').on('click', function() {
        if (!confirm('Czy na pewno usunąć ten kurs?\n\nUWAGA: Usunięte zostaną też wszystkie zajęcia (sesje) i zapisy!')) return;
        
        var id = $(this).data('id');
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_delete_class',
            nonce: ssmAdmin.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                alert('✅ Usunięto kurs i wszystkie powiązane dane');
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    // ========================================
    // ZAPISY
    // ========================================
    
    $('#ssm-add-enrollment-btn').on('click', function() {
        $('#ssm-enrollment-form')[0].reset();
        $('#enrollment-child').html('<option value="">-- Najpierw wybierz rodzica --</option>').prop('disabled', true);
        $('#enrollment-class-details').hide();
        $('#ssm-enrollment-form-container').slideDown();
    });
    
    $('#ssm-cancel-enrollment-btn').on('click', function() {
        $('#ssm-enrollment-form-container').slideUp();
    });
    
    $('#ssm-enrollment-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=ssm_enroll_child';
        formData += '&nonce=' + ssmAdmin.nonce;
        
        $.post(ssmAdmin.ajax_url, formData, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    $('.ssm-unenroll').on('click', function() {
        var childName = $(this).data('child');
        var className = $(this).data('class');
        
        if (!confirm('Wypisać ' + childName + ' z kursu "' + className + '"?')) return;
        
        var id = $(this).data('id');
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_unenroll_child',
            nonce: ssmAdmin.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                alert('✅ Dziecko wypisane z kursu');
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
    $('.ssm-delete-enrollment').on('click', function() {
        if (!confirm('Usunąć ten zapis?')) return;
        
        var id = $(this).data('id');
        $.post(ssmAdmin.ajax_url, {
            action: 'ssm_delete_enrollment',
            nonce: ssmAdmin.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                alert('✅ Zapis usunięty');
                location.reload();
            } else {
                alert('❌ ' + response.data);
            }
        });
    });
    
});
