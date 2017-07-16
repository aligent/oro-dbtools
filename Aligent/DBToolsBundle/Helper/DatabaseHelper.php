<?php
/**
 * Created by PhpStorm.
 * User: adam.hall
 * Date: 7/13/17
 * Time: 1:38 PM
 */

namespace Aligent\DBToolsBundle\Helper;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

class DatabaseHelper
{
    /** @var $_connection PDO */
    private $_connection;

    /** @var $_tables array  - cached tables*/
    private $_tables;

    /** @var $settings DBSettings */
    public $settings;

    function __construct(DBSettings $settings) {
        $this->settings = $settings;
    }

    public function getMysqlConnectionString($command = "mysql", $args = array()) {
        $segments = array(
            $command,
            '-h ' . escapeshellarg($this->settings->getHost()),
            '-u ' . escapeshellarg($this->settings->getUser())
        );

        if (null !== $this->settings->getPort()) {
            $segments[] = '-P' . escapeshellarg($this->settings->getPort());
        }

        if (strlen($this->settings->getPassword())) {
            $segments[] = '-p' . escapeshellarg($this->settings->getPassword());
        }

        // add all the extra before database name
        foreach ($args as $arg) {
            $segments[] = $arg;
        }

        if (strlen($this->settings->getName())) {
            $segments[] = escapeshellarg($this->settings->getName());
        }

        return implode(' ', $segments);
    }

    public function getJbdcConnectionString() {
        $connectionString = sprintf(
            'jbdc:mysql://%s:%s/%s?username=%s',
                $this->settings->getHost(),
                $this->settings->getPort(),
                $this->settings->getName(),
                $this->settings->getUser()
            );

        if (strlen($this->settings->getPassword())) {
            $connectionString .= '&password=' . $this->settings->getPassword();
        }

        return $connectionString;
    }

    public function getPdoConnectionString(){
        return sprintf(
          'mysql:host=%s;port=%s;dbname=%s',
          $this->settings->getHost(),
          $this->settings->getPort(),
          $this->settings->getName()
        );
    }

    public function getConnection() {
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('pdo_mysql extension is not installed');
        }

        if($this->_connection) {
            return $this->_connection;
        }

        $database = $this->settings->getName();

        $connection = new PDO(
            $this->getPdoConnectionString(),
            $this->settings->getUser(),
            $this->settings->getPassword()
        );

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

        $this->_connection = $connection;
        return $this->_connection;
    }

    public function getTables() {
        if ($this->_tables !== null) {
            return $this->_tables;
        }

        $db = $this->getConnection();

        $columnName = 'table_name';
        $column = $columnName;

        $input = array();

        $condition = 'table_schema = database()';

        $query = sprintf('SELECT %s FROM information_schema.tables WHERE %s;', $column, $condition);
        $statement = $db->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $result = $statement->execute($input);

        if (!$result) {
            // @codeCoverageIgnoreStart
            $this->throwRuntimeException(
                $statement,
                sprintf('Failed to obtain tables from database: %s', var_export($query, true))
            );
        } // @codeCoverageIgnoreEnd

        $result = $statement->fetchAll(PDO::FETCH_COLUMN, 0);

        return $result;
    }

    public function resolveTables(array $list, array $definitions = array(), array $resolved = array()) {
        if ($this->_tables === null) {
            $this->_tables = $this->getTables(true);
        }

        $resolvedList = array();
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
                $connection = $this->getConnection();
                $sth = $connection->prepare(
                    'SHOW TABLES LIKE :like',
                    array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY)
                );
                $sth->execute(
                    array(':like' => str_replace('*', '%', $entry))
                );
                $rows = $sth->fetchAll();
                foreach ($rows as $row) {
                    $resolvedList[] = $row[0];
                }
                continue;
            }

            if (in_array($entry, $this->_tables)) {
                $resolvedList[] = $entry;
            }
        }

        asort($resolvedList);
        $resolvedList = array_unique($resolvedList);

        return $resolvedList;
    }

    /**
     * Mysql quoting of an identifier
     *
     * @param string $identifier UTF-8 encoded
     *
     * @return string quoted identifier
     */
    private function quoteIdentifier($identifier) {
        $quote = '`'; // le backtique

        $pattern = '~^(?:[\x1-\x7F]|[\xC2-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2})+$~';

        if (!preg_match($pattern, $identifier)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid identifier, must not contain NUL and must be UTF-8 encoded in the BMP: %s (hex: %s)',
                    var_export($identifier), bin2hex($identifier)
                )
            );
        }

        return $quote . strtr($identifier, array($quote => $quote . $quote)) . $quote;
    }

    /**
     * throw a runtime exception and provide error info for the statement if available
     *
     * @param PDOStatement $statement
     * @param string $message
     *
     * @throws RuntimeException
     */
    private function throwRuntimeException(PDOStatement $statement, $message = "") {
        $reason = $statement->errorInfo()
            ? vsprintf('SQLSTATE[%s]: %s: %s', $statement->errorInfo())
            : 'no error info for statement';

        if (strlen($message)) {
            $message .= ': ';
        } else {
            $message = '';
        }

        throw new RuntimeException($message . $reason);
    }

}
