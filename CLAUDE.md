# CLAUDE.md — Contexte projet : Application événementielle QR Code

## Vue d'ensemble

Application web PHP/MySQL multi-événements pour la gestion d'invitations et le contrôle d'accès par QR Code. Hébergée sur un seul serveur. Interface backoffice + PWA scanner mobile.

---

## Stack technique

- **Backend** : PHP 8.2+, sans framework lourd (routeur maison MVC léger)
- **BDD** : MySQL 8.0+, PDO, requêtes préparées obligatoires
- **Frontend backoffice** : HTML/CSS/JS vanilla (pas de framework JS)
- **PWA scanner** : HTML/CSS/JS vanilla, jsQR pour la caméra, Web Audio API pour les sons
- **Emails** : PHPMailer + SMTP (Brevo ou Postmark)
- **QR Code** : chillerlan/php-qrcode (génération serveur)
- **Import/Export** : PhpSpreadsheet pour Excel/CSV
- **PDF export** : TCPDF ou DomPDF
- **Dépendances** : gérées via Composer

---

## Architecture

- Front controller unique : `public/index.php`
- Tout le code applicatif est dans `src/` (non exposé)
- Routeur : `src/Core/Router.php` → mappe URL vers `Controller::method`
- Pas de moteur de template tiers : PHP natif dans `views/`
- Sessions PHP sécurisées (pas de JWT)

---

## Rôles utilisateurs

| Rôle | Périmètre |
|---|---|
| `super_admin` | Tous les événements, gestion des utilisateurs, création événements |
| `admin` | Uniquement ses événements affectés (table `event_admins`) |
| `scanner` | Accès PWA scanner uniquement, pas de backoffice |

Un événement peut avoir **plusieurs admins** (table pivot `event_admins`).

---

## Schéma BDD — tables principales

```
users            → utilisateurs backoffice (super_admin / admin / scanner)
events           → événements (slug unique, options JSON, quota optionnel)
event_admins     → pivot événement ↔ admin (multi-admin par événement)
guests           → invités par événement (cycle de vie complet)
guest_responses  → réponses invité (participation + transport + extras JSON)
qr_tokens        → tokens QR signés HMAC (1 par invité × événement)
scan_logs        → historique exhaustif de chaque scan
import_batches   → traçabilité des imports CSV/Excel
audit_logs       → journal RGPD de toutes les actions admin
```

---

## Cycle de vie d'un invité (statuts)

```
pending → [admin valide] → approved → [invité répond oui] → confirmed → [scan J] → (présent)
        → [admin rejette] → rejected
        → [quota atteint] → waitlist → [place libérée] → approved
confirmed → [invité répond non] → declined
```

---

## Sécurité — règles non négociables

1. **Toutes les requêtes SQL** via PDO avec requêtes préparées, jamais de concaténation
2. **CSRF** : token sur chaque formulaire POST, validé par `CsrfMiddleware`
3. **Mots de passe** : `password_hash()` avec `PASSWORD_BCRYPT`, coût minimum 12
4. **QR Code tokens** : UUID v4 + HMAC-SHA256 avec clé secrète dans `.env`
5. **XSS** : tout affichage de données utilisateur via `htmlspecialchars()` — ne jamais `echo` directement
6. **Rate limiting** : inscription publique limitée à 5 soumissions/10 min par IP
7. **Headers HTTP** : X-Frame-Options, X-Content-Type-Options, CSP minimal
8. **Sessions** : `session_regenerate_id(true)` après login, cookie `httponly` + `samesite=strict`
9. **Fichiers uploadés** : validation MIME type + extension + taille, stockage hors `public/`
10. **Variables sensibles** : uniquement dans `.env`, jamais commitées

---

## QR Code — fonctionnement technique

```php
// Structure du token
$uuid      = bin2hex(random_bytes(16));
$payload   = $guestId . '|' . $eventId . '|' . $uuid;
$hmac      = hash_hmac('sha256', $payload, $_ENV['QR_SECRET_KEY']);
$token     = $payload . '.' . $hmac;

// Validation au scan
[$payloadReceived, $hmacReceived] = explode('.', $tokenScanned, 2);
$hmacExpected = hash_hmac('sha256', $payloadReceived, $_ENV['QR_SECRET_KEY']);
$valid = hash_equals($hmacExpected, $hmacReceived); // timing-safe
```

---

## PWA Scanner — comportements attendus

- **Succès** : fond vert plein écran + prénom/nom de l'invité + son cloche (Web Audio API)
- **Déjà scanné** : fond rouge + message "Déjà enregistré à HH:MM" + son buzzer
- **Token invalide** : fond rouge + message "QR Code invalide" + son buzzer
- **Mauvais événement** : fond rouge + message "QR Code invalide pour cet événement" + son buzzer
- **Mode hors-ligne** : scans mis en file (IndexedDB), sync automatique au retour réseau

Sons générés via Web Audio API (pas de fichiers audio), déclenchés uniquement après interaction utilisateur (tap "Démarrer").

---

## Conventions de code

### PHP
- PSR-12 (formatage)
- Typage strict : `declare(strict_types=1)` en tête de chaque fichier
- Types de retour explicites sur toutes les méthodes
- Nommage : `camelCase` méthodes, `PascalCase` classes, `snake_case` variables/BDD
- Pas de `die()` ou `exit()` dans les controllers — utiliser les exceptions
- Tous les `catch` doivent logger via `error_log()` ou le logger maison

### SQL
- Nommage tables : `snake_case` pluriel
- Toujours spécifier les colonnes dans les INSERT (pas de `INSERT INTO table VALUES(...)`)
- Index sur toutes les colonnes utilisées dans WHERE, JOIN, ORDER BY

### HTML/CSS
- Sémantique : `<main>`, `<nav>`, `<section>`, `<article>` correctement utilisés
- CSS : variables custom pour couleurs et espacements, pas de styles inline sauf exceptions JS
- JS : pas de `var`, utiliser `const`/`let`, pas de jQuery

---

## Fichiers de configuration importants

```
.env                  → variables sensibles (ne jamais committer)
config/app.php        → configuration générale
config/database.php   → connexion BDD (lit .env)
config/mail.php       → SMTP
config/security.php   → QR_SECRET_KEY, durée tokens, rate limits
```

### Variables .env requises
```env
APP_NAME="Mon Application"
APP_URL="https://votredomaine.com"
APP_ENV="production"   # development | production

DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="evenement_app"
DB_USER="db_user"
DB_PASS="db_password"

MAIL_HOST="smtp.brevo.com"
MAIL_PORT="587"
MAIL_USER="apikey"
MAIL_PASS="votre_clé_api"
MAIL_FROM="noreply@votredomaine.com"
MAIL_FROM_NAME="Mon Application"

QR_SECRET_KEY="une_clé_aléatoire_de_64_caractères_minimum"
SESSION_SECRET="une_autre_clé_aléatoire_pour_les_sessions"
```

---

## Structure des routes principales

```
GET  /                              → redirect vers /admin
GET  /login                         → AuthController::loginForm
POST /login                         → AuthController::login
GET  /logout                        → AuthController::logout

# Backoffice (auth requise)
GET  /admin                         → DashboardController::index
GET  /admin/events                  → EventController::list
POST /admin/events                  → EventController::create
GET  /admin/events/{id}             → EventController::show
POST /admin/events/{id}/admins      → EventAdminController::assign

GET  /admin/events/{id}/guests      → GuestController::list
POST /admin/guests/{id}/approve     → GuestController::approve
POST /admin/guests/{id}/reject      → GuestController::reject
POST /admin/events/{id}/import      → GuestImportController::import
GET  /admin/events/{id}/export      → GuestExportController::export

GET  /admin/events/{id}/stats       → StatsController::index

# Scanner (rôle scanner requis)
GET  /scanner/{event_id}            → ScannerController::index
POST /api/scan                      → ScanApiController::validate  (JSON)

# Public (sans auth)
GET  /inscription/{slug}            → RegistrationController::form
POST /inscription/{slug}            → RegistrationController::submit
GET  /reponse/{access_token}        → ResponseController::form
POST /reponse/{access_token}        → ResponseController::submit
GET  /invitation/{access_token}     → InvitationController::show
```

---

## Points d'attention fréquents

- **Ne jamais exposer** le `QR_SECRET_KEY` dans les logs ou les réponses JSON
- **La page scanner** `/scanner/{event_id}` doit filtrer les scans : un scanner affecté à l'événement A ne valide pas les QR de l'événement B
- **Import CSV** : toujours valider et désinfecter chaque ligne, générer un rapport d'erreurs ligne par ligne
- **Quota** : la vérification quota doit être dans une transaction SQL pour éviter les race conditions
- **QR Code image** : générée à la volée et servie directement (pas stockée sur disque sauf pour l'email)
- **Emails** : utiliser une file (table `email_queue` ou envoi synchrone selon volume) — ne jamais bloquer la réponse HTTP sur l'envoi email
- **RGPD** : les données invités doivent pouvoir être supprimées proprement (cascade BDD + audit log)

---

## Cron jobs

```bash
# Relances invités sans réponse (toutes les 6h)
0 */6 * * * php /chemin/vers/cron/send_reminders.php

# Nettoyage fichiers temporaires (quotidien à 3h)
0 3 * * * php /chemin/vers/cron/cleanup_storage.php
```

---

## Dépendances Composer

```json
{
    "require": {
        "php": ">=8.2",
        "phpmailer/phpmailer": "^6.9",
        "chillerlan/php-qrcode": "^5.0",
        "phpoffice/phpspreadsheet": "^2.0",
        "tecnickcom/tcpdf": "^6.7",
        "vlucas/phpdotenv": "^5.6"
    }
}
```
