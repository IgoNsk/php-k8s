FROM alpine:3.6

LABEL description="DNS cache контейнер для использования в подах kubernetes" \
      maintainer="<Infrastructure & Operations> io@2gis.ru" \
      source="https://gitlab.2gis.ru/continuous-delivery/dns-cache"

RUN apk --no-cache add unbound
ADD entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh

EXPOSE 53 53/udp
ENTRYPOINT ["/entrypoint.sh"]
