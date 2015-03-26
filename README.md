# ![](logo.png)


[![Test Results](https://img.shields.io/badge/build-passing-brightgreen.svg)](http://ci.publisher7.com/job/flo/job/Flo-Analysis/lastCompletedBuild/testReport/)
[![Code Coverage](https://img.shields.io/badge/coverage-54.8-yellow.svg)](http://ci.publisher7.com/job/flo/job/Flo-Analysis/)

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
 check-php         runs parallel-lint against the change files.
 check-php-cs      runs phpcs against the change files.
 config-del        Delete configurations key for flo command
 config-get        Get configurations for flo command
 config-set        Set configurations for flo command
 git-init          Initialized proper git remotes NBCUOTS & Acquia
 help              Displays help for a command
 list              Lists commands
 new-release       Updates a version file (e.g. version.php), commits that change and tags the commit for release.
 new-relic         Deploy a tag to new-relic.
 pr-certify        Certify a specific pull-request.
 pr-deploy         Deploy a specific pull-request to a solo environment.
 pr-destroy        Destroy pull-request environment(s), removing its web root and database.
 pr-integration    Pull all valid PRs into the acquia integration branch.
 pr-postpone       Postpone a specific pull-request.
 pr-reject         Reject a specific pull-request.
 pr-unpostpone     Un-postpone a specific pull-request.
 pr-unreject       Un-reject a specific pull-request.
 run-script        Runs project-specific script for a particular event.
 self-update       Updates flo.phar to the latest version
 tag-deploy        Deploy a Tag on Acquia.
 tag-pre-release   Marks a Tag on GitHub as a non-production tag.
```
