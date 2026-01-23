/**
 * JavaScript Admin
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Confirmer un rendez-vous
        $(document).on('click', '.confirm-appointment', function(e) {
            e.preventDefault();
            const appointmentId = $(this).data('id');
            confirmAppointment(appointmentId);
        });

        // Supprimer un rendez-vous
        $(document).on('click', '.delete-appointment', function(e) {
            e.preventDefault();
            var confirmText = (tsAppointment && tsAppointment.i18n && tsAppointment.i18n['Êtes-vous sûr?']) ? tsAppointment.i18n['Êtes-vous sûr?'] : 'Êtes-vous sûr?';
            if (!confirm(confirmText)) return;
            const appointmentId = $(this).data('id');
            deleteAppointment(appointmentId);
        });

        // Voir les détails
        $(document).on('click', '.view-appointment', function(e) {
            e.preventDefault();
            const appointmentId = $(this).data('id');
            viewAppointment(appointmentId);
        });

        function confirmAppointment(appointmentId) {
            const restUrl = tsAppointment.restUrl;
            const restNonce = tsAppointment.restNonce || '';

            $.ajax({
                url: restUrl + 'appointment/' + appointmentId + '/confirm',
                type: 'POST',
                beforeSend: function(xhr) {
                    if (restNonce) {
                        xhr.setRequestHeader('X-WP-Nonce', restNonce);
                    }
                },
                success: function(response) {
                    if (response.success) {
                        var ok = (tsAppointment && tsAppointment.i18n && tsAppointment.i18n['Rendez-vous confirmé']) ? tsAppointment.i18n['Rendez-vous confirmé'] : 'Rendez-vous confirmé';
                        alert(ok);
                        location.reload();
                    } else {
                        var err = (tsAppointment && tsAppointment.i18n && tsAppointment.i18n['Erreur lors de la confirmation']) ? tsAppointment.i18n['Erreur lors de la confirmation'] : 'Erreur lors de la confirmation';
                        alert(err);
                    }
                },
                error: function() {
                    var srv = (tsAppointment && tsAppointment.i18n && tsAppointment.i18n['Erreur serveur']) ? tsAppointment.i18n['Erreur serveur'] : 'Erreur serveur';
                    alert(srv);
                }
            });
        }

        function deleteAppointment(appointmentId) {
            const restUrl = tsAppointment.restUrl;
            const restNonce = tsAppointment.restNonce || '';

            $.ajax({
                url: restUrl + 'appointment/' + appointmentId + '/cancel',
                type: 'POST',
                data: JSON.stringify({ reason: 'Supprimé par l\'administrateur' }),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    if (restNonce) {
                        xhr.setRequestHeader('X-WP-Nonce', restNonce);
                    }
                },
                success: function(response) {
                    if (response.success) {
                        var del = (tsAppointment && tsAppointment.i18n && tsAppointment.i18n['Rendez-vous supprimé']) ? tsAppointment.i18n['Rendez-vous supprimé'] : 'Rendez-vous supprimé';
                        alert(del);
                        location.reload();
                    } else {
                        var derr = (tsAppointment && tsAppointment.i18n && tsAppointment.i18n['Erreur lors de la suppression']) ? tsAppointment.i18n['Erreur lors de la suppression'] : 'Erreur lors de la suppression';
                        alert(derr);
                    }
                },
                error: function() {
                    var srv = (tsAppointment && tsAppointment.i18n && tsAppointment.i18n['Erreur serveur']) ? tsAppointment.i18n['Erreur serveur'] : 'Erreur serveur';
                    alert(srv);
                }
            });
        }

        function viewAppointment(appointmentId) {
            const restUrl = tsAppointment.restUrl;
            const restNonce = tsAppointment.restNonce || '';

            $.ajax({
                url: restUrl + 'appointment/' + appointmentId,
                type: 'GET',
                beforeSend: function(xhr) {
                    if (restNonce) {
                        xhr.setRequestHeader('X-WP-Nonce', restNonce);
                    }
                },
                success: function(response) {
                    displayAppointmentModal(response);
                },
                error: function() {
                    var loadErr = (tsAppointment && tsAppointment.i18n && tsAppointment.i18n['Erreur lors du chargement']) ? tsAppointment.i18n['Erreur lors du chargement'] : 'Erreur lors du chargement';
                    alert(loadErr);
                }
            });
        }

        function __(text) {
            try {
                return (tsAppointment && tsAppointment.i18n && tsAppointment.i18n[text]) ? tsAppointment.i18n[text] : text;
            } catch (e) {
                return text;
            }
        }

        function displayAppointmentModal(appointment) {
            const locationLabels = tsAppointment.locationLabels || {};

            const statusLabels = {
                'pending': __('En attente'),
                'confirmed': __('Confirmé'),
                'completed': __('Complété'),
                'cancelled': __('Annulé')
            };

            const dateTime = new Date((appointment.appointment_date || '') + 'T' + (appointment.appointment_time || ''));
            const formattedDate = isNaN(dateTime.getTime()) ? '' : dateTime.toLocaleDateString('fr-FR');
            const formattedTime = isNaN(dateTime.getTime()) ? '' : dateTime.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

            // Parse client_data JSON to get client info
            let clientData = {};
            if (appointment.client_data) {
                try {
                    clientData = typeof appointment.client_data === 'string' ? JSON.parse(appointment.client_data) : appointment.client_data;
                } catch (e) {
                    console.error('Failed to parse client_data:', e);
                }
            }

            const clientName = clientData.client_name || appointment.client_name || '';
            const clientEmail = clientData.client_email || appointment.client_email || '';
            const clientPhone = clientData.client_phone || appointment.client_phone || '';
            const clientAddress = clientData.client_address || appointment.client_address || '';
            const notes = clientData.notes || appointment.notes || '';

            const modal = `
                <div style="position: fixed;top: 0;left: 0;right: 0;bottom: 0;background: rgba(0,0,0,0.7);display: flex;align-items: center;justify-content: center;z-index: 10000;" class="appointment-modal-bg">
                    <div style="background: white;padding: 30px;border-radius: 8px;max-width: 500px;width: 90%;" class="appointment-modal">
                        <h2 style="margin-top: 0; margin-bottom: 20px; color: #007cba;">${__('Détails du rendez-vous')}</h2>

                        <div style="margin-bottom: 15px;"><strong>${__('Nom:')}</strong> ${clientName}</div>
                        <div style="margin-bottom: 15px;"><strong>${__('Email:')}</strong> ${clientEmail}</div>
                        <div style="margin-bottom: 15px;"><strong>${__('Téléphone:')}</strong> ${clientPhone}</div>
                        <div style="margin-bottom: 15px;"><strong>${__('Date:')}</strong> ${formattedDate} ${formattedTime ? 'à ' + formattedTime : ''}</div>
                        <div style="margin-bottom: 15px;"><strong>${__('Type:')}</strong> ${locationLabels[appointment.appointment_type] || appointment.appointment_type || ''}</div>
                        ${clientAddress ? `<div style="margin-bottom: 15px;"><strong>${__('Adresse:')}</strong> ${clientAddress}</div>` : ''}
                        ${notes ? `<div style="margin-bottom: 15px;"><strong>${__('Notes:')}</strong> ${notes}</div>` : ''}
                        <div style="margin-bottom: 15px;"><strong>${__('Statut:')}</strong> <span style="display: inline-block;padding: 4px 8px;border-radius: 4px;background: #f0f0f0;color: #333;">${statusLabels[appointment.status] || appointment.status || ''}</span></div>

                        <div style="margin-top: 25px; text-align: right;"><button class="close-modal" style="padding: 8px 16px;background: #007cba;color: white;border: none;border-radius: 4px;cursor: pointer;font-weight: 600;">${__('Fermer')}</button></div>
                    </div>
                </div>
            `;

            $('body').append(modal);

            $('.appointment-modal-bg').on('click', function(e) {
                if ($(e.target).closest('.appointment-modal').length === 0) {
                    $(this).remove();
                }
            });

            $('.close-modal').on('click', function() {
                $('.appointment-modal-bg').remove();
            });
        }
    });

})(jQuery);
