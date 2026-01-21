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
            if (!confirm('Êtes-vous sûr?')) return;
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
                        alert('Rendez-vous confirmé');
                        location.reload();
                    } else {
                        alert('Erreur lors de la confirmation');
                    }
                },
                error: function() {
                    alert('Erreur serveur');
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
                        alert('Rendez-vous supprimé');
                        location.reload();
                    } else {
                        alert('Erreur lors de la suppression');
                    }
                },
                error: function() {
                    alert('Erreur serveur');
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
                    alert('Erreur lors du chargement');
                }
            });
        }

        function displayAppointmentModal(appointment) {
            // Get location labels from localized data
            const locationLabels = tsAppointment.locationLabels || {};

            const statusLabels = {
                'pending': 'En attente',
                'confirmed': 'Confirmé',
                'completed': 'Complété',
                'cancelled': 'Annulé'
            };

            const dateTime = new Date(appointment.appointment_date + 'T' + appointment.appointment_time);
            const formattedDate = dateTime.toLocaleDateString('fr-FR');
            const formattedTime = dateTime.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

            const modal = `
                <div style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.7);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                " class="appointment-modal-bg">
                    <div style="
                        background: white;
                        padding: 30px;
                        border-radius: 8px;
                        max-width: 500px;
                        width: 90%;
                    " class="appointment-modal">
                        <h2 style="margin-top: 0; margin-bottom: 20px; color: #007cba;">Détails du rendez-vous</h2>
                        
                        <div style="margin-bottom: 15px;">
                            <strong>Nom:</strong> ${appointment.client_name}
                        </div>
                        <div style="margin-bottom: 15px;">
                            <strong>Email:</strong> ${appointment.client_email}
                        </div>
                        <div style="margin-bottom: 15px;">
                            <strong>Téléphone:</strong> ${appointment.client_phone}
                        </div>
                        <div style="margin-bottom: 15px;">
                            <strong>Date:</strong> ${formattedDate} à ${formattedTime}
                        </div>
                        <div style="margin-bottom: 15px;">
                            <strong>Type:</strong> ${locationLabels[appointment.appointment_type] || appointment.appointment_type}
                        </div>
                        ${appointment.client_address ? `
                        <div style="margin-bottom: 15px;">
                            <strong>Adresse:</strong> ${appointment.client_address}
                        </div>
                        ` : ''}
                        ${appointment.notes ? `
                        <div style="margin-bottom: 15px;">
                            <strong>Notes:</strong> ${appointment.notes}
                        </div>
                        ` : ''}
                        <div style="margin-bottom: 15px;">
                            <strong>Statut:</strong> <span style="
                                display: inline-block;
                                padding: 4px 8px;
                                border-radius: 4px;
                                background: #f0f0f0;
                                color: #333;
                            ">${statusLabels[appointment.status] || appointment.status}</span>
                        </div>
                        
                        <div style="margin-top: 25px; text-align: right;">
                            <button class="close-modal" style="
                                padding: 8px 16px;
                                background: #007cba;
                                color: white;
                                border: none;
                                border-radius: 4px;
                                cursor: pointer;
                                font-weight: 600;
                            ">Fermer</button>
                        </div>
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
