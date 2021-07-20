#!/bin/bash

DBCONN_LOCAL_TAG=cob_dbconnector:latest
ECS_HOST=251803681989.dkr.ecr.us-east-1.amazonaws.com
ECS_URL=${ECS_HOST}/cob-digital-apps-staging/cob_dbconnector
DBCONN_ECS_TAG="deploy-default"

if [[ "${1}" == "push" ]] || [[ "${1}" == "PUSH" ]]; then
  printf "Building and pushing ECR (prod) container image.\n"
  read -p "==> Are you ready to build and push image for production (y/n) ? " ans
  if [[ ! $ans == "y" ]]; then
    printf "Abandoned.\n\n"
    exit
  fi

  # Login to aws ecr
  awslogin=$(aws ecr get-login-password --region us-east-1 --profile=cityofboston | docker login --username AWS --password-stdin ${ECS_HOST})
  if [[ "${awslogin}" != "Login Succeeded" ]]; then
    printf "AWS-ECR login failed.  See the README.md (ref: AWS credentials in Installation section)\n\n"
  else
    printf "ok - $awslogin\n"
    # Build the dbconnector image and tag with ECR tag
    # Push the image to the ECR repo.
    printf "[Note] Build output is supressed.\n"
    printf "       (If build fails, run without 'push' argument to see errors)\n\n"
    printf "Depending on what needs to be built by Docker, this process could take 5+ mins to run.\n"
    docker build --quiet --tag ${ECS_URL}:${DBCONN_ECS_TAG} --file Dockerfile . &&
      printf "Docker build is complete.\n" &&
      docker push ${ECS_URL}:${DBCONN_ECS_TAG} &&
      printf "Latest container is now in AWS-ECR (and tagged) ready for deployment.\n"

    # Now deploy the newly uploaded image.
    # Ensure IPAddress URL for the AWS EC2/ECS (AppStaging) instance is properly set,
    # and the VPN is established (if needed).
    # NOTE: This IPAddress changes when the AWS AMI is updated.
    # Connection information is located within config.json
    python3 ./deploy.py || printf "Ensure IPAddress URL for the AWS EC2/ECS (AppStaging) instance is properly set ... \n\n"

  fi

else
  # Make sure production apoc config is in place
  printf "Building local container image.\n"
  read -p "==> Are you ready to build and tag a local Docker Neo4J image (y/n) ? " ans
  if [[ ! $ans == "y" ]]; then
    printf "Abandoned.\n\n"
    exit
  fi

  # Make sure current mode is "local" in (python) config and Dockerfile
  sed -i 's/"current": "prod",/"current": "local",/' config.json
  sed -i 's/CMDB_ENV=prod/CMDB_ENV=local/' neo4j/Dockerfile

  # Make sure local apoc config is back in place
  rm -f conf/apoc.conf &&
    cp conf/apoc.local conf/apoc.conf

  # Build the Neo4J image and tag as latest/local
  docker build --tag ${DBCONN_LOCAL_TAG} --file neo4j/Dockerfile .

  printf "\n[NOTE] This image was only built and tagged locally (i.e. for dev purposes). \n"
  printf "       You can re-run the script with the 'push' argument to deploy to AWS.\n"
  printf "       e.g. %s push \n\n" $BASH_SOURCE

  printf "[TIP] To deploy locally, you can now run the following commands:\n"
  printf "    $ docker stop neo4j\n"
  printf "    $ docker rm neo4j\n"
  printf "    $ docker-compose up --no-build -d neo4j\n"
  printf "[TIP] You can also edit and redeploy the container in Portainer (recommended)\n"

fi
