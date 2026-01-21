# TS Appointment - Plugin WordPress de Réservation de Rendez-vous

## 📋 Présentation

**TS Appointment** est un plugin WordPress complet et professionnel pour la gestion des rendez-vous clients, avec synchronisation en temps réel avec Google Agenda. C'est une alternative puissante à Calendly, entièrement personnalisable et intégrée à votre site WordPress.

### ✨ Caractéristiques principales

- 📅 **Système de réservation complet** - Interface intuitive et responsive
- 🔄 **Synchronisation Google Calendar** - Synchronisation en temps réel automatique
- 📍 **Lieux de rendez-vous configurables** - Personnalisez vos lieux de rendez-vous (bureau, distance, domicile client, etc.)
- 💰 **Prix par lieu** - Définissez un prix différent pour chaque lieu
- 💱 **Configuration monétaire** - Symbole de devise et position personnalisables
- 📱 **100% Responsive** - Optimisé pour mobile, tablette et desktop
- 🎨 **Entièrement customizable** - Couleurs, messages, paramètres, formulaire
- 📧 **Système d'emails** - Confirmations et notifications automatiques
- ⏰ **Gestion des créneaux** - Créneaux disponibles configurable par service
- 🛡️ **Sécurisé** - Nonces, validation des données, permissions WordPress
- 🚀 **API REST** - Pour intégrations tierces
- 👨‍💼 **Dashboard complet** - Gestion admin intuitive

## 🚀 Installation

### Prérequis
- WordPress 5.0+
- PHP 7.2+
- MySQL 5.5+

### Installation manuelle

1. Téléchargez le fichier ZIP du plugin
2. Allez à **Tableau de bord → Extensions → Ajouter**
3. Cliquez sur **Importer une extension**
4. Choisissez le fichier ZIP et cliquez sur **Installer**
5. Activez le plugin
6. Les tables de base de données seront créées automatiquement

### Désinstallation

⚠️ **Attention**: La suppression du plugin entraînera la suppression définitive de toutes les données :
- Toutes les tables de la base de données (rendez-vous, services, créneaux, etc.)
- Tous les paramètres et configurations
- Les tokens Google Calendar

Cette action est **irréversible**. Assurez-vous d'avoir sauvegardé vos données si nécessaire avant de supprimer le plugin.

## ⚙️ Configuration

### Accès au panneau d'administration

Après l'activation, un nouveau menu **Rendez-vous** apparaît dans le menu WordPress.

### Configuration initiale

1. Allez à **Rendez-vous → Paramètres**
2. Remplissez les informations de votre entreprise :
   - Nom
   - Email
   - Téléphone
   - Adresse
3. Configurez les paramètres des rendez-vous :
   - Fuseau horaire
   - Nombre de jours max à l'avance
   - Délai minimum entre rendez-vous
    - Formats de date et d'heure
4. Personnalisez les couleurs

### Configuration Google Calendar (optionnel)

Pour synchroniser automatiquement vos rendez-vous avec Google Agenda :

Configuration simplifiée :
   - Allez à **Rendez-vous → Paramètres → Google Calendar**
   - Cochez "Activer la synchronisation"
   - Cliquez sur "Lier mon compte Google" et autorisez l’accès à votre Google Agenda
   - Saisissez l'ID de votre calendrier si nécessaire

Remarque: des identifiants OAuth (Client ID et Client Secret) sont requis et doivent être créés dans Google Cloud Console.

#### Créer Client ID et Client Secret (Google OAuth)

- Ouvrir Google Cloud Console: https://console.cloud.google.com/
- Créer un projet (ou utiliser un projet existant).
- Activer l’écran de consentement OAuth: "APIs & Services" → "OAuth consent screen".
    - Type d’utilisateur: "External" (si des comptes Google externes) ou "Internal" (organisation).
    - Ajouter le scope: https://www.googleapis.com/auth/calendar
    - Enregistrer et publier (si nécessaire).
- Créer les identifiants OAuth: "APIs & Services" → "Credentials" → "Create Credentials" → "OAuth client ID".
    - Application type: "Web application".
    - Authorized redirect URIs: ajouter l’URL suivante (adapter votre domaine):
        - https://votresite.com/wp-admin/admin.php?page=ts-appointment-settings&action=google_callback
    - (Optionnel) Authorized JavaScript origins: https://votresite.com
- Copier le "Client ID" et le "Client Secret" générés.
- Dans WordPress: Rendez-vous → Paramètres → Google Calendar, coller le Client ID et le Client Secret, puis enregistrer.

### Protection Cloudflare Turnstile (optionnel)

- Créez une paire de clés Turnstile (site key + secret key) sur le tableau de bord Cloudflare
- Dans WordPress : Rendez-vous → Paramètres → Sécurité, cochez "Activer Cloudflare Turnstile" et renseignez les clés
- Le widget s'affiche alors sur le formulaire public et chaque réservation est validée côté serveur avant création

## 📖 Utilisation

### Pour les clients

Utilisez le shortcode pour afficher le formulaire de réservation sur une page :

```php
[ts_appointment_form]
```

### Pour l'administrateur

- **Tableau de bord** : Vue d'ensemble des rendez-vous
- **Rendez-vous** : Liste complète, édition, confirmation, annulation
- **Paramètres** : Configuration du plugin

### Lieux et Formulaire personnalisés

- **Lieux**: menu Rendez-vous → Lieux. Ajoutez des lieux (ex: À distance, Au domicile du client, Venir à notre adresse) et indiquez si l'adresse de l'entreprise ou celle du client doit être affichée/demandée.
- **Formulaire**: menu Rendez-vous → Formulaire. Ajoutez/supprimez des champs, types (text, email, tel, number, date, time, textarea, select, checkbox), obligatoire ou non — via l'UI.
- Les champs supplémentaires sont enregistrés avec la réservation et visibles dans les notes.
- **Créneaux**: menu Rendez-vous → Créneaux. Planifiez les créneaux par service (choix de jours multiples, heure de début/fin configurables, durée en minutes, actif).

### Paramètres monétaires

- **Symbole de devise**: configurez le symbole monétaire (€, $, etc.) dans Paramètres
- **Position**: choisissez si le symbole s'affiche à gauche ou à droite du montant
- Les prix sont définis par lieu pour chaque service

### Formats Date / Heure

- **Format de date** : personnalisez l'affichage (ex: j/m/Y ou Y-m-d)
- **Format d'heure** : 24h (H:i) ou 12h (g:i A)
- **Période de réservation** : fixez le nombre de jours maximum à l'avance

### Créer des services

Avant qu'un client puisse réserver, créez au moins un service :
- Dans l'admin : menu **Rendez-vous → Services** pour ajouter/supprimer un service. Vous pouvez définir un prix différent par lieu (cocher "Actif" pour qu'il apparaisse côté client).
- Par code : voir la section Développement ci-dessous.

## 💻 Développement

### Créer un service par code

```php
$service_id = TS_Appointment_Database::insert_service(array(
    'name' => 'Consultation',
    'description' => 'Consultation professionnelle de 1 heure',
    'duration' => 60,
    'price' => wp_json_encode(array('on_site' => 50, 'remote' => 30)),
    'active' => 1,
));
```

### Ajouter des créneaux horaires

```php
// Lundi de 9h à 18h (jour_of_week: 1 = lundi, ... 7 = dimanche)
TS_Appointment_Database::insert_slot(array(
    'service_id' => $service_id,
    'day_of_week' => 1,
    'start_time' => '09:00',
    'end_time' => '18:00',
    'max_appointments' => 1,
    'active' => 1,
));
```

### API REST

#### Récupérer les services
```
GET /wp-json/ts-appointment/v1/services
```

#### Récupérer les créneaux disponibles
```
GET /wp-json/ts-appointment/v1/available-slots?service_id=1&date=2024-01-20
```

#### Réserver un rendez-vous
```
POST /wp-json/ts-appointment/v1/appointment/book
Content-Type: application/json

{
    "service_id": 1,
    "client_name": "Jean Dupont",
    "client_email": "jean@example.com",
    "client_phone": "+33612345678",
    "appointment_type": "on_site",
    "appointment_date": "2024-01-20",
    "appointment_time": "14:00",
    "client_address": "",
    "notes": "Mes notes"
}
```

### Hooks WordPress

#### Actions
```php
do_action('ts_appointment_before_book', $data);
do_action('ts_appointment_after_book', $appointment_id, $appointment);
do_action('ts_appointment_before_confirm', $appointment_id);
do_action('ts_appointment_after_confirm', $appointment_id);
do_action('ts_appointment_before_cancel', $appointment_id);
do_action('ts_appointment_after_cancel', $appointment_id);
```

#### Filtres
```php
apply_filters('ts_appointment_validation_rules', $rules);
apply_filters('ts_appointment_confirmation_email', $email_body, $appointment);
```

## 🗄️ Structure de la base de données

### Tables créées

- `wp_ts_appointment_services` - Services disponibles
- `wp_ts_appointment_slots` - Créneaux horaires
- `wp_ts_appointment_appointments` - Rendez-vous
- `wp_ts_appointment_settings` - Paramètres du plugin

## 📱 Responsive & Mobile

Le plugin est entièrement optimisé pour :
- ✅ Smartphones (320px et plus)
- ✅ Tablettes (768px et plus)
- ✅ Desktop (1024px et plus)
- ✅ Points de rupture adaptables via CSS

## 🔒 Sécurité

- 🛡️ Validation de tous les inputs
- 🛡️ Nonces pour tous les formulaires
- 🛡️ Vérifications de permissions WordPress
- 🛡️ Échappement des données de sortie
- 🛡️ Requêtes préparées (prepared statements)
- 🛡️ Protection CSRF

## 🐛 Dépannage

### Les créneaux ne s'affichent pas
- Vérifiez que le service est créé et actif
- Vérifiez qu'il y a des créneaux configurés pour ce jour
- Vérifiez la date sélectionnée

### Google Calendar ne synchronise pas
- Vérifiez que Google Calendar est activé dans les paramètres
- Vérifiez que le Client ID et Secret sont corrects
- Vérifiez que l'ID du calendrier est valide

### Les emails ne s'envoient pas
- Vérifiez que votre serveur peut envoyer des emails
- Vérifiez l'adresse email configurée
- Vérifiez les logs WordPress

## 📞 Support

Pour toute question ou signaler un bug, contactez : support@ts-appointment.local

## 📄 Licence

GPL-2.0-or-later

## 🙏 Crédits

Développé par TS Appointment Team

---

**Version actuelle:** 1.0.0
**Dernière mise à jour:** Janvier 2024
