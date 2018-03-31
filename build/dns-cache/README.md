# DNS cache for pods
## Для чего это нужно
Существуют сервисы, который на каждый запрос извне(http), генерируют до 5 - 10 запросов в DNS, с учётом механики работы kube-dns(Service Discovery) это значение можно умнажать на 3 и на 2(A и AAAA), тоесть при 200 RPS на сервис, мы получаем 5 * 3 * 2 * 200 = 6000 запросов к DNS в секунду. В первую очередь это касается сервисов которые не обладают внутренней реализацией DNS резолвинга - сервисы на PHP
При увеличении количества таких сервисов и связей между ними мы получим неоправданный поток запросов в DNS.  
Чтобы решить эту проблему предлагается для подобных сервисов использовать внешний для кода приложения, но локальный(работающий на том же сетевом интерфейсе) механизм кеширования записей DNS и контроля TTL.
## Как это использовать
Для начала нам потребуется переопределить resolv.conf во всех контейнерах где хочется заиспользовать DNS cache. Для этого:
* Создаём templates/configmap.yaml.j2  
```
apiVersion: v1
kind: ConfigMap
metadata:
  name: {{ app_name }}-resolvconf
data:
  resolv.conf: |
    search {{ k8s_namespace }}.svc.{{ dns_base }} svc.{{ dns_base }} {{ dns_base }}
    nameserver 127.0.0.1
    options ndots:5
```
 * app_name - имя вашего приложения в config.yaml
 * k8s_namespace - ваш namespace в config.yaml
 * dns_base придётся добавить вручную для каждого DC(см. ниже)
* Добавляем его в config.yaml  
```
...
staging:
  kubectl:
  - template: configmap.yaml.j2 # Важно, чтобы он был перед deployment
  - template: deployment.yaml.j2
...
```
* Добавляем resolv.conf в контейнер в templates/deployment.yaml.j2  
```
# Большая часть конфига пропущена, будьте внимательны
apiVersion: extensions/v1beta1
kind: Deployment
spec:
  template:
    spec:
      volumes:
      - name: resolv-conf
        configMap:
          name: {{ app_name }}-resolvconf
      containers:
      - name: {{ app_name }}
        volumeMounts:
        - mountPath: /etc/resolv.conf
          name: resolv-conf
          subPath: resolv.conf
```
* Добавляем dns-cache контейнер в под в templates/deployment.yaml.j2  
```
...
        name: {{ app_name }}-dns
        image: docker-hub.2gis.ru/2gis-io/dns-cache:1.0
        resources:
          requests:
            cpu: 100m
            memory: 32M
          limits:
            cpu: 200m
            memory: 64M
...
```
* Добавляем dns_base в config.yaml  
```
...
staging:
  dns_base: web-staging.os-n3.hw
  ...
n3:  
  dns_base: k8s.os-n3.hw
  ...
m1:
  dns_base: k8s.m1.nato
  ...
d1:
  dns_base: k8s.os-d1.hw
  ...
```

* Готово! Можно разворачивать
