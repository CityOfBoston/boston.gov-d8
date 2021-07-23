FROM node:16-alpine
ENV WORKSPACE=dbconnector
ENV DBCONN_HOME=/app
WORKDIR $DBCONN_HOME

EXPOSE 3000
EXPOSE 7473
EXPOSE 7687

# Install python and pip for script execution, and then load dependencies.
RUN apt-get update && apt-get install -y python3-minimal python3-pip python3-pyodbc curl unixodbc-dev cron unzip
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - && \
  curl https://packages.microsoft.com/config/debian/10/prod.list > /etc/apt/sources.list.d/mssql-release.list
RUN apt-get update && ACCEPT_EULA=Y apt-get -y install msodbcsql17

# Install the AWS CLI.
RUN cd ~ && \
  wget -q "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -O "awscliv2.zip" && \
  unzip -q awscliv2.zip && \
  rm awscliv2.zip && \
  mkdir $NEO4J_HOME/.aws && \
  ./aws/install
ENV AWS_PAGER=""

# Dev only, can remove when ready for prod
RUN apt-get install -y vim

# Set this to prod.  If a local container is built using the docker-compose
# file, then the variable will be reset by that process.
ENV COB_ENV=local
# Define a startup script, and add as extension_script envar
ENV EXTENSION_SCRIPT=$CMDB_HOME/scripts/startup.sh

# LEAVE THE FILE COPIES TO LAST TO SPEED UP BUILDS
# (docker build will use caches of the apt-gets ^^^)
# Install the scripts and other required files.
COPY --chown=neo4j:neo4j requirements.txt .
RUN pip3 install -r requirements.txt

# Make a cron task to run a startup process, and do the nightly main import.
RUN printf "# Removed by cmdb dockerfile during build." > /etc/crontab

# Need to create this file or else the ssh commands fail.
RUN mkdir $HOME/.ssh && touch $HOME/.ssh/known_hosts && touch cmdb_importer.log
COPY --chown=neo4j:neo4j ./ssh/ $HOME/.ssh

COPY --chown=neo4j:neo4j ./awscli/ $NEO4J_HOME/.aws
COPY --chown=root:root ./awscli/ /root/.aws
COPY --chown=neo4j:neo4j ./plugins/ /plugins
COPY --chown=neo4j:neo4j ./ssl/ $NEO4J_HOME/certificates
COPY --chown=neo4j:neo4j ./conf/ $NEO4J_HOME/conf
COPY --chown=neo4j:neo4j ./neo4j/import/*.cypher $NEO4J_HOME/import/
COPY --chown=neo4j:neo4j ./neo4j/import/ ./neo4j/import
COPY --chown=neo4j:neo4j ./neo4j/cob.grass ./neo4j/
COPY --chown=neo4j:neo4j ./keys/ ./keys
COPY --chown=neo4j:neo4j ./ssh/ ./ssh
COPY --chown=neo4j:neo4j ./scripts/startup.sh ./scripts/
COPY --chown=neo4j:neo4j .env ./
COPY --chown=neo4j:neo4j *.json ./
COPY --chown=neo4j:neo4j *.py ./

CMD ["neo4j"]
ENTRYPOINT ["/sbin/tini", "-g", "--", "/docker-entrypoint.sh"]
