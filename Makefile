.PHONY: up test build

TEST=""

up:
	docker-compose start
build:
	docker build -t kushki/magento1.9 -f dev-environment/Dockerfile .
	docker-compose -f dev-environment/docker-compose.yml up -d
	docker exec -it devenvironment_web_1 install-magento
	docker exec -it devenvironment_web_1 install-sampledata