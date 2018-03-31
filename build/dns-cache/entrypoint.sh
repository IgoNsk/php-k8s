#!/bin/sh

FORWARD_ADDR=$(getent hosts kube-dns.kube-system.svc | awk '{print $1}')

cat > /etc/unbound/unbound.conf << EOF
server:
	verbosity: ${VERBOSITY:-1}
	interface: 0.0.0.0
	do-ip6: no
	do-daemonize: no
	use-syslog: no

forward-zone:
  name: "."
  forward-addr: ${FORWARD_ADDR}
EOF

exec /usr/sbin/unbound -c /etc/unbound/unbound.conf
