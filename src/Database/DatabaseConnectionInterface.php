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

interface DatabaseConnectionInterface
{
    /**
     * DatabaseConnectionInterface constructor.
     * @param string $name
     * @param string $user
     * @param string $password
     * @param string $host
     * @param string $port
     */
    public function __construct(
        string $name,
        string $user,
        string $password,
        string $host,
        string $port
    );

    /**
     * Returns a CLI execution string to connect to the database
     * @param string $command
     * @param array $args
     * @return string
     */
    public function getConnectionString(string $command, array $args = []): string;

    /**
     * Returns a JDBC Connection string
     * @return string
     */
    public function getJbdcConnectionString(): string;

    /**
     * Returns a PDO Connection String
     * @return string
     */
    public function getPdoConnectionString(): string;

    /**
     * Returns a PDO Connection
     * @return \PDO
     */
    public function getPDOConnection(): \PDO;

    /**
     * Returns an array of all the tables available in this database
     * @return string[]
     */
    public function getTables(): array;

    /**
     * Returns the query used to create the database if it does not already exist
     * @return string
     */
    public function getCreateDatabaseQuery(): string;

    /**
     * Returns the query used to drop the configured database
     * @return string
     */
    public function getDropDatabaseQuery(): string;

    /**
     * Returns the query used to drop all tables in the configured database
     * @return string
     */
    public function getDropTablesQuery(): string;

    /**
     * Returns a configured dumper object
     * @param array $dumpSettings
     * @throws \Exception
     * @return Mysqldump
     */
    public function getDumper(array $dumpSettings): Mysqldump;
}