.PHONY: up test build

TEST=""

up:
	docker-compose up -d
build:
	docker build -t kushki/magento .
	docker-compose up -d
	docker exec -it magent019enviroment_web_1 install-magento