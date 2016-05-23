HOSTPORT = 12345

.PHONY: docker run-docker

docker:
	docker build -t "malibu" ..

run-docker: docker
	@echo "Running 'malibu' container on port $(HOSTPORT)"
	@echo "Open 'http://localhost:$(HOSTPORT)/isbn/suche.html' to access it"
	docker run --rm -it -p $(HOSTPORT):80 "malibu"
