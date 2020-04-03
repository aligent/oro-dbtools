<?php
/**
 *
 *
 * @category  Aligent
 * @package
 * @author    Adam Hall <adam.hall@aligent.com.au>
 * @copyright 2020 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */

namespace Aligent\DBToolsBundle\Provider;

use Aligent\DBToolsBundle\Database\DatabaseConnectionInterface;
use Aligent\DBToolsBundle\Database\MysqlConnection;
use Aligent\DBToolsBundle\Database\PostgresConnection;

class DatabaseConnectionProvider
{
    /**
     * @var DatabaseConnectionInterface
     */
    protected $connection;

    /**
     * DatabaseConnectionProvider constructor.
     * @param string $name
     * @param string $user
     * @param string $password
     * @param string $host
     * @param string $port
     *
     */
    public function __construct(
        string $name,
        string $user,
        string $password,
        string $host,
        string $port,
        string $driver
    )
    {
        switch ($driver) {
            case MysqlConnection::DRIVER:
                $this->connection = new MysqlConnection($name, $user, $password, $host, $port);
                break;
            case PostgresConnection::DRIVER:
                $this->connection = new MysqlConnection($name, $user, $password, $host, $port);
                break;
            default:
                throw new \InvalidArgumentException('Unsupported Database Connection Type.');
        }
    }

    /**
     * @return DatabaseConnectionInterface
     */
    public function getConnection(): DatabaseConnectionInterface
    {
        return $this->connection;
    }
}