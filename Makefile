.PHONY: up test build

TEST=""

up:
	docker-compose start
build:
    docker build -t kushki-local/basemagento1.9 -f .dev-environment/Dockerfile .
	docker-compose -f .dev-environment/docker-compose.yml up -d
	docker exec -it devenvironment_web_1 install-magento
	docker exec -it devenvironment_web_1 install-sampledata
unitTest:
    docker exec -it devenvironment_web_1 phpunit   /tmp/local/Kushki/KushkiPayment/tests