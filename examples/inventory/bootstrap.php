<?php
declare(strict_types=1);

/**
 * Common bootstrap for the mandryn inventory_db examples.
 *
 * Adjust the constants to match your local MySQL/MariaDB setup
 * (Laragon default: user root, empty password).
 */

const DB_HOST = '127.0.0.1';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'inventory_db';

require dirname(__DIR__, 2) . '/vendor/autoload.php';

/**
 * Tiny console output helper.
 */
function out(string $text = ''): void
{
    echo $text . PHP_EOL;
}
