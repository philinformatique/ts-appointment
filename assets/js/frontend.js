/**
 * JavaScript Frontend - Formulaire de réservation
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        const $form = $('#ts-appointment-form');
        const $serviceId = $('#service_id');
        const $appointmentType = $('input[name="appointment_type"]');
        const $appointmentDate = $('#appointment_date');
        const $appointmentTime = $('#appointment_time');
        const $appointmentTimeSlots = $('#appointment-time-slots');
        const $message = $('#form-message');
        const $priceBox = $('.service-price-large');
        const $locationExtras = $('.location-extra');
        const maxDaysAhead = parseInt(tsAppointment.maxDaysAhead || 0, 10);
        const timeFormat = tsAppointment.timeFormat || 'H:i';
        const currencySymbol = tsAppointment.currencySymbol || '€';
        const currencyPosition = tsAppointment.currencyPosition || 'right';
        const restNonce = tsAppointment.restNonce || '';
        const turnstileEnabled = !!tsAppointment.turnstileEnabled;
        const turnstileSiteKey = tsAppointment.turnstileSiteKey || '';
        const $turnstile = $('#ts-turnstile');
        let turnstileToken = '';
        let turnstileWidgetId = null;

        // Initialize progressive reveal UI and mobile interactions
        function initProgressiveReveal() {
            // Hide everything except service selector initially
            const $allGroups = $form.find('.form-group, .form-row, .location-extra, .form-actions, .service-price-large');
            $allGroups.hide();
            $serviceId.closest('.form-group').show();

            const $locGroup = $('#ts-locations').closest('.form-group');
            $locGroup.hide();
            $locationExtras.hide();

            // Hide date/time and time slots
            $appointmentDate.closest('.form-row').hide();
            $appointmentTimeSlots.closest('.form-group').hide();

            $('.form-actions').hide();
            $priceBox.hide();

            // Reveal locations after selecting a service
            $serviceId.on('change.reveal', function(){
                $locGroup.show();
                $locGroup[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            });

            // When a location is selected, reveal extras and date
            $(document).on('change.reveal', 'input[name="appointment_type"]', function(){
                const key = $(this).val();
                $locationExtras.hide();
                const $target = $('#loc-extra-' + key);
                if ($target.length) {
                    $target.show();
                    $target.find('[required]').prop('required', true);
                }
                $appointmentDate.closest('.form-row').show();
                $appointmentDate[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            });

            // Reveal times after date is picked (fetch will populate)
            $appointmentDate.on('change.reveal', function(){
                $appointmentTimeSlots.closest('.form-group').show();
                $appointmentTimeSlots[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            });

            // Enhance selection to reveal submit and price
            const _origSelectTimeSlot = window.selectTimeSlot || null;
            // If selectTimeSlot exists as function, wrap it; otherwise update later via event
            $(document).on('tsSlotSelected.reveal', function(e, $btn){
                $('.form-actions').show();
                $priceBox.show();
                $('.form-actions')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            });

            // Swipe handling on time slots for day navigation
            let touchStartX = null;
            let touchStartY = null;
            $appointmentTimeSlots.on('touchstart', function(e){
                const t = e.originalEvent.touches[0];
                touchStartX = t.clientX;
                touchStartY = t.clientY;
            });
            $appointmentTimeSlots.on('touchend', function(e){
                if (touchStartX === null) return;
                const t = e.originalEvent.changedTouches[0];
                const dx = t.clientX - touchStartX;
                const dy = t.clientY - touchStartY;
                touchStartX = null;
                touchStartY = null;
                if (Math.abs(dx) > 40 && Math.abs(dy) < 80) {
                    if (dx < 0) {
                        if ($('.day-next').length) { $('.day-next').trigger('click'); }
                        $appointmentTimeSlots.trigger('tsNextDay');
                    } else {
                        if ($('.day-prev').length) { $('.day-prev').trigger('click'); }
                        $appointmentTimeSlots.trigger('tsPrevDay');
                    }
                }
            });

            // Accessibility: keyboard activate
            $appointmentTimeSlots.on('keydown', '.time-slot', function(e){
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });

            // When slots are populated, enlarge and make accessible
            $(document).on('tsSlotsPopulated', function(){
                $appointmentTimeSlots.find('.time-slot').addClass('time-slot-large').attr('tabindex', 0);
            });
        }

        initProgressiveReveal();

        // Afficher/masquer les champs supplémentaires selon le lieu
        $(document).on('change', 'input[name="appointment_type"]', function() {
            const key = $(this).val();
            $locationExtras.hide().find('textarea, input, select').prop('required', false);
            const $target = $('#loc-extra-' + key);
            if ($target.length) {
                $target.show();
                $target.find('[required]').prop('required', true);
            }
            updatePriceDisplay();
        });

        // Charger les créneaux disponibles
        $appointmentDate.on('change', function() {
            const serviceId = $serviceId.val();
            const date = $(this).val();

            if (!serviceId || !date) {
                $appointmentTime.prop('disabled', true);
                return;
            }

            fetchAvailableSlots(serviceId, date);
        });

        $serviceId.on('change', function() {
            $appointmentDate.trigger('change');
            updatePriceDisplay();
        });

        // Appliquer les bornes min/max sur la date
        setDateBounds();
        // call init after bounds so date element exists
        // already called above to setup reveal handlers
        if (turnstileEnabled && turnstileSiteKey && $turnstile.length) {
            initTurnstile();
        }

        // Soumettre le formulaire
        $form.on('submit', function(e) {
            e.preventDefault();
            submitForm();
        });

        // Sélectionner un créneau horaire
        function selectTimeSlot($btn) {
            $appointmentTimeSlots.find('.time-slot').removeClass('active');
            $btn.addClass('active');
            $appointmentTime.val($btn.data('time'));
            // Notify progressive reveal logic that a slot was selected
            try { $(document).trigger('tsSlotSelected', [$btn]); } catch (e) {}
        }

        // Charger les créneaux disponibles via API
        function fetchAvailableSlots(serviceId, date) {
            $.ajax({
                url: tsAppointment.restUrl + 'available-slots',
                type: 'GET',
                data: { service_id: serviceId, date: date },
                beforeSend: function(xhr) {
                    if (restNonce) xhr.setRequestHeader('X-WP-Nonce', restNonce);
                    $appointmentTimeSlots.html('<div style="text-align:center;padding:20px;color:#999;">' + __('Chargement...') + '</div>');
                },
                success: function(response) {
                    populateTimeSlots(response);
                },
                error: function(xhr) {
                    const errMsg = xhr.responseJSON?.message || __('Erreur lors du chargement des créneaux');
                    showMessage(errMsg, 'error');
                    $appointmentTimeSlots.empty();
                }
            });
        }

        function updatePriceDisplay() {
            if (!$priceBox.length) return;
            const selectedOption = $serviceId.find('option:selected');
            const pricesRaw = selectedOption.data('prices');
            const duration = selectedOption.data('duration');
            
            if (!pricesRaw) {
                $priceBox.hide();
                return;
            }

            let prices = null;
            try {
                prices = typeof pricesRaw === 'object' ? pricesRaw : JSON.parse(pricesRaw);
            } catch (e) {
                prices = null;
            }

            if (!prices) {
                $priceBox.hide();
                // Show all locations if no prices available
                filterLocationsByService(null);
                return;
            }

            const locationKey = $('input[name="appointment_type"]:checked').val();
            let priceVal = null;
            if (locationKey && prices.hasOwnProperty(locationKey)) {
                priceVal = prices[locationKey];
            } else if (prices.hasOwnProperty('default')) {
                priceVal = prices['default'];
            } else {
                const values = Object.values(prices);
                priceVal = values.length ? values[0] : null;
            }

            if (priceVal === null || priceVal === undefined || priceVal === '') {
                $priceBox.hide();
                return;
            }

            const num = parseFloat(priceVal);
            const formatted = isNaN(num) ? priceVal : num.toFixed(2);
            const display = currencyPosition === 'left' ? (currencySymbol + formatted) : (formatted + currencySymbol);
            
            let durationText = '';
            if (duration) {
                durationText = ' • ' + duration + ' min';
            }
            
            $priceBox.text(display + durationText).show();

            // Filter location cards according to prices: hide locations explicitly priced at 0
            filterLocationsByService(prices);
        }

        // Hide location cards where the selected service price for that location is exactly 0
        function filterLocationsByService(prices) {
            const $cards = $('#ts-locations .location-card');

            if (!prices || typeof prices !== 'object') {
                $cards.show();
                return;
            }

            $cards.each(function() {
                const $card = $(this);
                const $input = $card.find('input[type="radio"]');
                const key = $input.val();

                if (prices.hasOwnProperty(key)) {
                    const val = prices[key];
                    const num = parseFloat(val);
                    if (!isNaN(num) && num === 0) {
                        $card.hide();
                        // If it was selected, clear selection and hide extras
                        if ($input.is(':checked')) {
                            $input.prop('checked', false);
                            $locationExtras.hide();
                        }
                        return;
                    }
                }

                $card.show();
            });
        }

        // Remplir les créneaux horaires
        function populateTimeSlots(slots) {
            $appointmentTimeSlots.empty();

            if (!Array.isArray(slots) || slots.length === 0) {
                showMessage(__('Aucun créneau disponible pour cette date'), 'error');
                return;
            }

            slots.forEach(function(slot) {
                const label = formatTimeLabel(slot);
                const $btn = $('<button type="button">')
                    .addClass('time-slot')
                    .attr('data-time', slot)
                    .text(label)
                    .on('click', function(e) {
                        e.preventDefault();
                        selectTimeSlot($(this));
                    });
                $appointmentTimeSlots.append($btn);
            });
            // Mark slots populated (used to apply accessibility/size tweaks)
            try { $(document).trigger('tsSlotsPopulated'); } catch (e) {}

            showMessage('', '');
        }

        // Soumettre le formulaire
        function submitForm() {
            const formData = {
                service_id: $serviceId.val(),
                // Values from dynamic base schema
                client_name: $('#client_name').val(),
                client_email: $('#client_email').val(),
                client_phone: $('#client_phone').val(),
                appointment_type: $('input[name="appointment_type"]:checked').val(),
                appointment_date: $appointmentDate.val(),
                appointment_time: $appointmentTime.val(),
                // Get client address from the active location extra container (each textarea id is client_address_<key>)
                client_address: (function(){
                    var apType = $('input[name="appointment_type"]:checked').val();
                    if (apType) {
                        var $txt = $('#loc-extra-' + apType).find('textarea[name="client_address"]');
                        if ($txt.length) return $txt.val();
                    }
                    // Fallback: any visible client_address textarea
                    var $visible = $('textarea[name="client_address"]:visible');
                    if ($visible.length) return $visible.first().val();
                    return $('#client_address').val() || '';
                })(),
                notes: $('#notes').val(),
                extra: Object.assign({}, collectExtraFields(), collectBaseExtras())
            };

            if (turnstileEnabled && (!turnstileToken || !turnstileToken.length)) {
                showMessage(__('Merci de valider le contrôle anti-robot.'), 'error');
                return;
            }

            if (turnstileEnabled) {
                formData.turnstile_token = turnstileToken;
            }

            // Validation
            if (!formData.service_id || !formData.client_name || !formData.client_email || 
                !formData.client_phone || !formData.appointment_type || 
                !formData.appointment_date || !formData.appointment_time) {
                showMessage(__('Veuillez remplir tous les champs obligatoires'), 'error');
                return;
            }

            // Required extras are enforced by HTML required attributes within shown container

            // Envoyer la requête
            const restUrl = tsAppointment.restUrl;

            $.ajax({
                url: restUrl + 'appointment/book',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                beforeSend: function(xhr) {
                    if (restNonce) {
                        xhr.setRequestHeader('X-WP-Nonce', restNonce);
                    }
                    $form.css('opacity', '0.6').css('pointer-events', 'none');
                    showMessage(__('Traitement en cours...'), 'loading');
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(__('✓ Rendez-vous réservé avec succès! Vous recevrez une confirmation par email.'), 'success');
                        $appointmentTime.prop('disabled', true);

                        // Réinitialiser le formulaire
                        setTimeout(function() {
                            $form.css('opacity', '1').css('pointer-events', 'auto');
                            $form[0].reset();
                            $locationExtras.hide();
                            showMessage('', '');
                            if (turnstileEnabled && window.turnstile && turnstileWidgetId !== null) {
                                window.turnstile.reset(turnstileWidgetId);
                                turnstileToken = '';
                            }
                        }, 2000);
                    } else {
                        showMessage(response.message || __('Une erreur est survenue'), 'error');
                        $form.css('opacity', '1').css('pointer-events', 'auto');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMsg = __('Erreur lors de la réservation');
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showMessage(errorMsg, 'error');
                    $form.css('opacity', '1').css('pointer-events', 'auto');
                }
            });
        }

        function initTurnstile() {
            let attempts = 0;
            const maxAttempts = 20;

            const tryRender = function() {
                if (window.turnstile && typeof window.turnstile.render === 'function') {
                    turnstileWidgetId = window.turnstile.render('#ts-turnstile', {
                        sitekey: turnstileSiteKey,
                        callback: function(token) {
                            turnstileToken = token;
                        },
                        'expired-callback': function() {
                            turnstileToken = '';
                        },
                        'error-callback': function() {
                            turnstileToken = '';
                        },
                        'unsupported-callback': function() {
                            turnstileToken = '';
                            showMessage(__('La vérification Turnstile est indisponible sur ce navigateur.'), 'error');
                        }
                    });
                    return;
                }

                attempts++;
                if (attempts <= maxAttempts) {
                    setTimeout(tryRender, 250);
                } else {
                    showMessage(__('Impossible de charger le contrôle anti-robot.'), 'error');
                }
            };

            tryRender();
        }

        function collectExtraFields() {
            const result = {};
            const selected = $('input[name="appointment_type"]:checked').val();
            if (!selected) return result;
            const $container = $('#loc-extra-' + selected);
            if (!$container.length) return result;
            $container.find('[name^="extra["]').each(function() {
                const name = $(this).attr('name');
                const key = name.replace(/^extra\[(.+)\]$/, '$1');
                if ($(this).is(':checkbox')) {
                    result[key] = $(this).is(':checked') ? '1' : '';
                } else {
                    result[key] = $(this).val();
                }
            });
            return result;
        }

        function collectBaseExtras() {
            const result = {};
            const exclude = ['service_id','appointment_type','appointment_date','appointment_time','client_address','client_name','client_email','client_phone','notes'];
            $('#ts-appointment-form').find('input, select, textarea').each(function() {
                const name = $(this).attr('name');
                if (!name || name.startsWith('extra[')) return;
                if (exclude.indexOf(name) !== -1) return;
                if ($(this).is(':checkbox')) {
                    result[name] = $(this).is(':checked') ? '1' : '';
                } else {
                    result[name] = $(this).val();
                }
            });
            return result;
        }

        // Afficher les messages
        function showMessage(message, type = 'info') {
            $message.removeClass('success error loading info');
            if (message) {
                $message.addClass(type).text(message);
            } else {
                $message.empty();
            }
        }

        // Applique les bornes min/max de date en fonction de la config
        function setDateBounds() {
            const today = new Date();
            const isoToday = today.toISOString().split('T')[0];
            $appointmentDate.attr('min', isoToday);
            if (maxDaysAhead > 0) {
                const maxDate = new Date();
                maxDate.setDate(maxDate.getDate() + maxDaysAhead);
                const isoMax = maxDate.toISOString().split('T')[0];
                $appointmentDate.attr('max', isoMax);
            }
        }

        // Formate l'affichage des heures selon le format choisi
        function formatTimeLabel(timeStr) {
            if (!timeStr || typeof timeStr !== 'string') return timeStr;
            const parts = timeStr.split(':');
            if (parts.length < 2) return timeStr;
            const d = new Date();
            d.setHours(parseInt(parts[0], 10), parseInt(parts[1], 10), 0, 0);
            const hour12 = /[aAgGh]/.test(timeFormat);
            try {
                return new Intl.DateTimeFormat('fr-FR', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: hour12
                }).format(d);
            } catch (e) {
                return timeStr;
            }
        }

        // Traduction simple
        function __(text) {
            try {
                if (typeof tsAppointment !== 'undefined' && tsAppointment.i18n && tsAppointment.i18n[text]) {
                    return tsAppointment.i18n[text];
                }
            } catch (e) {}
            return text;
        }
    });

})(jQuery);
