# TeamCity Messages

This PHP library simplifies formatting and writing TeamCity service messages.

[![Build Status](https://travis-ci.org/michal-kocarek/teamcity-messages.svg?branch=master)](https://travis-ci.org/michal-kocarek/teamcity-messages)
[![Coverage Status](https://coveralls.io/repos/github/michal-kocarek/teamcity-messages/badge.svg?branch=master)](https://coveralls.io/github/michal-kocarek/teamcity-messages?branch=master)

## Installation

To add TeamcityMessages as a local, per-project dependency to your project, simply add a dependency on `michal-kocarek/teamcity-messages` to your project's `composer.json` file.
Here is a minimal example of a `composer.json` file that just defines a dependency on TeamcityMessages 0.1:

    {
        "require": {
            "michal-kocarek/teamcity-messages": "^0.1"
        }
    }


## Usage

Here's the basic example. MessageLogger instance dumps everything through the writer.

StdOutWriter echoes output directly to script standard output.

```php
use MichalKocarek\TeamcityMessages\MessageLogger;
use MichalKocarek\TeamcityMessages\Writers\StdoutWriter;

$logger = new MessageLogger(new StdoutWriter());

$logger->progressMessage('Reticulating splines...');

$logger->block('Counting llamas...', null, function(MessageLogger $logger) {
    $logger->warning('Too many llamas!');
    $logger->publishArtifacts('logs/llamas-count.csv');
});
```

See PhpDoc for MessageLogger public methods for more info about the usage.

## Links

* [Build Script Interaction with TeamCity](https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity)

## Authors

Michal Kočárek <michal.kocarek@brainbox.cz> - <https://twitter.com/michalkocarek>

## License

This library is licensed under the MIT License – see the [LICENSE](LICENSE.md) file for details.
