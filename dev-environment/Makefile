.PHONY: up test build

TEST=""

up:
	docker-compose start
build:
	docker build -t kushki/magento1.9 -f .dev-environment/Dockerfile .
	docker-compose up -d
	docker exec -it devenvironment_web_1 install-\magento