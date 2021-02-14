<?php

declare(strict_types=1);

namespace Xodej\Olest;

use Xodej\Olapi\Connection;

/**
 * Class ConnectionFactory.
 */
class ConnectionFactory
{
    /** @var Connection[] */
    private static array $connections = [];

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
     * @return bool
     * @throws \Exception
     */
    public static function delete(string $connection_id): bool
    {
        if (isset(self::$connections[$connection_id])) {
            self::$connections[$connection_id]->close();
            unset(self::$connections[$connection_id]);

            return true;
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function __destruct()
    {
        foreach (self::$connections as $connection) {
            $connection->close();
        }
        self::$connections = [];
    }
}
