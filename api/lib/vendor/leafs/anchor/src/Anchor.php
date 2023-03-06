<?php

declare(strict_types=1);

namespace Leaf;

/**
 * Leaf Security Module
 * ---------------------------------
 * Simple to use security based utility methods
 *
 * @author Michael Darko <mickdd22@gmail.com>
 * @since v2.2
 * @version 1.0
 */
class Anchor
{
    protected static $config = [
        'SECRET_KEY' => '_token',
        'SECRET' => '@nkor_leaf$0Secret!',
        'EXCEPT' => [],
        'METHODS' => ['POST', 'PUT', 'PATCH', 'DELETE'],
    ];

    protected static $errors = [];

    /**
     * Manage config for leaf anchor
     *
     * @param array|null $config The config to set
     */
    public static function config($config = null)
    {
        if ($config === null) {
            return static::$config;
        }

        static::$config = array_merge(static::$config, $config);
    }

    /**
     * Escape malicious characters
     *
     * @param mixed $data The data to sanitize.
     */
    public static function sanitize($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[is_string($key) ? self::sanitize($key) : $key] = self::sanitize($value);
            }
        }

        if (is_string($data)) {
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }

        return $data;
    }

    /**
     * Get an item or items from an array of data.
     * 
     * @param array $dataSource An array of data to search through
     * @param string|array $item The items to return
     */
    public static function deepGet($dataSource, $item = null)
    {
        if (!$item) {
            return $dataSource;
        }

        $output = [];

        if (is_array($item)) {
            foreach ($item as $dataItem) {
                $output[$dataItem] = $dataSource[$dataItem] ?? null;
            }
        } else {
            $output = $dataSource[$item] ?? null;
        }

        return $output;
    }

    /**
     * Convert string to boolean. Created due to inconsistencies in PHP's boolval and (bool)
     * 
     * @param string $value The value to convert
     * @return bool
     */
    public static function toBool($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower($value);

            if ($value === 'true' || $value === '1') {
                return true;
            }

            if ($value === 'false' || $value === '0') {
                return false;
            }
        }

        return (bool) $value;
    }

    /**
     * Generate a token for identifying your application
     *
     * @param int $strength Number of random characters to attach to token
     */
    public static function generateToken(int $strength = 16): string
    {
        return bin2hex(static::$config['SECRET'] . '.' . random_bytes($strength));
    }

    public static function errors(): array
    {
        return static::$errors;
    }
}
