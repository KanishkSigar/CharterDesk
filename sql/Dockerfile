FROM mariadb:11.4
COPY --chmod=0644 schema.sql /docker-entrypoint-initdb.d/01-schema.sql
COPY --chmod=0644 seed.sql /docker-entrypoint-initdb.d/02-seed.sql
COPY --chmod=0644 participants.sql /docker-entrypoint-initdb.d/03-participants.sql
