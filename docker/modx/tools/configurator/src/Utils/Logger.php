<?php

namespace App\Utils;

class Logger
{
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';

    /**
     */
    public static function debug($message): void
    {
        self::log($message, self::LEVEL_DEBUG);
    }

    /**
     */
    public static function info($message): void
    {
        self::log($message, self::LEVEL_INFO);
    }

    /**
     */
    public static function warning($message): void
    {
        self::log($message, self::LEVEL_WARNING);
    }

    /**
     */
    public static function error($message): void
    {
        self::log($message, self::LEVEL_ERROR);
    }

    /**
     */
    public static function log($message, string $level): void
    {
        $message = is_array($message) ? print_r($message, 1) : $message;
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = sprintf("[%s] [%s] %s\n", $timestamp, strtoupper($level), $message);
        if ($level === self::LEVEL_ERROR) {
            fwrite(STDERR, $formattedMessage);
        } else {
            echo $formattedMessage;
        }
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
