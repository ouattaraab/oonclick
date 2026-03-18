# Crons à configurer dans cPanel → Cron Jobs

# 1. Laravel Scheduler (OBLIGATOIRE — toutes les minutes)
* * * * * /usr/bin/php8.2 /home/USERNAME/public_html/artisan schedule:run >> /dev/null 2>&1

# 2. Queue Worker (OBLIGATOIRE — toutes les minutes)
* * * * * /usr/bin/php8.2 /home/USERNAME/public_html/artisan queue:work database --stop-when-empty --tries=3 --timeout=60 >> /home/USERNAME/logs/queue.log 2>&1

# 3. Queue Worker — fraude (file dédiée, toutes les 2 minutes)
*/2 * * * * /usr/bin/php8.2 /home/USERNAME/public_html/artisan queue:work database --queue=fraud --stop-when-empty --tries=1 >> /home/USERNAME/logs/queue-fraud.log 2>&1

# Notes :
# - Remplacer USERNAME par le nom d'utilisateur Hostinger
# - Vérifier le chemin php8.2 : which php8.2 via SSH
# - Les logs sont dans ~/logs/ (créer le dossier si nécessaire)
# - Le scheduler exécute : oonclick:daily-stats à 08h00, queue:prune-failed quotidien
