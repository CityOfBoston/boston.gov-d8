FROM node:16-alpine
ENV WORKSPACE dbconnector
ENV DBCONN_HOME /app
WORKDIR $DBCONN_HOME

EXPOSE 3000/tcp
EXPOSE 3600/tcp

# Install dependencies
RUN apk --no-cache add curl gnupg tini bash

# Gets our root enterprise cert into the OS. Needed to securely talk to .cob
# servers.
RUN mkdir -m 755 -p /usr/local/share/ca-certificates
ADD https://raw.githubusercontent.com/CityOfBoston/devops-public/master/ca-certificates/CityOfBoston-Enterprise-Root-CA.crt /usr/local/share/ca-certificates/
RUN chmod 644 /usr/local/share/ca-certificates/* && update-ca-certificates

# Tells Node to use the OS for trusted certificates
ENV NODE_OPTIONS=--use-openssl-ca

# Download the desired package(s)
RUN curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/msodbcsql17_17.6.1.1-1_amd64.apk
RUN curl -O https://download.microsoft.com/download/e/4/e/e4e67866-dffd-428c-aac7-8d28ddafb39b/mssql-tools_17.6.1.1-1_amd64.apk

# Install the package(s)
RUN apk add --allow-untrusted msodbcsql17_17.6.1.1-1_amd64.apk
RUN apk add --allow-untrusted mssql-tools_17.6.1.1-1_amd64.apk

# Install the AWS CLI.
# RUN cd ~ && \
#   wget -q "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -O "awscliv2.zip" && \
#   unzip -q awscliv2.zip && \
#   rm awscliv2.zip && \
#   mkdir $DBCONN_HOME/.aws && \
  # ./aws/install
  # /usr/local/bin/aws --version
ENV AWS_PAGER ""

# Dev only, can remove when ready for prod
RUN apk --no-cache add vim

# Flag for container processes to use.  Set by build.sh during building.
ENV COB_ENV local
ENV USE_SSL 1

# LEAVE THE FILE COPIES TO LAST TO SPEED UP BUILDS
ADD . /app/

RUN npm install

# COPY ./dist/ ./dist/
# COPY ./node_modules/ ./node_modules/
# COPY ./src/ ./src/
# COPY --chown=root:root ./awscli/ /root/.aws

CMD ["npm", "start"]
ENTRYPOINT ["/sbin/tini", "-g", "--"]
