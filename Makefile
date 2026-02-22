DC=docker compose

.PHONY: build install test analyse format-check qa shell

build:
	$(DC) build php

install:
	$(DC) run --rm php composer install --no-interaction --prefer-dist --no-progress

test:
	$(DC) run --rm php composer test

analyse:
	$(DC) run --rm php composer analyse

format-check:
	$(DC) run --rm php composer format -- --test

qa: test analyse format-check

shell:
	$(DC) run --rm php bash
