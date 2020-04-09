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

namespace Aligent\DBToolsBundle\Database;


use Ifsnop\Mysqldump\Mysqldump;

class PostgresConnection extends AbstractConnection
{
    const DRIVER = 'pgsql';

    /**
     * Returns a CLI execution string to connect to the database
     * @param string $command
     * @param array $args
     * @return string
     */
    public function getConnectionString(string $command, array $args = []): string
    {
        throw new \RuntimeException('Postgres Support has not been implemented yet.');
    }

    /**
     * Returns a JDBC Connection string
     * @return string
     */
    public function getJbdcConnectionString(): string
    {
        throw new \RuntimeException('Postgres Support has not been implemented yet.');
    }

    /**
     * Returns a PDO Connection String
     * @return string
     */
    public function getPdoConnectionString(): string
    {
        throw new \RuntimeException('Postgres Support has not been implemented yet.');
    }

    /**
     * Returns a PDO Connection
     * @return \PDO
     */
    public function getPDOConnection(): \PDO
    {
        throw new \RuntimeException('Postgres Support has not been implemented yet.');
    }

    /**
     * Returns an array of all the tables available in this database
     * @return string[]
     */
    public function getTables(): array
    {
        throw new \RuntimeException('Postgres Support has not been implemented yet.');
    }

    /**
     * @inheritDoc
     */
    public function getCreateDatabaseQuery(): string
    {
        throw new \RuntimeException('Postgres Support has not been implemented yet.');
    }

    /**
     * @inheritDoc
     */
    public function getDropDatabaseQuery(): string
    {
        throw new \RuntimeException('Postgres Support has not been implemented yet.');
    }

    /**
     * @inheritDoc
     */
    public function getDropTablesQuery(): string
    {
        throw new \RuntimeException('Postgres Support has not been implemented yet.');
    }

    /**
     * @inheritDoc
     */
    public function getDumper(array $dumpSettings): Mysqldump
    {
        throw new \RuntimeException('Postgres Support has not been implemented yet.');
    }
}