#!/usr/bin/env bash
set -euo pipefail

SWAPFILE_PATH="${SWAPFILE_PATH:-/swapfile}"
SWAP_SIZE_GB="${SWAP_SIZE_GB:-2}"
SWAPPINESS="${SWAPPINESS:-10}"
VFS_CACHE_PRESSURE="${VFS_CACHE_PRESSURE:-50}"
SYSCTL_CONF="${SYSCTL_CONF:-/etc/sysctl.d/99-indicoerp-memory.conf}"

if [ "${EUID:-$(id -u)}" -ne 0 ]; then
  echo "ERRO: executar como root."
  exit 1
fi

is_positive_int() {
  [[ "$1" =~ ^[0-9]+$ ]] && [ "$1" -gt 0 ]
}

for value in "$SWAP_SIZE_GB" "$SWAPPINESS" "$VFS_CACHE_PRESSURE"; do
  if ! is_positive_int "$value"; then
    echo "ERRO: valor invalido (esperado inteiro positivo): $value"
    exit 1
  fi
done

if [ ! -f "$SWAPFILE_PATH" ]; then
  if command -v fallocate >/dev/null 2>&1; then
    fallocate -l "${SWAP_SIZE_GB}G" "$SWAPFILE_PATH"
  else
    dd if=/dev/zero of="$SWAPFILE_PATH" bs=1M count="$((SWAP_SIZE_GB * 1024))" status=progress
  fi
  chmod 600 "$SWAPFILE_PATH"
  mkswap "$SWAPFILE_PATH"
fi

if ! swapon --show | awk '{print $1}' | grep -Fxq "$SWAPFILE_PATH"; then
  swapon "$SWAPFILE_PATH"
fi

if ! grep -Eq "^[[:space:]]*${SWAPFILE_PATH//\//\\/}[[:space:]]+none[[:space:]]+swap[[:space:]]+sw[[:space:]]+0[[:space:]]+0[[:space:]]*$" /etc/fstab; then
  echo "$SWAPFILE_PATH none swap sw 0 0" >> /etc/fstab
fi

cat > "$SYSCTL_CONF" <<EOF
vm.swappiness=${SWAPPINESS}
vm.vfs_cache_pressure=${VFS_CACHE_PRESSURE}
EOF

sysctl -p "$SYSCTL_CONF" >/dev/null

echo "Swap configurada com sucesso."
echo "Ficheiro: $SWAPFILE_PATH (${SWAP_SIZE_GB}G)"
echo "vm.swappiness=${SWAPPINESS}"
echo "vm.vfs_cache_pressure=${VFS_CACHE_PRESSURE}"
swapon --show || true
free -h || true
