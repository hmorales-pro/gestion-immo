# ImmoBooking

Alternative légère, auto-hébergée et open source à Calendly, pensée pour les agences immobilières.
Chaque bien a son propre lien public de prise de rendez-vous ; les visites impliquent l'agent, le
visiteur (acheteur / locataire) et le propriétaire.

**Stack :** Laravel 12 · PHP 8.2 à 8.5 (8.5 recommandé) · Filament 3 · Livewire 3 · TailwindCSS (+ `@tailwindcss/forms`) · SQLite (compatible PostgreSQL)

## Démarrage

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed       # agent de démo : agent@example.com / password
composer run dev                 # serveur + worker de file d'attente + vite
```

- Administration : `http://localhost:8000/admin`
- Créer un agent : `php artisan make:filament-user`

Les notifications sont mises en file d'attente (`QUEUE_CONNECTION=database`) : en production, lancez
`php artisan queue:work` (ou passez `QUEUE_CONNECTION=sync`).

## Parcours

1. **L'agent** crée un bien (`/admin/properties`) : titre, adresse, durée des créneaux, mode de réservation
   et coordonnées du propriétaire. Il génère ensuite les créneaux (ex. « samedi 10:00 → 12:00 », découpé
   selon la durée, répétable sur plusieurs semaines) et copie le lien public `/v/{property}`.
2. **Le visiteur** ouvre le lien (page mobile, sans compte), choisit un créneau libre et laisse ses
   coordonnées (nom, e-mail, téléphone, situation professionnelle). Une réservation `PENDING` est créée
   avec un jeton unique.
3. **Le propriétaire** reçoit un e-mail (et un SMS s'il a un téléphone) avec le lien `/approve/{token}` :
   il accepte (`CONFIRMED`, créneau réservé, confirmations à l'agent et au visiteur) ou refuse
   (`REJECTED`, l'agent et le visiteur sont prévenus, le créneau redevient libre).

4. **Le visiteur** garde un lien privé `/rdv/{token}` (dans chaque e-mail) pour ajouter la visite à son
   agenda, changer de créneau, annuler ou compléter ses informations.
5. **L'agent** peut, depuis l'admin : inviter des visiteurs à choisir un créneau (e-mail avec le lien du
   bien), approuver / refuser, déplacer, annuler une visite, et demander des informations au visiteur.

### Services autour de la visite (sans entretien)

- **Affiche et QR code** : pour chaque bien, une affiche A4 prête à imprimer et un QR code SVG
  (action « Affiche et QR code »).
- **Bouton pour le site de l'agence** : un code HTML à coller qui ouvre la page de réservation du bien.
- **Liste d'attente** : quand tout est complet, le visiteur laisse son e-mail. Il est prévenu une seule fois
  dès qu'un créneau se libère (annulation, refus, déplacement, nouveaux créneaux), puis son adresse est
  supprimée.
- **Bon de visite signé** : le visiteur signe sur le téléphone de l'agent (`/bon-de-visite/{id}`). Le bon est
  pré-rempli, horodaté, archivé et imprimable.
- **Avis de visite** : après la visite, la page « Mon rendez-vous » propose un avis en 30 secondes, transmis
  à l'agent et au propriétaire.
- **Suivi propriétaire** : une page privée (`/suivi/{token}`) avec les visites à venir, les visites passées
  et les avis. Les visiteurs y sont anonymisés (prénom et initiale).
- **Locataire en place** : facultatif. Il est prévenu des visites confirmées, déplacées ou annulées, sans les
  coordonnées du visiteur.

Aucun de ces services ne demande de tâche planifiée, d'API externe ni de données à mettre à jour.

Chaque participant ne reçoit que ce qui le concerne : l'invitation d'agenda ne liste que son destinataire,
le propriétaire ne reçoit pas le téléphone du visiteur, et les liens privés du visiteur ne sont envoyés
qu'au visiteur.

### Modes de réservation

| Mode | Qui valide ? |
| --- | --- |
| `OWNER_APPROVAL` | Le propriétaire, via le lien reçu par e-mail / SMS |
| `AGENT_APPROVAL` | L'agent, depuis « Demandes de visite » dans l'admin |
| `INSTANT` | Personne : la visite est confirmée immédiatement |

L'agent peut toujours approuver / refuser manuellement une demande en attente depuis l'admin.

## Règles métier

- Un agent ne voit que ses biens et les demandes associées (requêtes Filament filtrées + policies).
- Les identifiants de biens sont des ULID : les liens publics ne sont pas énumérables.
- Un créneau visible est futur, non réservé et sans demande en attente (une demande en attente « bloque »
  le créneau jusqu'à la décision).
- Un même visiteur (e-mail) ne peut pas avoir deux visites actives qui se chevauchent, tous biens confondus.
- Les créneaux d'un même bien ne peuvent pas se chevaucher (génération et saisie manuelle).
- Téléphones validés avec libphonenumber (`propaganistas/laravel-phone`, France par défaut, international
  accepté) et stockés au format E.164 ; e-mails validés en RFC strict.
- Page publique : honeypot anti-spam + 5 demandes / 10 min par IP. Page propriétaire : 30 requêtes / min.

## Agendas (Google, Apple, Outlook)

- **Invitations** : les e-mails de confirmation, de déplacement et d'annulation contiennent une invitation
  `.ics` (même identifiant d'événement, numéro de version incrémenté), donc l'événement est ajouté, mis à
  jour puis supprimé dans l'agenda du visiteur, du propriétaire et de l'agent. Ils proposent aussi des liens
  « Ajouter à Google Agenda / Outlook / Apple (.ics) ».
- **Abonnement de l'agent** : menu « Synchroniser mon agenda ». Ce lien d'abonnement privé
  (`/calendar/{token}.ics`) liste toutes ses visites dans Google Agenda, Apple Calendrier ou Outlook. On peut
  le régénérer. La synchro est en lecture seule : l'agenda affiche les visites mais ne les modifie pas.

## E-mails : Brevo

```dotenv
MAIL_MAILER=brevo
BREVO_API_KEY=xkeysib-...
MAIL_FROM_ADDRESS="visites@votre-agence.fr"   # adresse validée dans Brevo
```

Les e-mails partent par l'API transactionnelle Brevo (`symfony/brevo-mailer`). Chaque type d'e-mail a son
tag Brevo pour les statistiques : `visit-invitation`, `owner-approval`, `prospect-request-received`,
`booking-confirmed`, `booking-rejected`, `booking-rescheduled`, `booking-cancelled`,
`information-requested`, etc. La réponse revient à l'agent (`Reply-To`).

## SMS

`SMS_DRIVER=log` (défaut) écrit les SMS dans les logs. `brevo` (réutilise `BREVO_API_KEY`), `twilio` et
`vonage` envoient réellement. Pour un autre fournisseur, implémentez
`App\Services\Sms\SmsSender`.

## Code

| Dossier | Contenu |
| --- | --- |
| `app/Actions` | `CreateBooking`, `ApproveBooking`, `RejectBooking`, `RescheduleBooking`, `CancelBooking`, `RequestInformation`, `InviteProspects`, `GenerateSlots`… |
| `app/Services/Calendar` | Génération iCalendar (invitations et flux d'abonnement), liens Google / Outlook |
| `app/Livewire/ManageBooking.php` | Page visiteur `/rdv/{token}` |
| `app/Filament` | `PropertyResource` (+ créneaux et demandes en relation managers), `BookingResource`, widget de stats |
| `app/Livewire/PublicBooking.php` | Page publique `/v/{property}` |
| `app/Http/Controllers/OwnerApprovalController.php` | Page propriétaire `/approve/{token}` |
| `app/Notifications` | E-mails / SMS (propriétaire, agent, visiteur) |

## Hébergement mutualisé

ImmoBooking tourne sur un hébergement web classique (Hostinger, o2switch, OVH…) : PHP 8.2 à 8.5 (choisissez 8.5), MySQL ou
SQLite, **sans worker, sans cron obligatoire, sans Node.js sur le serveur**. Les fichiers CSS/JS compilés
sont versionnés dans `public/build`.

**Première installation** (en SSH, dans le dossier du site) :

```bash
git clone https://github.com/hmorales-pro/gestion-immo.git booking && cd booking
./deploy.sh --install     # crée .env à partir de .env.mutualise.example puis s'arrête
nano .env                 # APP_URL, base MySQL, clé Brevo, IMMOBOOKING_*…
./deploy.sh --install     # dépendances, clé, base, démo, caches
php artisan make:filament-user
```

Faites pointer la racine du (sous-)domaine vers le dossier `public/`. Si l'hébergeur ne le permet pas, le
`.htaccess` à la racine du projet redirige tout vers `public/`, et `.env`, `vendor/`, `storage/` restent
inaccessibles.

**Mise à jour** : `./deploy.sh`. Le script fait un `git pull`, met le site en maintenance quelques
secondes, installe les dépendances, lance les migrations, reconstruit les caches puis remet le site en
ligne, même si une étape échoue. Options utiles :

- `PHP_BIN=/usr/bin/php8.3 ./deploy.sh` si la commande `php` n'est pas en version 8.2 ou plus ;
- `COMPOSER_BIN="php composer.phar" ./deploy.sh` si Composer n'est pas installé ;
- `SKIP_GIT=1 ./deploy.sh` si les fichiers sont envoyés par FTP.

`QUEUE_CONNECTION=sync` envoie les e-mails immédiatement, sans worker.

## Site vitrine, démo et SEO (site officiel)

Avec `IMMOBOOKING_WEBSITE=true`, `/` affiche la page d'accueil, et le site a aussi des guides, des pages
légales, `sitemap.xml` et `robots.txt`. Avec `IMMOBOOKING_DEMO=true`, `/demo` ouvre une démo publique :

- **Réinitialisation** : un agent `demo@immobooking.test` / `demo` est recréé avec 3 biens, tout seul,
  toutes les 3 h (`IMMOBOOKING_DEMO_RESET_MINUTES`). Pas de cron nécessaire ; `php artisan demo:reset` force
  une réinitialisation.
- **Aucun envoi** : aucun e-mail ni SMS ne part pour les données de démo. Les liens propriétaire et visiteur
  s'affichent à l'écran.
- **Profil verrouillé** : le compte démo ne peut pas changer son profil.

Pour l'installation d'une agence, laissez les deux options à `false` : `/` ouvre l'administration et
`robots.txt` interdit l'indexation.

**Outils gratuits** (`/outils`) : calculateur « quel salaire pour louer » (règle des 3 fois le loyer),
générateur de QR code, checklist de visite imprimable et modèle de bon de visite. Ils ne reposent sur aucun
barème légal : rien à mettre à jour.

**Ajouter un guide SEO** : créez `resources/content/guides/mon-slug.md` avec cet en-tête :

```markdown
---
title: "Titre de 60 à 70 caractères"
description: "Description de 140 à 160 caractères."
date: 2026-11-10
updated: 2026-11-20   # facultatif
---
```

Le guide apparaît alors dans `/guides`, sur la page d'accueil (3 derniers) et dans le sitemap. Chaque guide
a ses données structurées Article et BreadcrumbList.

Mentions légales : remplissez les variables `IMMOBOOKING_LEGAL_*`, elles sont obligatoires pour un site
édité en France.

## Tests

```bash
php artisan test
```

GitHub Actions lance les tests sur PHP 8.3, 8.4 et 8.5, avec `composer audit`, à chaque push et
automatiquement le 1er de chaque mois. Activez les notifications GitHub pour être prévenu si un test
échoue ou si une dépendance devient vulnérable, sans rien surveiller vous-même.
