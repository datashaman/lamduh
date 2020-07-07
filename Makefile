AWS_REGION ?= eu-west-1

BUILD_TAG = datashaman/phial-7.4-build
DOCKER_TAG = datashaman/phial-7.4

RUNTIME = docker run --rm -v $(PWD)/artifacts:/lambda/opt lambci/yumda:2

# INSTANCE_TYPE = t2.large
IMAGE_ID = ami-08935252a36e25f85
INSTANCE_TYPE = t2.micro
KEY_NAME = datashaman
SECURITY_GROUP_DESCRIPTION = datashaman
SECURITY_GROUP_ID = sg-0e4bbf7dd070fa824
SECURITY_GROUP_NAME = datashaman
SUBNET_ID =

examples = $(notdir $(wildcard examples/*))

$(examples):
	./phial local --project-dir=examples/$@

layers: layers-build layers-copy layers-publish

layers-publish:
	aws $(AWS_FLAGS) \
		lambda publish-layer-version \
		--compatible-runtimes provided \
		--layer-name PHP-74-runtime \
		--region $(AWS_REGION) \
		--zip-file fileb://artifacts/runtime.zip
	aws $(AWS_FLAGS) \
		lambda publish-layer-version \
		--compatible-runtimes provided \
		--layer-name PHP-74-vendor \
		--region $(AWS_REGION) \
		--zip-file fileb://artifacts/vendor.zip

layers-build:
	docker build \
		-t $(BUILD_TAG) \
		.

layers-copy:
	rm -f artifacts/{runtime,vendor}.zip
	docker run -it --rm \
		-v $(PWD)/artifacts/:/opt \
		$(BUILD_TAG) \
		cp runtime.zip vendor.zip artifacts

layers-runtime:
	sudo rm -rf artifacts/*
	$(RUNTIME) yum install -y libcurl libxml2 openssl re2c sqlite
	cd artifacts && zip -r system.zip *
	aws $(AWS_FLAGS) \
		lambda publish-layer-version \
		--compatible-runtimes provided \
		--layer-name PHP-74-system \
		--region $(AWS_REGION) \
		--zip-file fileb://artifacts/system.zip
