HOSTPORT = 12345
IMAGENAME = "malibu"

.PHONY: docker run-docker

docker:
	docker build -t $(IMAGENAME) ..

run-docker: docker
	@echo "Running 'malibu' container on port $(HOSTPORT)"
	@echo "Open 'http://localhost:$(HOSTPORT)/isbn/suche.html' to access it"
	docker run --rm -it -p $(HOSTPORT):80 "$(IMAGENAME)"

run-docker-bash: docker
	docker run --rm -it -p $(HOSTPORT):80 "$(IMAGENAME)" bash
