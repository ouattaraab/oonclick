# Checklist de déploiement oon.click

## Pré-déploiement
- [ ] Variables .env production configurées (DB, Paystack live, Pusher, R2, SMS)
- [ ] Compte Cloudflare R2 créé, bucket `oonclick-media` créé, CORS configuré
- [ ] Compte Pusher créé, credentials copiés dans .env
- [ ] Clés Paystack live récupérées (pas les clés test)
- [ ] Webhook Paystack configuré sur https://api.oon.click/api/paystack/webhook
- [ ] Compte Africa's Talking créé, numéro shortcode CI enregistré
- [ ] Domaine oon.click pointé vers Hostinger (A record + CNAME)
- [ ] SSL Let's Encrypt activé sur Hostinger cPanel
- [ ] Base MySQL créée sur Hostinger, credentials dans .env

## Déploiement
- [ ] `git clone` ou upload via SSH/SFTP
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `.env` production uploadé (jamais versionné)
- [ ] `php artisan key:generate`
- [ ] `php artisan migrate --force`
- [ ] `php artisan db:seed --class=PlatformConfigSeeder --force`
- [ ] `php artisan storage:link`
- [ ] `php artisan optimize`
- [ ] Crons configurés dans cPanel (voir CRON_SETUP.md)
- [ ] Test route `/up` → HTTP 200

## Post-déploiement
- [ ] Créer compte admin : `php artisan filament:user`
- [ ] Accéder à https://oon.click/admin → login OK
- [ ] Tester inscription abonné via Flutter (sandbox Paystack)
- [ ] Tester création campagne annonceur
- [ ] Vérifier webhook Paystack reçoit bien les events (dashboard Paystack)
- [ ] Vérifier notifications Pusher (dashboard Pusher Debug Console)
- [ ] Vérifier médias Cloudflare R2 (uploader une vidéo test)
- [ ] Vérifier emails (mail.log ou service SMTP)
- [ ] Vérifier queue workers : `php artisan queue:monitor`
