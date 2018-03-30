FROM nginx:1.13.6-alpine

LABEL maintainer = "i.yatsevich@2gis.ru"
LABEL version = "1.0"
LABEL description = "Nginx image"

# пакет нужен для того, чтобы стянуть dockerize по https
RUN apk add --no-cache openssl

# dockerize в данном случае используем для шаблонизации параметров
ENV DOCKERIZE_VERSION v0.6.0
RUN wget https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz

COPY build/nginx/etc/main/nginx.conf /etc/nginx/nginx.conf
COPY build/nginx/etc/conf.d/default.conf.template /etc/nginx/conf.d/default.conf.template

COPY public/ /var/www/html/

CMD [ "dockerize",\
        "-template", "/etc/nginx/conf.d/default.conf.template:/etc/nginx/conf.d/default.conf",\
      "nginx", "-g", "daemon off;"\
    ]

