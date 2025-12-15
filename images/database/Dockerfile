FROM linuxserver/mariadb:11.4.8
ENV REMOTE_SQL=http://database
RUN ln -sf /var/lib/mysql /config/databases
RUN echo "# Disable binlogs" >> /defaults/custom.cnf
RUN echo "skip-log-bin" >> /defaults/custom.cnf
