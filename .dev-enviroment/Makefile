.PHONY: up test build

TEST=""

up:
	docker-compose start
build:
	docker build -t kushki/magento1.9 .
	docker-compose up -d
	docker exec -it devenviroment_web_1 install-magento