AWS_REGION ?= eu-west-1

BUILD_TAG = datashaman/phial-7.4-build
DOCKER_TAG = datashaman/phial-7.4

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

build:
	docker build \
		-t $(BUILD_TAG) \
		.
	docker run \
		-v $(PWD)/artifacts/:/artifacts \
		$(BUILD_TAG) \
		cp runtime.zip vendor.zip artifacts
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

docker-run:
	docker run -it --rm $(DOCKER_TAG)

docker-sh:
	docker run -it --rm --entrypoint '' $(DOCKER_TAG) bash
