.PHONY: up test build

TEST=""

up:
	docker-compose start
build:
	docker-compose -f .dev-environment/docker-compose.yml up -d
	docker exec -it devenvironment_web_1 install-magento
	docker exec -it devenvironment_web_1 install-sampledata
unitTest:
    docker exec -it devenvironment_web_1 install-sampledata