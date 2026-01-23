# TS Appointment - Plugin WordPress de Réservation de Rendez-vous

## 📋 Présentation

**TS Appointment** est un plugin WordPress complet et professionnel pour la gestion des rendez-vous clients, avec synchronisation en temps réel avec Google Agenda. Entièrement personnalisable et intégrée à votre site WordPress.

### ✨ Caractéristiques principales

- 📅 **Système de réservation complet** - Interface intuitive et responsive avec révélation progressive
- 🔄 **Synchronisation Google Calendar** - Synchronisation bidirectionnelle en temps réel automatique
- 📍 **Lieux de rendez-vous configurables** - Personnalisez vos lieux avec champs spécifiques par localisation
- 💰 **Prix par lieu** - Définissez un prix différent pour chaque lieu avec devise personnalisable
- 💱 **Configuration monétaire** - Symbole de devise et position personnalisables (€, $, etc.)
- 📱 **100% Responsive** - Optimisé pour mobile, tablette et desktop avec design moderne
- 🎨 **Formulaire dynamique JSON** - Créez vos champs personnalisés sans code, stockage en JSON
- 📧 **Templates d'emails avancés** - Personnalisation complète avec placeholders et logique conditionnelle
- ⏰ **Gestion intelligente des créneaux** - Créneaux disponibles par service avec vérification de disponibilité
- 🛡️ **Protection anti-robot** - Cloudflare Turnstile intégré
- 🔐 **Sécurité renforcée** - Nonces, HMAC tokens, validation stricte, permissions WordPress
- 🚀 **API REST** - Endpoints complets pour intégrations tierces
- 👨‍💼 **Dashboard admin complet** - Gestion, édition, logs d'emails, statistiques
- ✅ **Confirmation d'annulation** - Page de confirmation professionnelle avant annulation
- 🎯 **Architecture moderne** - Code optimisé, pas de champs hardcodés, extensible

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

#### Configuration des lieux
- **Menu**: Rendez-vous → Lieux
- Ajoutez des lieux personnalisés (ex: À distance, Au domicile du client, À notre bureau)
- Pour chaque lieu, configurez:
  - **Label** : nom affiché au client
  - **Clé** : identifiant unique (ex: `remote`, `home`, `office`)
  - **Prix** : montant spécifique à ce lieu
  - **Affichage adresse** : adresse de l'entreprise ou du client
  - **Champs supplémentaires** : ajoutez des champs spécifiques à chaque lieu (ex: lien Zoom pour "À distance")

#### Formulaire dynamique JSON
- **Menu**: Rendez-vous → Formulaire
- **Architecture moderne** : tous les champs sont stockés en JSON dans la colonne `client_data`
- **Pas de champs hardcodés** : ajoutez/supprimez des champs via l'interface sans modifier le code
- **Types de champs disponibles** :
  - `text` - Texte simple
  - `email` - Email avec validation
  - `tel` - Téléphone
  - `number` - Numérique
  - `date` - Sélecteur de date
  - `time` - Sélecteur d'heure
  - `textarea` - Texte multiligne
  - `select` - Liste déroulante (options séparées par |)
  - `checkbox` - Case à cocher
- **Configuration par champ** :
  - Clé unique (ex: `client_name`, `client_email`)
  - Label affiché
  - Placeholder
  - Obligatoire ou optionnel
- **Champs système recommandés** : `client_name`, `client_email`, `client_phone` pour compatibilité emails
- Les données sont automatiquement disponibles dans les templates d'emails via placeholders `{client_name}`, `{client_email}`, etc.

#### Migration automatique
- Si vous aviez des champs hardcodés dans une version antérieure, la migration s'effectue automatiquement
- Les données existantes sont préservées et converties en JSON lors de l'activation du plugin

### Créneaux horaires

- **Menu**: Rendez-vous → Créneaux
- Planifiez les disponibilités par service
- **Configuration** :
  - Jour de la semaine (1-7, lundi-dimanche)
  - Heure de début et fin
  - Durée du créneau en minutes
  - Nombre maximum de rendez-vous simultanés
  - Actif/Inactif
- Les créneaux sont vérifiés en temps réel pour éviter les doubles réservations
- Synchronisation automatique avec Google Calendar pour bloquer les créneaux réservés

### Templates d'emails personnalisables

- **Menu**: Rendez-vous → Emails
- 4 templates configurables :
  - **Email client - nouvelle demande** : envoyé au client après réservation (statut pending)
  - **Email client - confirmation** : envoyé quand l'admin confirme le rendez-vous
  - **Email admin - nouvelle demande** : notification admin pour nouvelle réservation
  - **Email client - annulation** : envoyé quand un rendez-vous est annulé

#### Placeholders disponibles
Tous les champs de votre formulaire JSON sont automatiquement disponibles :
- `{client_name}`, `{client_email}`, `{client_phone}` (champs système)
- `{nom_du_champ}` pour tout champ personnalisé
- `{service_name}`, `{appointment_date}`, `{appointment_time}`
- `{location}`, `{business_name}`, `{business_address}`
- `{appointment_id}`, `{cancel_url}`, `{cancel_button}`
- `{reason}` (pour annulation)

#### Logique conditionnelle
```
{if location==remote}Votre rendez-vous aura lieu en visioconférence{else}Rendez-vous en personne{endif}
```

### Annulation avec confirmation

- Liens d'annulation sécurisés envoyés dans les emails (tokens HMAC)
- Page de confirmation professionnelle et responsive avant annulation
- Affichage des détails du rendez-vous
- Deux boutons : Retour ou Confirmer l'annulation
- Design moderne avec gradient et optimisation mobile
- Tokens valides jusqu'à la date du rendez-vous

### Édition des rendez-vous

- **Menu**: Rendez-vous → Liste des rendez-vous
- Bouton "Modifier" sur chaque rendez-vous
- Formulaire d'édition dynamique basé sur le schéma JSON
- Modification de tous les champs client stockés en JSON
- Mise à jour du statut, date, heure, type de rendez-vous
- Synchronisation automatique avec Google Calendar en cas de modification

### Logs d'emails

- **Menu**: Rendez-vous → Email Logs
- Historique complet de tous les emails envoyés
- Détails : date, type, destinataire, sujet, statut
- Actions disponibles :
  - **Voir** : afficher le contenu complet de l'email (sujet, body HTML, contexte)
  - **Edit Appoint.** : modifier le rendez-vous associé
  - **Renvoyer** : renvoyer l'email en cas d'échec
- Utile pour déboguer les problèmes d'envoi d'emails

### Paramètres monétaires

- **Symbole de devise**: configurez le symbole monétaire (€, $, £, etc.) dans Paramètres
- **Position**: choisissez si le symbole s'affiche à gauche ou à droite du montant
- Les prix sont définis par lieu pour chaque service

### Formats Date / Heure

- **Format de date** : personnalisez l'affichage (ex: j/m/Y ou Y-m-d)
- **Format d'heure** : 24h (H:i) ou 12h (g:i A)
- **Période de réservation** : fixez le nombre de jours maximum à l'avance

### Créer des services

Avant qu'un client puisse réserver, créez au moins un service :
- Dans l'admin : menu **Rendez-vous → Services** pour ajouter/supprimer un service
- Définissez un prix différent par lieu
- Cochez "Actif" pour qu'il apparaisse côté client
- Par code : voir la section Développement ci-dessous

## 🎨 Interface utilisateur

### Révélation progressive (Progressive Reveal)
Le formulaire de réservation utilise une interface moderne avec révélation progressive :
1. **Sélection du service** → révèle les lieux disponibles
2. **Choix du lieu** → affiche les champs spécifiques au lieu + date
3. **Sélection de la date** → charge et affiche les créneaux disponibles
4. **Choix du créneau** → révèle les informations client
5. **Remplissage du formulaire** → affiche le prix et le bouton de réservation

### Optimisations UX
- **Auto-scroll** : défilement automatique vers les nouveaux champs révélés
- **Focus automatique** : premier champ focusé automatiquement
- **Mobile-first** : design adaptatif avec breakpoints optimisés
- **Chargement dynamique** : les créneaux sont chargés via AJAX
- **Feedback visuel** : messages de succès/erreur clairs
- **Validation en temps réel** : vérification des champs avant soumission

## 💻 Développement

### Créer un service par code

```php
$service_id = TS_Appointment_Database::insert_service(array(
    'name' => 'Consultation',
    'description' => 'Consultation professionnelle de 1 heure',
    'duration' => 60,
    'price' => wp_json_encode(array('on_site' => 50, 'remote' => 30, 'home' => 80)),
    'active' => 1,
));
```

### Accéder aux données client (JSON)

Toutes les données client sont stockées dans la colonne `client_data` au format JSON. Utilisez le helper :

```php
// Récupérer une valeur client
$client_name = TS_Appointment_Email::get_client_value($appointment, 'client_name');
$client_email = TS_Appointment_Email::get_client_value($appointment, 'client_email');
$custom_field = TS_Appointment_Email::get_client_value($appointment, 'ma_cle_personnalisee');

// Le helper vérifie d'abord le JSON client_data, puis les colonnes directes (rétrocompatibilité)
```

### Ajouter des champs au formulaire par code

```php
// Récupérer le schéma actuel
$form_schema = json_decode(get_option('ts_appointment_form_schema'), true);

// Ajouter un champ
$form_schema[] = array(
    'key' => 'company',
    'label' => 'Entreprise',
    'type' => 'text',
    'placeholder' => 'Nom de votre entreprise',
    'required' => false
);

// Sauvegarder
update_option('ts_appointment_form_schema', wp_json_encode($form_schema));
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
X-WP-Nonce: <nonce>

{
    "service_id": 1,
    "appointment_type": "on_site",
    "appointment_date": "2026-01-25",
    "appointment_time": "14:00",
    "client_name": "Jean Dupont",
    "client_email": "jean@example.com",
    "client_phone": "+33612345678",
    "extra": {
        "company": "Ma Société SARL",
        "custom_field": "Valeur personnalisée"
    },
    "turnstile_token": "token_cloudflare" // Si Turnstile activé
}
```

**Note** : Tous les champs définis dans le formulaire JSON peuvent être envoyés soit directement, soit dans l'objet `extra`. Ils seront automatiquement stockés dans `client_data`.

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

- `wp_ts_appointment_services` - Services disponibles (nom, description, durée, prix JSON par lieu)
- `wp_ts_appointment_slots` - Créneaux horaires (service_id, jour, heure début/fin, max rendez-vous)
- `wp_ts_appointment_appointments` - Rendez-vous (service_id, date, heure, type, statut, **client_data JSON**, google_calendar_id)
- `wp_ts_appointment_settings` - Paramètres du plugin (paires clé/valeur)
- `wp_ts_appointment_email_logs` - Logs des emails envoyés (type, destinataire, statut, contexte)

### Architecture moderne - client_data JSON

**Important** : Les données client ne sont plus stockées dans des colonnes séparées hardcodées. Tout est dans `client_data` (LONGTEXT JSON) :

```json
{
  "client_name": "Jean Dupont",
  "client_email": "jean@example.com",
  "client_phone": "+33612345678",
  "client_address": "123 rue Example",
  "notes": "Remarques",
  "company": "Ma Société",
  "custom_field_1": "valeur personnalisée"
}
```

**Avantages** :
- ✅ Ajout de champs sans migration de base de données
- ✅ Flexibilité totale pour personnaliser le formulaire
- ✅ Pas de limite sur le nombre de champs
- ✅ Migration automatique depuis l'ancien format

### Migration automatique

Si vous mettez à jour depuis une version antérieure avec des colonnes `client_name`, `client_email`, etc. :
1. Les données existantes sont copiées dans `client_data` JSON
2. Les anciennes colonnes sont supprimées
3. La compatibilité descendante est assurée via le helper `get_client_value()`

## 📱 Responsive & Mobile

Le plugin est entièrement optimisé pour :
- ✅ Smartphones (320px et plus)
- ✅ Tablettes (768px et plus)
- ✅ Desktop (1024px et plus)
- ✅ Points de rupture adaptables via CSS

## 🔒 Sécurité

- 🛡️ **Validation stricte** de tous les inputs (sanitize, validate)
- 🛡️ **Nonces WordPress** pour tous les formulaires admin
- 🛡️ **HMAC tokens** pour les liens d'annulation (sécurisés, expirables)
- 🛡️ **Cloudflare Turnstile** - Protection anti-robot avec vérification serveur
- 🛡️ **Permissions WordPress** - Vérifications de capacités (manage_options)
- 🛡️ **Échappement des sorties** - esc_html, esc_attr, esc_url, wp_kses
- 🛡️ **Requêtes préparées** - Protection SQL injection (wpdb->prepare)
- 🛡️ **Protection CSRF** - Tokens vérifiés côté serveur
- 🛡️ **Rate limiting** - Protection Turnstile contre spam de formulaires
- 🛡️ **Validation email** - Vérification format et domaine

## 🐛 Dépannage

### Les créneaux ne s'affichent pas
- ✅ Vérifiez que le service est créé et actif (Rendez-vous → Services)
- ✅ Vérifiez qu'il y a des créneaux configurés pour ce service et ce jour (Rendez-vous → Créneaux)
- ✅ Vérifiez que la date sélectionnée est dans la période autorisée (paramètre "jours max à l'avance")
- ✅ Vérifiez la console navigateur pour les erreurs AJAX
- ✅ Désactivez temporairement le cache WordPress/serveur

### Google Calendar ne synchronise pas
- ✅ Vérifiez que Google Calendar est activé dans Rendez-vous → Paramètres → Google Calendar
- ✅ Vérifiez que le Client ID et Secret sont corrects et correspondent à votre projet Google Cloud
- ✅ Vérifiez que l'URL de redirection est bien configurée dans Google Cloud Console
- ✅ Vérifiez que l'API Google Calendar est activée dans votre projet
- ✅ Relancez l'autorisation Google (bouton "Lier mon compte Google")
- ✅ Vérifiez l'ID du calendrier (calendrier principal = "primary")
- ✅ Consultez les logs WordPress pour les erreurs d'API

### Les emails ne s'envoient pas
- ✅ Vérifiez que votre serveur peut envoyer des emails (testez avec un plugin comme WP Mail SMTP)
- ✅ Vérifiez l'adresse email configurée dans Paramètres → Business
- ✅ Vérifiez les logs d'emails (Rendez-vous → Email Logs) pour voir les erreurs
- ✅ Si vous utilisez Mailgun, vérifiez les credentials dans le code
- ✅ Vérifiez les templates d'emails (Rendez-vous → Emails)
- ✅ Assurez-vous que les placeholders sont correctement orthographiés

### Cloudflare Turnstile ne fonctionne pas
- ✅ Vérifiez que Turnstile est activé dans Rendez-vous → Paramètres → Sécurité
- ✅ Vérifiez que la Site Key et Secret Key sont correctes
- ✅ Vérifiez que le domaine est autorisé dans les paramètres Cloudflare
- ✅ Vérifiez la console navigateur pour les erreurs JavaScript
- ✅ Testez en mode "visible" plutôt que "invisible" pour déboguer

### Les champs personnalisés ne s'affichent pas
- ✅ Vérifiez le schéma JSON dans Rendez-vous → Formulaire
- ✅ Assurez-vous que chaque champ a une clé unique
- ✅ Vérifiez qu'il n'y a pas d'erreurs JSON (utilisez un validateur)
- ✅ Videz le cache WordPress si actif

### Erreur "Erreur lors de la création du rendez-vous"
- ✅ Activez le mode debug WordPress (WP_DEBUG) pour voir l'erreur exacte
- ✅ Vérifiez que tous les champs obligatoires du formulaire sont remplis
- ✅ Vérifiez que l'email est valide
- ✅ Vérifiez que la date/heure est dans le futur
- ✅ Vérifiez les logs de base de données pour les erreurs SQL

## 📞 Support

Pour toute question ou signaler un bug, contactez : support@techno-solution.ca

## 📄 Licence

GPL-2.0-or-later

## 🙏 Crédits

Développé par TS Appointment Team

---

**Version actuelle:** 1.0.0  
**Dernière mise à jour:** Janvier 2026

### 🆕 Nouveautés version 2.0

#### Architecture moderne
- ✅ Migration vers stockage JSON des données client (colonne `client_data`)
- ✅ Suppression complète des champs hardcodés (client_name, client_email, etc.)
- ✅ Formulaire 100% dynamique basé sur schéma JSON personnalisable
- ✅ Migration automatique des données existantes

#### Interface améliorée
- ✅ Révélation progressive (progressive reveal) du formulaire de réservation
- ✅ Auto-scroll et focus automatique pour meilleure UX
- ✅ Design responsive mobile-first optimisé
- ✅ Page de confirmation d'annulation professionnelle avec design moderne

#### Nouvelles fonctionnalités
- ✅ Édition complète des rendez-vous avec formulaire dynamique
- ✅ Système de logs d'emails complet avec visualisation et renvoi
- ✅ Templates d'emails avec logique conditionnelle (`{if}...{else}...{endif}`)
- ✅ Champs spécifiques par lieu de rendez-vous
- ✅ Protection anti-robot Cloudflare Turnstile intégrée
- ✅ Tokens HMAC sécurisés pour liens d'annulation

#### Optimisations
- ✅ Code optimisé sans doublons ni références hardcodées
- ✅ Helper `get_client_value()` pour compatibilité ascendante/descendante
- ✅ Validation stricte côté serveur et client
- ✅ Performance améliorée avec chargement AJAX des créneaux

---
