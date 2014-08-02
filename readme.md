# pub

A php CLI application for managing publisher projects

##building

```php
composer install
```

##List of commands
```bash
>pub

pub version 1.0.0

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
  composer-validate   Validate a projects composer file for publisher & pub
  config-del          Delete configurations key for pub command
  config-get          Get configurations for pub command
  config-set          Set configurations for pub command
  git-init            Initialized proper git remotes NBCUOTS & Acquia
  help                Displays help for a command
  list                Lists commands
  pr-certify          Certify a specific pull-request.
  pr-reject           Reject a specific pull-request.

```