#!/usr/bin/env bash
# =====================================================================
#  _tests/probe.sh — check every .htaccess rule with real HTTP requests
# ---------------------------------------------------------------------
#  Local (default): copies the site to a temp folder and serves it with
#  Laragon's Apache + PHP on its own port and config, so Laragon itself
#  is never touched. Then fires curl requests and checks the answers.
#    bash _tests/probe.sh
#
#  Live (after a deploy): read-only requests to the real server. Uses
#  the IP from public DNS, so the Windows hosts file can't interfere.
#    bash _tests/probe.sh --prod kiande.com
#
#  Exit code 0 = everything passed.
# =====================================================================
set -u
APACHE="${APACHE:-E:/vlaragon/bin/apache/httpd-2.4.66-260223-Win64-VS18}"
PHPDIR="${PHPDIR:-E:/vlaragon/bin/php/php-8.3.30-Win32-vs16-x64}"
PORT="${PORT:-18765}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WORK="$(cygpath -m "${TMPDIR:-/tmp}")/parked-probe"
BODY="$WORK/body.html"
PROD_IP=""
FAILS=0

pass() { printf 'ok    %s\n' "$1"; }
fail() { printf 'FAIL  %s\n' "$1"; FAILS=$((FAILS + 1)); }

# req <scheme> <host> <path> [curl args] — saves body to $BODY, prints "status location"
req() {
	local scheme="$1" host="$2" path="$3"; shift 3
	if [ -n "$PROD_IP" ]; then
		local port=443; [ "$scheme" = http ] && port=80
		curl -s -o "$BODY" -w '%{http_code} %{redirect_url}' --max-time 15 \
			--resolve "$host:$port:$PROD_IP" "$@" "$scheme://$host$path"
	else
		curl -s -o "$BODY" -w '%{http_code} %{redirect_url}' --max-time 15 \
			-H "Host: $host" "$@" "http://127.0.0.1:$PORT$path"
	fi
}

# headers <host> <path> [curl args] — prints the response headers
headers() {
	local host="$1" path="$2"; shift 2
	if [ -n "$PROD_IP" ]; then
		curl -s -D - -o /dev/null --max-time 15 --resolve "$host:443:$PROD_IP" "$@" "https://$host$path"
	else
		curl -s -D - -o /dev/null --max-time 15 -H "Host: $host" "$@" "http://127.0.0.1:$PORT$path"
	fi
}

# expect <label> <scheme> <host> <path> <status> [location] [text the body must contain]
expect() {
	local label="$1" scheme="$2" host="$3" path="$4" want="$5" wantloc="${6:-}" wanttext="${7:-}"
	local got loc
	read -r got loc <<< "$(req "$scheme" "$host" "$path")"
	if [ "$got" != "$want" ]; then fail "$label: got $got${loc:+ -> $loc}, expected $want"; return; fi
	if [ -n "$wantloc" ] && [ "$loc" != "$wantloc" ]; then fail "$label: redirects to '$loc', expected '$wantloc'"; return; fi
	if [ -n "$wanttext" ] && ! grep -q "$wanttext" "$BODY"; then fail "$label: body lacks '$wanttext'"; return; fi
	pass "$label"
}

has()   { if grep -qiE "$3" <<< "$2"; then pass "$1"; else fail "$1"; fi; }
lacks() { if grep -qiE "$3" <<< "$2"; then fail "$1"; else pass "$1"; fi; }

start_local() {
	rm -rf "$WORK"; mkdir -p "$WORK/site"
	# Copy the site without .git, then plant a fake .git/HEAD to prove it's blocked.
	tar -C "$ROOT" --exclude=.git -cf - . | tar -C "$WORK/site" -xf -
	mkdir -p "$WORK/site/.git" && echo "ref: refs/heads/main" > "$WORK/site/.git/HEAD"
	cat > "$WORK/httpd.conf" <<EOF
ServerRoot "$APACHE"
Listen 127.0.0.1:$PORT
ServerName localhost
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule authz_host_module modules/mod_authz_host.so
LoadModule dir_module modules/mod_dir.so
LoadModule mime_module modules/mod_mime.so
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
LoadModule expires_module modules/mod_expires.so
LoadModule filter_module modules/mod_filter.so
LoadModule deflate_module modules/mod_deflate.so
LoadModule php_module "$PHPDIR/php8apache2_4.dll"
PHPIniDir "$PHPDIR"
AddHandler application/x-httpd-php .php
TypesConfig conf/mime.types
PidFile "$WORK/httpd.pid"
ErrorLog "$WORK/error.log"
DocumentRoot "$WORK/site"
<Directory "$WORK/site">
	AllowOverride All
	Require all granted
</Directory>
DirectoryIndex index.php
EOF
	"$APACHE/bin/httpd.exe" -f "$WORK/httpd.conf" &
	if ! curl -s -o /dev/null --retry 30 --retry-connrefused --retry-delay 1 "http://127.0.0.1:$PORT/"; then
		echo "Apache did not start; see $WORK/error.log"; exit 2
	fi
}

stop_local() {
	[ -f "$WORK/httpd.pid" ] && taskkill //F //T //PID "$(cat "$WORK/httpd.pid")" > /dev/null 2>&1
}

if [ "${1:-}" = "--prod" ]; then
	D="${2:?usage: probe.sh --prod <domain>}"
	PROD_IP="$(nslookup -type=A "$D" 1.1.1.1 2>/dev/null | awk '/^Name:/{f=1} f&&/^Address/{print $2; exit}')"
	[ -n "$PROD_IP" ] || { echo "Could not resolve $D"; exit 2; }
	mkdir -p "$WORK"
	SELF="https://$D"
	echo "Live checks against $D ($PROD_IP)"
	expect "http -> https (host level)" http "$D" / 301 "https://$D/"
else
	D="kiande.com"
	SELF="http://$D"
	start_local
	trap stop_local EXIT
fi
IMG="$(cd "$ROOT/img" && ls | head -1)"

# Pages
expect "home page"               https "$D" /                200 "" "Coming Soon"
expect "/index.php -> /"         https "$D" /index.php       301 "$SELF/"
expect "robots.txt"              https "$D" /robots.txt      200 "" "User-agent"
expect "missing page"            https "$D" /nope            404 "" "Page Not Found"
expect "missing folder"          https "$D" /nope/           404 "" "Page Not Found"
expect "missing .php"            https "$D" /nope.php        404 "" "Page Not Found"
expect "bot probe wp-login"      https "$D" /wp-login.php    404 "" "Page Not Found"
expect "bot probe xmlrpc"        https "$D" /xmlrpc.php      404 "" "Page Not Found"

# www -> bare domain, path and query kept
expect "www -> bare"             http "www.$D" /             301 "https://$D/"
expect "www keeps path+query"    http "www.$D" "/a/b?c=1"    301 "https://$D/a/b?c=1"

# Blocked paths
expect "config.php blocked"      https "$D" /config.php      403 "" "Access Denied"
expect "partials/ blocked"       https "$D" /partials/       403 "" "Access Denied"
expect "partial file blocked"    https "$D" /partials/head.php 403 "" "Access Denied"
expect "_docs blocked"           https "$D" /_docs/          403 "" "Access Denied"
expect "_originals blocked"      https "$D" /_originals/     403 "" "Access Denied"
expect "_tests blocked"          https "$D" /_tests/check.php 403 "" "Access Denied"
expect ".git blocked"            https "$D" /.git/HEAD       403 "" "Access Denied"
expect ".gitignore blocked"      https "$D" /.gitignore      403 "" "Access Denied"
expect ".env blocked"            https "$D" /.env            403 "" "Access Denied"
expect ".well-known not blocked" https "$D" /.well-known/x   404 "" "Page Not Found"

# Security headers on 200, 403 and 404
for path in / /config.php /nope; do
	H="$(headers "$D" "$path")"
	has   "X-Frame-Options on $path"        "$H" '^X-Frame-Options: SAMEORIGIN'
	has   "X-Content-Type-Options on $path" "$H" '^X-Content-Type-Options: nosniff'
	has   "Referrer-Policy on $path"        "$H" '^Referrer-Policy: strict-origin-when-cross-origin'
	has   "Permissions-Policy on $path"     "$H" '^Permissions-Policy: camera=\(\), microphone=\(\), geolocation=\(\)'
	lacks "no X-Powered-By on $path"        "$H" '^X-Powered-By:'
done

# Caching + compression
has "HTML not cached"      "$(headers "$D" /)"            'Cache-Control: max-age=0'
has "CSS cached 1 year"    "$(headers "$D" /style.css)"   'Cache-Control: max-age=31536000'
has "image cached 1 year"  "$(headers "$D" "/img/$IMG")"  'Cache-Control: max-age=31536000'
has "HTML gzipped"         "$(headers "$D" / -H 'Accept-Encoding: gzip')"          'Content-Encoding: gzip'
has "CSS gzipped"          "$(headers "$D" /style.css -H 'Accept-Encoding: gzip')" 'Content-Encoding: gzip'

echo
if [ "$FAILS" -eq 0 ]; then echo "All probe checks passed"; else echo "$FAILS probe check(s) failed"; fi
[ "$FAILS" -eq 0 ]
