HOSTPORT = 12345
IMAGENAME = "malibu"

.PHONY: docker docker-run docker-run-%

docker:
	docker build -t $(IMAGENAME) ..

docker-run: docker
	@echo "Running 'malibu' container on port $(HOSTPORT)"
	@echo "Open 'http://localhost:$(HOSTPORT)/malibu/isbn/suche.html' to access it"
	docker run --rm -it -p $(HOSTPORT):80 "$(IMAGENAME)" $(DOCKER_COMMAND)

docker-run-%:
	$(MAKE) docker-run DOCKER_COMMAND=$*
