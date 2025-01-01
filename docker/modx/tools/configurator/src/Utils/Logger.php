<?php

namespace App\Utils;

class Logger
{
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';

    /**
     * @param mixed $message
     * @return void
     */
    public static function debug($message): void
    {
        self::log($message, self::LEVEL_DEBUG);
    }

    /**
     * @param mixed $message
     * @return void
     */
    public static function info($message): void
    {
        self::log($message, self::LEVEL_INFO);
    }

    /**
     * @param mixed $message
     * @return void
     */
    public static function warning($message): void
    {
        self::log($message, self::LEVEL_WARNING);
    }

    /**
     * @param mixed $message
     * @return void
     */
    public static function error($message): void
    {
        self::log($message, self::LEVEL_ERROR);
    }

    /**
     * @param mixed $message
     * @param string $level
     * @return void
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
        ob_flush();
        flush();
    }
}
