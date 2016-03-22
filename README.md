# TeamCity Messages

This PHP library simplifies formatting and writing TeamCity service messages.

[![Build Status](https://travis-ci.org/michal-kocarek/teamcity-messages.svg?branch=master)](https://travis-ci.org/michal-kocarek/teamcity-messages)
[![Coverage Status](https://coveralls.io/repos/github/michal-kocarek/teamcity-messages/badge.svg?branch=master)](https://coveralls.io/github/michal-kocarek/teamcity-messages?branch=master)

## Installation

To add TeamcityMessages as a local, per-project dependency to your project, simply add a dependency on `michal-kocarek/teamcity-messages` to your project's `composer.json` file.
Here is a minimal example of a `composer.json` file that just defines a dependency on TeamcityMessages:

    {
        "require": {
            "michal-kocarek/teamcity-messages": "^1.1"
        }
    }


## Usage

Here's the basic example. MessageLogger instance dumps everything through the writer.

StdOutWriter echoes output directly to script standard output. (You may use CallbackWriter to pass messages
to arbitrary callback.)

Code like this:

```php
use MichalKocarek\TeamcityMessages\MessageLogger;
use MichalKocarek\TeamcityMessages\Writers\StdoutWriter;

$logger = new MessageLogger(new StdoutWriter());

$logger->progressMessage('Reticulating splines...');

$logger->block('Counting llamas...', null, function(MessageLogger $logger) {
    $logger->logWarning('Too many llamas!');
    $logger->publishArtifacts('logs/llamas-count.csv');
});
```

produces following output:

```
##teamcity[progressMessage timestamp='2016-03-16T23:29:37.120555+0000' message='Reticulating splines...']
##teamcity[blockOpened timestamp='2016-03-16T23:29:37.134303+0000' name='Counting llamas...']
##teamcity[message timestamp='2016-03-16T23:29:37.134535+0000' text='Too many llamas!' status='WARNING']
##teamcity[publishArtifacts timestamp='2016-03-16T23:29:37.134635+0000' path='logs/llamas-count.csv']
##teamcity[blockClosed timestamp='2016-03-16T23:29:37.134993+0000' name='Counting llamas...']
```

See MessageLogger public methods for more info about the usage and link below for detailed message specification.

## Links

* [Build Script Interaction with TeamCity](https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity)

## Authors

Michal Kočárek <michal.kocarek@brainbox.cz> - <https://twitter.com/michalkocarek>

## License

This library is licensed under the MIT License – see the [LICENSE](LICENSE.txt) file for details.
