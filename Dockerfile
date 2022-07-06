FROM node:lts-alpine
ADD ui /app
WORKDIR /app
RUN npm i && \
    npm run build

FROM alpine:3.16
LABEL maintainer="samlm0 <i@teddysun.com>"

RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.tuna.tsinghua.edu.cn/g' /etc/apk/repositories && \
    apk add --no-cache php81 php81-pecl-maxminddb php81-ctype php81-pecl-swoole nginx xz \
    iperf iperf3 \
    mtr \
    traceroute \
    iputils

ADD backend/app /app
COPY --from=0 /app/dist /app/webspaces

CMD php81 /app/app.php