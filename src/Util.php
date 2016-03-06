<?php

namespace Bileto\TeamcityMessages;

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

    const TIMESTAMP_FORMAT = 'Y-m-d\TH:i:sO';

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
            self::ensureValidJavaId($propertyName);

            $result .= ' ' . $propertyName . "='" . self::escape($propertyValue) . "'";
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
    private static function ensureValidJavaId($value)
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
            $date = new DateTimeImmutable();
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

}
