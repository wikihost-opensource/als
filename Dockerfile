FROM node:lts-alpine as builderNodeJSCache
ADD ui/package.json /app/package.json
WORKDIR /app
RUN npm i

FROM node:lts-alpine as builderNodeJS
ADD ui /app
WORKDIR /app
COPY --from=builderNodeJSCache /app/node_modules /app/node_modules
RUN npm run build \
    && chmod -R 650 /app/dist


FROM alpine:3 as builderGolang
ADD backend /app
WORKDIR /app
COPY --from=builderNodeJS /app/dist /app/embed/ui
RUN apk add --no-cache go 

RUN go build -o als && \
    chmod +x als

FROM alpine:3 as builderEnv
WORKDIR /app
ADD scripts /app
RUN sh /app/install-software.sh
RUN apk add --no-cache \
    iperf iperf3 \
    mtr \
    traceroute \
    iputils
RUN rm -rf /app

FROM alpine:3
LABEL maintainer="samlm0 <update@ifdream.net>"
COPY --from=builderEnv / /
COPY --from=builderGolang --chmod=777 /app/als/als /bin/als

CMD /bin/als