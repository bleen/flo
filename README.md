# ![](logo.png)


A php CLI application for managing publisher projects

##building

```php
composer install
```

Then add bin/flo to your path.


##testing

```sh
./vendor/bin/phpunit

# optional if you want to see the converage report inline.
./vendor/bin/phpunit --coverage-text

# If you want to see an html file of the converage report
./vendor/bin/phpunit --coverage-html=results/clover-html
# open results/clover-html/index.html for html report.
```

##requirements
* >= PHP 5.5
* [Hub](https://github.com/github/hub)

##List of commands
```bash
>flo

flo version 1.0.0

Usage:
  [options] command [arguments]

Options:
  --help           -h Display this help message.
  --quiet          -q Do not output any message.
  --verbose        -v Increase verbosity of messages.
  --version        -V Display this program version.
  --ansi              Force ANSI output.
  --no-ansi           Disable ANSI output.
  --no-interaction -n Do not ask any interactive question.

Available commands:
  acquia-init         Set up Acquia Cloud hooks for API Calls.
  composer-validate   Validate a projects composer file for publisher & flo
  config-del          Delete configurations key for flo command
  config-get          Get configurations for flo command
  config-set          Set configurations for flo command
  git-init            Initialized proper git remotes NBCUOTS & Acquia
  help                Displays help for a command
  list                Lists commands
  new-relic           Deploy a tag to new-relic.
  new-release         Tag a new release.
  pr-certify          Certify a specific pull-request.
  pr-deploy           Deploy a specific pull-request to a solo environment.
  pr-ignore           Ignore a specific pull-request.
  pr-integration      Pull all valid PRs into the acquia integration branch.
  pr-postpone         Postpone a specific pull-request.
  pr-reject           Reject a specific pull-request.
```
