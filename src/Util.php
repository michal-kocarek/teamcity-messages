<?php

namespace MichalKocarek\TeamcityMessages;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use LogicException;

class Util
{
    const ESCAPE_CHARACTER_MAP = [
        '\'' => '|\'',
        "\n" => '|n',
        "\r" => '|r',
        '|' => '||',
        '[' => '|[',
        ']' => '|]',
    ];

    const TIMESTAMP_FORMAT = 'Y-m-d\TH:i:s.uO';

    /**
     * Return arbitrary message formatted according the TeamCity message protocol.
     *
     * Both message name and property names should be valid Java IDs.
     * Property values are automatically escaped.
     *
     * @param string $messageName The message name.
     * @param array $properties Associative array of property names mapping to their respective values.
     * @return string
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-servMsgsServiceMessages TeamCity – Service Messages
     */
    public static function format($messageName, array $properties = [])
    {
        self::ensureValidJavaId($messageName);

        $result = '##teamcity[' . $messageName;
        foreach ($properties as $propertyName => $propertyValue) {
            $escapedValue = self::escape($propertyValue);

            if (is_int($propertyName)) {
                // Value without name; skip the key and dump just the value

                $result .= " '$escapedValue'";
            } else {
                // Classic name=value pair

                self::ensureValidJavaId($propertyName);
                $result .= " $propertyName='$escapedValue'";
            }

        }
        $result .= ']'.PHP_EOL;

        return $result;
    }

    /**
     * Checks if given value is valid Java ID.
     *
     * Valid Java ID starts with alpha-character and continues with mix of alphanumeric characters and `-`.
     *
     * @param string $value
     * @return bool
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-servMsgsServiceMessages TeamCity – Service Messages
     */
    public static function ensureValidJavaId($value)
    {
        if (!preg_match('/^[a-z][-a-z0-9]+$/i', $value)) {
            throw new InvalidArgumentException("Value '$value' is not valid Java ID.");
        }
    }

    /**
     * Return date in format acceptable as TeamCity "timestamp" parameter.
     *
     * @param DateTimeInterface $date Either date with timestamp or `NULL` for now.
     * @return string
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-MessageCreationTimestamp
     */
    public static function formatTimestamp(DateTimeInterface $date = null)
    {
        if (!$date) {
            $date = self::nowMicro();
        }
        
        return $date->format(self::TIMESTAMP_FORMAT);
    }

    /**
     * Escape the value.
     *
     * @param string $value
     * @return string
     * @see https://confluence.jetbrains.com/display/TCD9/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-servMsgsServiceMessages TeamCity – Service Messages
     */
    private static function escape($value)
    {
        return preg_replace_callback('/([\'\n\r|[\]])|\\\\u(\d{4})/', function($matches) {
            if ($matches[1]) {
                return self::ESCAPE_CHARACTER_MAP[$matches[1]];
            } elseif ($matches[2]) {
                return '|0x'.$matches[2];
            } else {
                throw new LogicException('Unexpected value.');
            }
        }, $value);
    }

    /**
     * Get current time with microseconds included.
     *
     * @return DateTimeImmutable
     */
    public static function nowMicro()
    {
        // This is kinda hacky way, because you can't simply ask for DateTime with microseconds in PHP

        list($microseconds, $timestamp) = explode(' ', microtime());

        // extract first six microsecond digits from string "0.12345678" as DateTime won't accept more
        $microseconds = substr($microseconds, 2, 6);

        // order of format matters; timestamp sets timezone and set microseconds to zero;
        // that's why microseconds must be set after parsing the timestamp
        return DateTimeImmutable::createFromFormat('U u', "$timestamp $microseconds");
    }

}
