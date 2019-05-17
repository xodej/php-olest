<?php

declare(strict_types=1);

namespace Xodej\Olest;

use Xodej\Olapi\Connection;

/**
 * Class ConnectionSingleton.
 */
class ConnectionSingleton
{
    /** @var Connection[] */
    protected static $connections = [];

    /**
     * @param string $connection_id
     * @param string $host_with_port
     * @param string $username
     * @param string $password
     *
     * @throws \ErrorException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return Connection
     */
    public static function getConnection(
        string $connection_id,
        string $host_with_port,
        string $username,
        string $password
    ): Connection {
        if (isset(self::$connections[$connection_id])) {
            return self::$connections[$connection_id];
        }

        self::$connections[$connection_id] = new Connection($host_with_port, $username, $password);

        return self::$connections[$connection_id];
    }

    public static function clear(): void
    {
        self::$connections = [];
    }

    /**
     * @param string $connection_id
     *
     * @return bool
     */
    public static function delete(string $connection_id): bool
    {
        if (isset(self::$connections[$connection_id])) {
            unset(self::$connections[$connection_id]);

            return true;
        }

        return false;
    }
}
