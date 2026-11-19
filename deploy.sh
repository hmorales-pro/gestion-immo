#!/usr/bin/env bash
#
# ImmoBooking — installation et mise à jour sur un hébergement mutualisé (SSH).
#
#   ./deploy.sh --install   première installation (crée .env, clé, base, démo)
#   ./deploy.sh             mise à jour (git pull, dépendances, migrations, caches)
#
# Variables facultatives :
#   PHP_BIN=/usr/bin/php8.5 ./deploy.sh      si "php" en SSH n'est pas la bonne version
#   COMPOSER_BIN="php composer.phar" ./deploy.sh
#   SKIP_GIT=1 ./deploy.sh                   si les fichiers sont envoyés par FTP
#
# Tout le script est dans une fonction : bash la lit en entier avant de l'exécuter,
# donc le "git pull" peut remplacer ce fichier sans casser l'exécution en cours.

main() {
    set -euo pipefail
    cd "$(dirname "$0")"

    local install=0
    [[ "${1:-}" == "--install" ]] && install=1

    PHP="${PHP_BIN:-php}"
    step "Vérification de PHP"
    "$PHP" -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' \
        || fail "PHP 8.2 ou plus est requis (trouvé : $("$PHP" -r 'echo PHP_VERSION;')). Changez la version dans le panneau de l'hébergeur ou utilisez PHP_BIN=/chemin/vers/php8.5."
    echo "PHP $("$PHP" -r 'echo PHP_VERSION;')"

    COMPOSER="$(find_composer)"

    if [[ ! -f .env ]]; then
        if (( install )); then
            cp .env.mutualise.example .env
            fail ".env vient d'être créé à partir de .env.mutualise.example. Complétez-le (APP_URL, base de données, Brevo…) puis relancez : ./deploy.sh --install"
        fi
        fail "Fichier .env introuvable. Pour une première installation : ./deploy.sh --install"
    fi

    if (( ! install )) && [[ -z "${SKIP_GIT:-}" ]] && [[ -d .git ]]; then
        step "Récupération de la dernière version"
        git pull --ff-only
    fi

    local was_installed=0
    if [[ -f vendor/autoload.php ]]; then
        was_installed=1
        step "Mise en maintenance"
        "$PHP" artisan down --retry=15 || true
        trap 'echo; echo "Échec : remise en ligne du site."; "$PHP" artisan up || true' ERR
    fi

    step "Dépendances PHP"
    $COMPOSER install --no-dev --optimize-autoloader --no-interaction --no-progress

    if ! grep -qE '^APP_KEY=.+' .env; then
        step "Génération de la clé de l'application"
        "$PHP" artisan key:generate --force
    fi

    step "Droits d'écriture"
    chmod -R ug+rwX storage bootstrap/cache

    step "Base de données"
    "$PHP" artisan migrate --force

    if (( install )) && grep -qE '^IMMOBOOKING_DEMO=true' .env; then
        step "Données de démonstration"
        "$PHP" artisan demo:reset
    fi

    step "Caches"
    "$PHP" artisan optimize:clear
    "$PHP" artisan config:cache
    "$PHP" artisan route:cache
    "$PHP" artisan view:cache
    "$PHP" artisan event:cache
    "$PHP" artisan filament:optimize

    step "Vérification des pages"
    "$PHP" artisan immobooking:check || echo "⚠ Certaines pages sont en erreur : copiez le message ci-dessus pour le corriger."

    if (( was_installed )); then
        step "Remise en ligne"
        trap - ERR
        "$PHP" artisan up
    fi

    step "Terminé"
    if (( install )); then
        echo "Créez maintenant votre compte agent : $PHP artisan make:filament-user"
    fi
    echo "Site : $(grep -E '^APP_URL=' .env | cut -d= -f2-)"
}

find_composer() {
    if [[ -n "${COMPOSER_BIN:-}" ]]; then echo "$COMPOSER_BIN"; return; fi
    # Some shared hosts ship Composer 1 as "composer" and Composer 2 as "composer2".
    if command -v composer2 >/dev/null 2>&1; then echo "composer2"; return; fi
    if command -v composer >/dev/null 2>&1; then echo "composer"; return; fi
    if [[ -f composer.phar ]]; then echo "$PHP composer.phar"; return; fi
    fail "Composer est introuvable. Installez composer.phar dans ce dossier ou utilisez COMPOSER_BIN=..."
}

step() { printf '\n\033[1;33m▸ %s\033[0m\n' "$1"; }
fail() { printf '\n\033[1;31m✗ %s\033[0m\n' "$1" >&2; exit 1; }

main "$@"
exit
