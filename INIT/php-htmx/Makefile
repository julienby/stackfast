.PHONY: check lint test serve migrate

check: lint test

lint:
	find src public tests bin -name '*.php' -print0 | xargs -0 -n1 php -l > /dev/null

test:
	vendor/bin/phpunit

serve:
	php -S localhost:8000 -t public

migrate:
	php bin/migrate
