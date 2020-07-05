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

create-php-layer:
	# aws ec2 create-security-group --group-name $(SECURITY_GROUP_NAME) --description $(SECURITY_GROUP_DESCRIPTION)
	aws ec2 run-instances \
		--image-id $(IMAGE_ID) \
		--count 1 \
		--instance-type $(INSTANCE_TYPE) \
		--key-name $(KEY_NAME) \
		--security-group-ids $(SECURITY_GROUP_ID) \
		--subnet-id $(SUBNET_ID)
