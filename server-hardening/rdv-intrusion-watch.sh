#!/bin/bash
# RDV Asie — surveillance intrusion WordPress
# Cron: toutes les 15 min — alerte mail si anomalie

set -u
SITE="/var/www/rdvasie.com"
STATE_DIR="/var/lib/rdv-intrusion-watch"
STATE_FILE="$STATE_DIR/last-alerts.hash"
LOG_FILE="/var/log/rdv-intrusion-watch.log"
ALERT_TO="stephlg2@orange.fr"
ALERT_FROM="securite@rdvasie.com"
HOSTNAME_SHORT="$(hostname -s 2>/dev/null || echo rdvasie)"
PHP_BIN="$(command -v php8.0 || command -v php8.4 || command -v php)"

mkdir -p "$STATE_DIR"
touch "$LOG_FILE"
chmod 700 "$STATE_DIR" 2>/dev/null || true

log() { echo "$(date '+%Y-%m-%d %H:%M:%S') $*" >> "$LOG_FILE"; }

ALERTS=()
add_alert() { ALERTS+=("$1"); }

# --- 1. Admins WP inattendus ---
ADMINS_JSON="$("$PHP_BIN" <<'PHP' 2>/dev/null
<?php
$_SERVER['HTTP_HOST']='www.rdvasie.com';
require '/var/www/rdvasie.com/wp-load.php';
$out=[];
foreach (get_users(['role'=>'administrator']) as $u) {
  $out[] = $u->ID.'|'.$u->user_login.'|'.$u->user_email;
}
echo implode("\n", $out);
PHP
)"
ALLOWED_ADMINS="jjoallan"
while IFS= read -r line; do
  [ -z "$line" ] && continue
  login="$(echo "$line" | cut -d'|' -f2)"
  if ! echo " $ALLOWED_ADMINS " | grep -q " $login "; then
    add_alert "ADMIN_INATTENDU: $line"
  fi
done <<< "$ADMINS_JSON"
ADMIN_COUNT="$(echo "$ADMINS_JSON" | grep -c . || true)"
if [ "${ADMIN_COUNT:-0}" -eq 0 ]; then
  add_alert "AUCUN_ADMIN: aucun administrateur WordPress trouvé"
fi

# --- 2. Plugins suspects (présents ou actifs) ---
BAD_PLUGIN_PATTERNS='filester|wp-file-manager|file-manager|njt-fs|wp-compat-cache|advanced-code-manager|wp-filemanager|elfinder'
for d in "$SITE"/wp-content/plugins/*; do
  [ -d "$d" ] || continue
  base="$(basename "$d")"
  if echo "$base" | grep -Eiq "$BAD_PLUGIN_PATTERNS"; then
    add_alert "PLUGIN_SUSPECT: dossier $base présent"
  fi
done
ACTIVE_BAD="$("$PHP_BIN" <<'PHP' 2>/dev/null
<?php
$_SERVER['HTTP_HOST']='www.rdvasie.com';
require '/var/www/rdvasie.com/wp-load.php';
foreach ((array)get_option('active_plugins') as $p) {
  if (preg_match('/filester|file.?manager|njt-fs|wp-compat-cache|advanced-code-manager|elfinder/i', $p)) {
    echo $p, "\n";
  }
}
PHP
)"
while IFS= read -r p; do
  [ -z "$p" ] && continue
  add_alert "PLUGIN_ACTIF_SUSPECT: $p"
done <<< "$ACTIVE_BAD"

# --- 3. MU-plugins hors allowlist ---
ALLOWED_MU='^(rdv-block-casino-spam|rdv-block-malicious-emails|rdv-force-homepage-seo|rdv-lock-theme|rdv-protect-gsc|rdv-security-lock)\.php$'
if [ -d "$SITE/wp-content/mu-plugins" ]; then
  while IFS= read -r f; do
    base="$(basename "$f")"
    case "$base" in
      index.php) continue ;;
    esac
    if [ -f "$f" ] && [[ "$base" == *.php ]]; then
      if ! echo "$base" | grep -Eq "$ALLOWED_MU"; then
        add_alert "MU_PLUGIN_INATTENDU: $base"
      fi
    fi
  done < <(find "$SITE/wp-content/mu-plugins" -maxdepth 1 -type f 2>/dev/null)
  # dossiers cachés / gz loaders
  while IFS= read -r f; do
    add_alert "MU_ARTEFACT: $(basename "$f")"
  done < <(find "$SITE/wp-content/mu-plugins" -maxdepth 2 \( -name '*.gz' -o -name 'wlock*' -o -name 'redis-cache-helper.php' -o -type d ! -path "$SITE/wp-content/mu-plugins" \) 2>/dev/null)
fi

# --- 4. Fichiers droppers connus ---
DROP_PATHS=(
  "$SITE/wp-meta.php"
  "$SITE/sitemap22.xml"
  "$SITE/wp-content/plugins/duplicate-post/common-functions-event.php"
  "$SITE/wp-includes/blocks/spacer/class-wp-db-delta.php"
  "$SITE/wp-includes/blocks/gallery/plugin-compat-report.php"
  "$SITE/wp-includes/blocks/table/wp-object-cache-helper.php"
  "$SITE/wp-includes/blocks/pattern/sync.php"
  "$SITE/wp-includes/blocks/social-link/wp-feed-manager.php"
  "$SITE/wp-content/themes/twentytwentyone/sync.php"
)
for f in "${DROP_PATHS[@]}"; do
  if [ -e "$f" ]; then
    add_alert "DROPPER: ${f#$SITE/}"
  fi
done

# google*.html récents (< 48h) hors allowlist connue
ALLOWED_GSC='google101bac414596211f.html'
while IFS= read -r f; do
  base="$(basename "$f")"
  if [ "$base" = "$ALLOWED_GSC" ]; then
    continue
  fi
  add_alert "GSC_HTML_INATTENDU: $base ($(stat -c '%y' "$f" 2>/dev/null | cut -d. -f1))"
done < <(find "$SITE" -maxdepth 1 -type f -name 'google*.html' 2>/dev/null)

# --- 5. Signatures malware (échantillon rapide) ---
HITS="$(grep -RIl --include='*.php' -E 'HTTP_5ACD3FB|STEALTH_GZ_GUARD|FilesMan|eval\s*\(\s*base64_decode\s*\(|eval\s*\(\s*curl_exec|module\.audio-video\.riff-num' \
  "$SITE/wp-content/mu-plugins" \
  "$SITE/wp-content/themes/Avada" \
  "$SITE/wp-content/themes/Avada-Child-Theme" \
  "$SITE/wp-includes" \
  "$SITE/wp-admin" \
  "$SITE"/wp-config.php \
  "$SITE"/*.php \
  2>/dev/null | head -40)"
while IFS= read -r f; do
  [ -z "$f" ] && continue
  add_alert "SIGNATURE_MALWARE: ${f#$SITE/}"
done <<< "$HITS"

# sync.php / login_admin.php récents dans plugins/themes
while IFS= read -r f; do
  add_alert "FICHIER_SUSPECT: ${f#$SITE/}"
done < <(find "$SITE/wp-content/plugins" "$SITE/wp-content/themes" \
  -type f \( -name 'sync.php' -o -name 'login_admin.php' -o -name 'bsdidics.php' -o -name '*shell*.php' \) 2>/dev/null | head -20)

# --- 6. Kit spam /tmp ---
while IFS= read -r d; do
  if [ -f "$d/cloaking.gz" ] || [ -f "$d/sitemap.gz" ] || [ -f "$d/router.gz" ]; then
    add_alert "TMP_SPAM_KIT: $d"
  fi
done < <(find /tmp -maxdepth 1 -type d \( -name '[0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f]' -o -name '[0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f][0-9a-f]' \) 2>/dev/null | head -10)

# --- Dédup / envoi ---
if [ "${#ALERTS[@]}" -eq 0 ]; then
  log "OK — aucune anomalie"
  # reset state so next alert fires again if issue returns after clean
  rm -f "$STATE_FILE"
  exit 0
fi

BODY="$(printf '%s\n' "${ALERTS[@]}")"
HASH="$(printf '%s' "$BODY" | sha256sum | awk '{print $1}')"
PREV="$(cat "$STATE_FILE" 2>/dev/null || true)"

if [ "$HASH" = "$PREV" ]; then
  log "ANOMALIES (déjà alerté, skip mail): ${#ALERTS[@]} finding(s)"
  exit 0
fi

SUBJECT="[ALERTE RDV Asie] Intrusion / anomalie détectée sur $HOSTNAME_SHORT"
FULL_BODY="Alerte surveillance rdvasie.com — $(date '+%Y-%m-%d %H:%M:%S %Z')

Anomalies détectées:
$BODY

Serveur: $(hostname)
Site: $SITE

Action: vérifier wp-admin utilisateurs/plugins, logs nginx, et /home/debian/security-quarantine
"

# Envoi via WP Mail SMTP (fiable)
export RDV_ALERT_TO="$ALERT_TO"
export RDV_ALERT_SUBJECT="$SUBJECT"
export RDV_ALERT_BODY="$FULL_BODY"
MAIL_OK="$("$PHP_BIN" <<'PHP' 2>/tmp/rdv-watch-mail.err
<?php
$_SERVER['HTTP_HOST'] = 'www.rdvasie.com';
$_SERVER['REQUEST_URI'] = '/';
require '/var/www/rdvasie.com/wp-load.php';
$to = getenv('RDV_ALERT_TO');
$subject = getenv('RDV_ALERT_SUBJECT');
$body = getenv('RDV_ALERT_BODY');
$headers = ['Content-Type: text/plain; charset=UTF-8'];
$ok = wp_mail($to, $subject, $body, $headers);
echo $ok ? 'OK' : 'FAIL';
PHP
)" || MAIL_OK="FAIL"

if [ "$MAIL_OK" = "OK" ]; then
  echo "$HASH" > "$STATE_FILE"
  log "ALERTE ENVOYEE ($MAIL_OK) à $ALERT_TO — ${#ALERTS[@]} finding(s)"
else
  log "ALERTE MAIL ECHEC — ${#ALERTS[@]} finding(s). stderr=$(tr '\n' ' ' </tmp/rdv-watch-mail.err 2>/dev/null)"
  # fallback PHP mail()
  "$PHP_BIN" -r '
    $to=getenv("RDV_ALERT_TO"); $s=getenv("RDV_ALERT_SUBJECT"); $b=getenv("RDV_ALERT_BODY");
    $ok=@mail($to,$s,$b,"From: securite@rdvasie.com\r\nContent-Type: text/plain; charset=UTF-8");
    echo $ok?"FALLBACK_OK\n":"FALLBACK_FAIL\n";
  ' >> "$LOG_FILE" 2>&1 || true
fi

exit 0
