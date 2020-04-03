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


use PDO;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class MysqlConnection extends AbstractConnection
{
    const DRIVER = 'pdo_mysql';

    /**
     * @var PDO
     */
    protected $connection;

    /**
     * Cached Tables array
     * @var array $tables
     */
    protected $tables;

    /**
     * @var array
     */
    protected $resolvedTables;

    /**
     * Returns a CLI execution string to connect to the database
     * @param string $command
     * @param array $args
     * @return string
     */
    public function getConnectionString(string $command = 'mysql', array $args = []): string
    {
        $segments = [
            $command,
            '-h ' . escapeshellarg($this->getHost()),
            '-u ' . escapeshellarg($this->getUser()),
        ];

        if (null !== $this->getPort()) {
            $segments[] = '-P' . escapeshellarg($this->getPort());
        }

        if (strlen($this->getPassword())) {
            $segments[] = '-p' . escapeshellarg($this->getPassword());
        }

        // add all the extra before database name
        foreach ($args as $arg) {
            $segments[] = $arg;
        }

        if (strlen($this->getName())) {
            $segments[] = escapeshellarg($this->getName());
        }

        return implode(' ', $segments);
    }

    /**
     * Returns a JDBC Connection string
     * @return string
     */
    public function getJbdcConnectionString(): string
    {
        $connectionString = sprintf('jbdc:mysql://%s:%s/%s?username=%s', $this->getHost(), $this->getPort(),
            $this->getName(), $this->getUser());

        if (strlen($this->getPassword())) {
            $connectionString .= '&password=' . $this->getPassword();
        }

        return $connectionString;
    }

    /**
     * Returns a PDO Connection String
     * @return string
     */
    public function getPdoConnectionString(): string
    {
        return sprintf('mysql:host=%s;port=%s;dbname=%s', $this->getHost(), $this->getPort(), $this->getName());
    }

    /**
     * Returns a PDO Connection
     * @return PDO
     */
    public function getPDOConnection(): PDO
    {
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('pdo_mysql extension is not installed');
        }

        if ($this->connection) {
            return $this->connection;
        }

        $database = $this->getName();

        $dsn = sprintf('mysql:host=%s;port=%s', $this->getHost(), $this->getPort());

        $connection = new PDO($dsn, $this->getUser(), $this->getPassword());

        /** @link http://bugs.mysql.com/bug.php?id=18551 */
        $connection->query("SET SQL_MODE=''");

        try {
            $connection->query('USE ' . $this->quoteIdentifier($database));
        } catch (PDOException $e) {
            $message = sprintf("Unable to use database '%s': %s %s", $database, get_class($e), $e->getMessage());
            throw new RuntimeException($message, 0, $e);
        }

        $connection->query("SET NAMES utf8");

        $connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->connection = $connection;

        return $this->connection;
    }

    /**
     * Returns an array of all the tables available in this database
     * @return string[]
     */
    public function getTables(): array
    {
        if ($this->tables !== null) {
            return $this->tables;
        }

        $db = $this->getPDOConnection();

        $columnName = 'table_name';
        $column = $columnName;

        $input = [];

        $condition = 'table_schema = database()';

        $query = sprintf('SELECT %s FROM information_schema.tables WHERE %s;', $column, $condition);
        $statement = $db->prepare($query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $result = $statement->execute($input);

        if (!$result) {
            $this->throwRuntimeException($statement,
                sprintf('Failed to obtain tables from database: %s', var_export($query, true)));
        }

        $result = $statement->fetchAll(PDO::FETCH_COLUMN, 0);

        return $result;
    }

    /**
     * @param array $list
     * @param array $definitions
     * @param array $resolved
     * @return array
     */
    public function resolveTables(array $list, array $definitions = [], array $resolved = []): array {
        if ($this->resolvedTables === null) {
            $this->resolvedTables = $this->getTables(true);
        }

        $resolvedList = [];
        foreach ($list as $entry) {
            if (substr($entry, 0, 1) == '@') {
                $code = substr($entry, 1);
                if (!isset($definitions [$code])) {
                    throw new RuntimeException('Table-groups could not be resolved: ' . $entry);
                }
                if (!isset($resolved[$code])) {
                    $resolved[$code] = true;
                    $tables = $this->resolveTables(
                        explode(' ', $definitions[$code]['tables']),
                        $definitions,
                        $resolved
                    );
                    $resolvedList = array_merge($resolvedList, $tables);
                }
                continue;
            }

            // resolve wildcards
            if (strpos($entry, '*') !== false) {
                $connection = $this->getPDOConnection();
                $sth = $connection->prepare(
                    'SHOW TABLES LIKE :like',
                    [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
                );
                $sth->execute(
                    [':like' => str_replace('*', '%', $entry)]
                );
                $rows = $sth->fetchAll();
                foreach ($rows as $row) {
                    $resolvedList[] = $row[0];
                }
                continue;
            }

            if (in_array($entry, $this->resolvedTables)) {
                $resolvedList[] = $entry;
            }
        }

        asort($resolvedList);
        $resolvedList = array_unique($resolvedList);

        return $resolvedList;
    }

    /**
     * @return string
     */
    public function getCreateDatabaseQuery(): string
    {
        return 'CREATE DATABASE IF NOT EXISTS `' . $this->getName() . '`;';
    }

    /**
     * Returns the query used to drop the configured database
     * @return string
     */
    public function getDropDatabaseQuery(): string
    {
        return 'DROP DATABASE `' . $this->getName() . '`;';
    }

    /**
     * Returns the query used to drop all tables in the configured database
     * @return string
     */
    public function getDropTablesQuery(): string
    {
        $query = "SET FOREIGN_KEY_CHECKS = 0;\n";
        $tables = $this->getTables();

        foreach ($tables as $table) {
            $query .= 'DROP TABLE IF EXISTS ' . $this->getName() . ".$table;\n";
        }

        $query .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        return $query;
    }

    /**
     * @return string
     */
    public function getDumpDatabaseCommand(): string
    {
        return $this->getConnectionString('mysqldump') .
            ' | LANG=C LC_CTYPE=C LC_ALL=C sed -e ' . escapeshellarg('s/DEFINER[ ]*=[ ]*[^*]*\*/\*/');
    }

    /**
     * Mysql quoting of an identifier
     *
     * @param string $identifier UTF-8 encoded
     *
     * @return string quoted identifier
     */
    protected function quoteIdentifier($identifier): string
    {
        $quote = '`'; // le backtique

        $pattern = '~^(?:[\x1-\x7F]|[\xC2-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2})+$~';

        if (!preg_match($pattern, $identifier)) {
            throw new InvalidArgumentException(sprintf('Invalid identifier, must not contain NUL and must be UTF-8 encoded in the BMP: %s (hex: %s)',
                var_export($identifier), bin2hex($identifier)));
        }

        return $quote . strtr($identifier, [$quote => $quote . $quote]) . $quote;
    }

    /**
     * throw a runtime exception and provide error info for the statement if available
     *
     * @param PDOStatement $statement
     * @param string $message
     *
     * @throws RuntimeException
     */
    protected function throwRuntimeException(PDOStatement $statement, $message = ""): string
    {
        $reason = $statement->errorInfo() ? vsprintf('SQLSTATE[%s]: %s: %s',
            $statement->errorInfo()) : 'no error info for statement';

        if (strlen($message)) {
            $message .= ': ';
        } else {
            $message = '';
        }

        throw new RuntimeException($message . $reason);
    }
}