<div class="ts-appointment-booking-form-wrapper">
    <div class="ts-appointment-container">
        <div class="booking-form-card">
            <h2><?php echo esc_html__('Réserver un rendez-vous', 'ts-appointment'); ?></h2>
            
            <form class="ts-appointment-form" id="ts-appointment-form">
                <!-- Service selection -->
                <div class="form-group">
                    <label for="service_id"><?php echo esc_html__('Service', 'ts-appointment'); ?> <span class="required">*</span></label>
                    <select id="service_id" name="service_id" required>
                        <option value=""><?php echo esc_html__('Sélectionner un service', 'ts-appointment'); ?></option>
                        <?php
                        $services = TS_Appointment_Database::get_services();
                        foreach ($services as $service) {
                            $price_raw = $service->price;
                            $price_map = json_decode($price_raw, true);
                            if (!is_array($price_map)) {
                                $price_map = array('default' => is_numeric($price_raw) ? floatval($price_raw) : $price_raw);
                            }
                            $data_prices = esc_attr(wp_json_encode($price_map));
                            $readable = esc_html(ts_appointment_format_duration(intval($service->duration)));
                            echo '<option value="' . esc_attr($service->id) . '" data-prices="' . $data_prices . '" data-duration="' . esc_attr($service->duration) . '" data-duration-readable="' . esc_attr($readable) . '">' . esc_html($service->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- client info (moved below date/time for progressive reveal) -->

                <?php
                // Locations radios and per-location extra fields
                $locations_json = get_option('ts_appointment_locations_config');
                $locations = json_decode($locations_json, true);
                if (!is_array($locations)) { $locations = array(); }
                ?>
                <div class="form-group">
                    <label><?php echo esc_html__('Lieu du rendez-vous', 'ts-appointment'); ?> <span class="required">*</span></label>
                    <div class="location-cards" id="ts-locations">
                        <?php foreach ($locations as $loc): $lkey = esc_attr($loc['key']); $llabel = esc_html($loc['label']); $licon = !empty($loc['icon']) ? esc_html($loc['icon']) : '📍'; ?>
                            <label class="location-card">
                                <input type="radio" name="appointment_type" value="<?php echo $lkey; ?>" required>
                                <span class="location-card-content">
                                    <span class="location-icon"><?php echo $licon; ?></span>
                                    <span class="location-text"><?php echo $llabel; ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php
                $business_address = get_option('ts_appointment_business_address');
                foreach ($locations as $loc) {
                    $lkey = esc_attr($loc['key']);
                    echo '<div class="location-extra" id="loc-extra-'. $lkey .'" style="display:none">';
                    if (!empty($loc['note'])) {
                        echo '<div class="info-box">';
                        echo '<div class="info-icon">i</div>';
                        echo '<div class="info-content">' . wpautop(wp_kses_post($loc['note'])) . '</div>';
                        echo '</div>';
                    }
                    // client address will be rendered centrally in ts-client-info; per-location address removed
                    if (!empty($loc['fields']) && is_array($loc['fields'])) {
                        // Champs système à ne pas dupliquer
                        $system_fields = array('client_name', 'client_email', 'client_phone', 'notes');
                        
                        foreach ($loc['fields'] as $field) {
                            $fk = esc_attr($field['key']);
                            
                            // Skip if it's a system field to avoid duplicates
                            if (in_array($fk, $system_fields)) {
                                continue;
                            }
                            
                            $flabel = esc_html($field['label']);
                            $ftype = isset($field['type']) ? $field['type'] : 'text';
                            $freq = !empty($field['required']);
                            $fplaceholder = isset($field['placeholder']) ? esc_attr($field['placeholder']) : '';
                            echo '<div class="form-group">';
                            echo '<label for="extra_' . $fk . '">' . $flabel . ($freq ? ' <span class="required">*</span>' : '') . '</label>';
                            if ($ftype === 'textarea') {
                                echo '<textarea id="extra_' . $fk . '" name="extra[' . $fk . ']" rows="3" placeholder="' . $fplaceholder . '"' . ($freq ? ' required' : '') . '></textarea>';
                            } elseif ($ftype === 'select' && !empty($field['options']) && is_array($field['options'])) {
                                echo '<select id="extra_' . $fk . '" name="extra[' . $fk . ']"' . ($freq ? ' required' : '') . '>';
                                echo '<option value="">' . esc_html__('Sélectionner', 'ts-appointment') . '</option>';
                                foreach ($field['options'] as $opt) {
                                    echo '<option value="' . esc_attr($opt) . '">' . esc_html($opt) . '</option>';
                                }
                                echo '</select>';
                            } elseif ($ftype === 'checkbox') {
                                echo '<label class="checkbox"><input type="checkbox" id="extra_' . $fk . '" name="extra[' . $fk . ']" value="1"' . ($freq ? ' required' : '') . '> ' . $flabel . '</label>';
                            } else {
                                $html_type = in_array($ftype, array('text','email','tel','number','date','time')) ? $ftype : 'text';
                                echo '<input type="' . esc_attr($html_type) . '" id="extra_' . $fk . '" name="extra[' . $fk . ']" placeholder="' . $fplaceholder . '"' . ($freq ? ' required' : '') . '>';
                            }
                            echo '</div>';
                        }
                    }
                    echo '</div>';
                }
                // Price display (placed between location-extra blocks and the date/time row)
                echo '<div id="service-price" class="service-price-large" style="display:none;"></div>';
                ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_date"><span id="appointment_date_label"><?php echo esc_html__('Choisir une date', 'ts-appointment'); ?></span> <span class="required">*</span></label>
                        <input type="date" id="appointment_date" name="appointment_date" required>
                    </div>

                    <div class="form-group">
                        <label for="appointment_time"><span id="appointment_time_label"><?php echo esc_html__('Choisir une heure', 'ts-appointment'); ?></span> <span class="required">*</span></label>
                        <input type="hidden" id="appointment_time" name="appointment_time" value="">
                        <div id="appointment-time-slots" class="time-slots-grid">
                            <!-- Rempli par JavaScript -->
                        </div>
                    </div>
                </div>


                <!-- Client info fields (rendered from schema) -->
                <div id="ts-client-info" class="client-info" style="display:none;">
                <?php
                // Render base form from schema (moved here so sequence is: service -> location -> date/time -> client info)
                $form_schema_json = get_option('ts_appointment_form_schema');
                $form_schema = json_decode($form_schema_json, true);
                if (!is_array($form_schema)) { $form_schema = array(); }
                
                // Helper function to render a field (same as original)
                $render_field = function($field) {
                    $key = esc_attr($field['key']);
                    $label = esc_html($field['label']);
                    $type = isset($field['type']) ? $field['type'] : 'text';
                    $required = !empty($field['required']);
                    $visible_locations = isset($field['visible_locations']) && is_array($field['visible_locations']) ? $field['visible_locations'] : array();
                    $placeholder = isset($field['placeholder']) ? esc_attr($field['placeholder']) : '';
                    // If this field is the client address and it is conditional to specific locations,
                    // ensure it is treated as required when shown for those locations.
                    if ($key === 'client_address' && !empty($visible_locations)) {
                        $required = true;
                    }
                    $req = $required ? ' required' : '';
                    $reqMark = $required ? ' <span class="required">*</span>' : '';
                    $data_vis = '';
                    $data_req_attr = $required ? ' data-original-required="1"' : '';
                    if (!empty($visible_locations)) {
                        $data_vis = ' data-visible-locations="' . esc_attr(join(',', $visible_locations)) . '"';
                    }
                    
                    echo '<div class="form-group"' . $data_vis . $data_req_attr . '>';
                    echo '<label for="' . $key . '">' . $label . $reqMark . '</label>';
                    
                    if ($type === 'textarea') {
                        echo '<textarea id="' . $key . '" name="' . $key . '" rows="3" placeholder="' . $placeholder . '"' . $req . '></textarea>';
                    } elseif ($type === 'select' && !empty($field['options']) && is_array($field['options'])) {
                        echo '<select id="' . $key . '" name="' . $key . '"' . $req . '>';
                        echo '<option value="">' . esc_html__('Sélectionner', 'ts-appointment') . '</option>';
                        foreach ($field['options'] as $opt) {
                            echo '<option value="' . esc_attr($opt) . '">' . esc_html($opt) . '</option>';
                        }
                        echo '</select>';
                    } elseif ($type === 'checkbox') {
                        echo '<label class="checkbox"><input type="checkbox" id="' . $key . '" name="' . $key . '" value="1"' . $req . '> ' . $label . '</label>';
                    } else {
                        $html_type = in_array($type, array('text','email','tel','number','date','time')) ? $type : 'text';
                        echo '<input type="' . esc_attr($html_type) . '" id="' . $key . '" name="' . $key . '" placeholder="' . $placeholder . '"' . $req . '>';
                    }
                    
                    echo '</div>';
                };
                
                // Render fields in two-column rows when possible
                $buffer = array();
                foreach ($form_schema as $field) {
                    $buffer[] = $field;
                    if (count($buffer) === 2) {
                        echo '<div class="form-row">';
                        foreach ($buffer as $bf) { 
                            $render_field($bf);
                        }
                        echo '</div>';
                        $buffer = array();
                    }
                }
                
                // Render remaining fields
                if (!empty($buffer)) {
                    foreach ($buffer as $bf) {
                        $render_field($bf);
                    }
                }
                ?>
                </div>

                <!-- Turnstile widget and Submit -->
                <div class="form-actions">
                    <?php if (get_option('ts_appointment_turnstile_enabled') && get_option('ts_appointment_turnstile_site_key') && get_option('ts_appointment_turnstile_secret_key')): ?>
                        <div class="form-group" style="width: 100%;">
                            <div id="ts-turnstile" class="ts-turnstile-widget" data-sitekey="<?php echo esc_attr(get_option('ts_appointment_turnstile_site_key')); ?>" style="margin-bottom: var(--spacing-md);"></div>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?php echo esc_html__('Réserver', 'ts-appointment'); ?></button>
                </div>

                <div class="form-message" id="form-message"></div>
            </form>
        </div>
    </div>
</div>

<style>
:root {
    --color-primary: <?php echo esc_attr(get_option('ts_appointment_color_primary', '#007cba')); ?>;
    --color-secondary: <?php echo esc_attr(get_option('ts_appointment_color_secondary', '#f0f0f0')); ?>;
}
</style>
