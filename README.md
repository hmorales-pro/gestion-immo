# ImmoBooking

Alternative légère, auto-hébergée et open source à Calendly, pensée pour les agences immobilières.
Chaque bien a son propre lien public de prise de rendez-vous ; les visites impliquent l'agent, le
visiteur (acheteur / locataire) et le propriétaire.

**Stack :** Laravel 11 · Filament 3 · Livewire 3 · TailwindCSS (+ `@tailwindcss/forms`) · SQLite (compatible PostgreSQL)

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

## Tests

```bash
php artisan test
```
