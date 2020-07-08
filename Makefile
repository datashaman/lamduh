AWS_REGION ?= eu-west-1
BUILD_TAG = datashaman/phial-73-build
IMAGE_ID = ami-08935252a36e25f85
IMAGE_TAG = datashaman/phial-73
# INSTANCE_TYPE = t2.large
INSTANCE_TYPE = t2.micro
KEY_NAME = datashaman
MD5SUM = $(word 1, $(shell md5sum --binary dist/$(PHP_PACKAGE).zip))
PHP_MAJOR_VERSION = 7
PHP_MINOR_VERSION = 3
PHP_PACKAGE = php$(subst .,,$(PHP_VERSION))
PHP_VERSION = $(PHP_MAJOR_VERSION).$(PHP_MINOR_VERSION)
ROOT_DIR := $(shell dirname $(realpath $(lastword $(MAKEFILE_LIST))))
S3_BUCKET = phial-layers-$(AWS_REGION)
S3_KEY = $(PHP_PACKAGE)/$(MD5SUM)
SECURITY_GROUP_DESCRIPTION = datashaman
SECURITY_GROUP_ID = sg-0e4bbf7dd070fa824
SECURITY_GROUP_NAME = datashaman
SUBNET_ID =
VERSION = $(shell aws --region $(AWS_REGION) lambda publish-layer-version --cli-input-json "{\"LayerName\": \"$(PHP_PACKAGE)\",\"Description\": \"PHP $(PHP_VERSION) Web Server Lambda Runtime\",\"Content\": {\"S3Bucket\": \"$(S3_BUCKET)\",\"S3Key\": \"$(S3_KEY)\"},\"CompatibleRuntimes\": [\"provided\"],\"LicenseInfo\": \"http://www.php.net/license/3_01.txt\"}"  --output text --query Version)

examples = $(notdir $(wildcard examples/*))

build:
	docker build \
		--build-arg PHP_MAJOR_VERSION=$(PHP_MAJOR_VERSION) \
		--build-arg PHP_MINOR_VERSION=$(PHP_MINOR_VERSION) \
		-t $(IMAGE_TAG) \
		.

dist: $(PHP_PACKAGE).zip

$(PHP_PACKAGE).zip:
	docker run --entrypoint '' -it --rm --user root --volume $(PWD)/.build:/build:rw $(IMAGE_TAG) bash -c 'cp -a /opt/* /build'
	sudo chown -R marlinf:marlinf .build/*
	cd .build/ && zip -r ../dist/$(PHP_PACKAGE).zip .

clean:
	sudo rm -rf .build/* dist/*

upload:
	aws --region $(AWS_REGION) s3 cp dist/$(PHP_PACKAGE).zip s3://$(S3_BUCKET)/$(S3_KEY)

publish: clean build dist upload
	aws --region $(AWS_REGION) lambda add-layer-version-permission --layer-name $(PHP_PACKAGE) --version-number $(VERSION) --statement-id=public --action lambda:GetLayerVersion --principal '*'

$(examples):
	./phial local --project-dir=examples/$@

bash:
	docker run -it --rm --entrypoint '' --env PATH=/opt/php73/bin:/usr/local/bin:/usr/bin:/bin -t $(IMAGE_TAG) bash

run:
	docker run -it --rm \
		--env PATH=/opt/php73/bin:/usr/local/bin:/usr/bin:/bin \
		--env PHP_PACKAGE=$(PHP_PACKAGE) \
		--volume $(PWD)/.build:/opt \
		--volume $(PWD):/var/task \
		$(IMAGE_TAG) \
		bash
