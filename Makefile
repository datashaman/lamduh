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

docker-build:
	docker build -t $(DOCKER_TAG) .

docker-run:
	docker run -it --rm $(DOCKER_TAG)

docker-bash:
	docker run -it --rm --entrypoint '' $(DOCKER_TAG) bash
