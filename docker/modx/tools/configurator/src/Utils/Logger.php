<?php

namespace App\Utils;

class Logger
{
    public const string LEVEL_DEBUG = 'debug';
    public const string LEVEL_INFO = 'info';
    public const string LEVEL_WARNING = 'warning';
    public const string LEVEL_ERROR = 'error';

    public static function debug($message, array $context = []): void
    {
        self::log($message, self::LEVEL_DEBUG, $context);
    }

    public static function info($message, array $context = []): void
    {
        self::log($message, self::LEVEL_INFO, $context);
    }

    public static function warning($message, array $context = []): void
    {
        self::log($message, self::LEVEL_WARNING, $context);
    }

    public static function error($message, array $context = []): void
    {
        self::log($message, self::LEVEL_ERROR, $context);
    }

    public static function log($message, string $level, array $context = []): void
    {
        $message = is_array($message) ? print_r($message, 1) : $message;
        if ($context !== []) {
            $message .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
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
