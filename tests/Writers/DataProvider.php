<?php

namespace MichalKocarek\TeamcityMessages\Tests\Writers;

class DataProvider
{

    public static function getMessages()
    {
        return [
            'empty' => [''],
            'one-line' => ['a message'],
            'multiple' => [
                'a message'.PHP_EOL,
                'another'.PHP_EOL,
            ],
        ];
    }
}
