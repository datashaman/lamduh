examples = $(notdir $(wildcard examples/*))

$(examples):
	cd examples/$@ && $(MAKE)
