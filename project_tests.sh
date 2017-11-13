#!/usr/bin/env bash
set -e

rm -f build/*.xml

echo "proofreader"
vendor/bin/proofreader bin/ src/ web/
vendor/bin/proofreader --no-phpcpd tests/

echo "PHPUnit tests"
vendor/bin/phpunit --log-junit build/phpunit.xml
