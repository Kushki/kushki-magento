.PHONY: up test build

TEST=""

up:
	docker-compose start
build:
<<<<<<< HEAD
    docker build -t kushki-local/basemagento1.9 -f .dev-environment/Dockerfile .
=======
	docker build -t kushki-local/basemagento1.9 -f .dev-environment/Dockerfile .
>>>>>>> [KV-0] New version
	docker-compose -f .dev-environment/docker-compose.yml up -d
	docker exec -it devenvironment_web_1 install-magento
	docker exec -it devenvironment_web_1 install-sampledata
unitTest:
<<<<<<< HEAD
    docker exec -it devenvironment_web_1 phpunit   /tmp/local/Kushki/KushkiPayment/tests
=======
	docker exec -it devenvironment_web_1 phpunit   /tmp/local/Kushki/KushkiPayment/tests
>>>>>>> [KV-0] New version
