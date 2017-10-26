#!/bin/bash

#SONARQBE
GITHUB_USER=devkushkipagos
ORGANIZATION=Kushki
PULL_REQUEST_ID=""
cd /tmp/local/Kushki/KushkiPayment
phpunit --bootstrap controllers/PaymentControllerKushki.php tests/unit/PaymentControllerKushkiTest.php
while IFS= read -r request
do
    COUNT=$(curl --user "$GITHUB_USER:$SONAR_OAUTH_TOKEN" https://api.github.com/repos/$ORGANIZATION/$CI_REPO_NAME/pulls/$request/commits | grep $CI_COMMIT_ID | wc -l)
    if [[ $COUNT>0 ]];then
    PULL_REQUEST_ID=$request
    fi
done < <(curl --user "$GITHUB_USER:$SONAR_OAUTH_TOKEN" https://api.github.com/repos/$ORGANIZATION/$CI_REPO_NAME/pulls?state=open | grep number\"  |grep -o '[0-9]*'  | sed -r 's/[ ]+/,/g')

if [[ "$CI_BRANCH" == *"hotfix"* ]]; then
  TARGET="master"
  else
  TARGET="develop"
fi

if [[ "$PULL_REQUEST_ID" == "" ]]; then
  PULL_REQUEST_ID=$(curl --user "$GITHUB_USER:$SONAR_OAUTH_TOKEN" --request POST --data '{ "title": "$CI_BRANCH", "body": "Codeship auto pull request", "head": "$CI_BRANCH", "base": "$TARGET" }' https://api.github.com/repos/$ORGANIZATION/$CI_REPO_NAME/pulls | grep number\"  |grep -o '[0-9]*' )
fi

sonar-scanner -X -Dsonar.github.pullRequest=$PULL_REQUEST_ID -Dsonar.host.url=http://sonarqube.kushkipagos.com -Dsonar.login=$SONAR_APP_TOKEN -Dsonar.github.repository=$ORGANIZATION/$CI_REPO_NAME -Dsonar.github.oauth=$SONAR_OAUTH_TOKEN -Dsonar.analysis.mode=preview  -Dsonar.branch.name=$CI_BRANCH  -Dsonar.branch.target=$TARGET
if [ "$CI_BRANCH" == "master" ] || [ "$CI_BRANCH" == "develop" ]; then
sonar-scanner -X -Dsonar.branch=$CI_BRANCH
fi