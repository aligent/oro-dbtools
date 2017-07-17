<?php

namespace Aligent\DBToolsBundle\Helper;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Database Settings Class
 *
 * @category  Aligent
 * @package   DBToolsBundle
 * @author    Adam Hall <adam.hall@aligent.com.au>
 * @copyright 2017 Aligent Consulting.
 * @license   https://opensource.org/licenses/mit MIT License
 * @link      http://www.aligent.com.au/
 **/

class DBSettings
{
    protected $name;
    protected $user;
    protected $password;
    protected $host;
    protected $port;
    protected $driver;

    /**
     * DBSettings constructor.
     * @param $filePath
     */
    function __construct($filePath)
    {
        if (file_exists($filePath)) {
            $configFile = Yaml::parse(file_get_contents($filePath));

            if (!array_key_exists("parameters", $configFile)) {
                throw new ParseException("parameters.yml is improperly formatted");
            }

            $parameters = $configFile['parameters'];

            $config = array_filter($parameters, function ($key) {
                return strpos($key, "database_") !== FALSE; //explicit type because the strings going to start at 0
            }, ARRAY_FILTER_USE_KEY);
        } else {
            throw new FileNotFoundException("parameters.yml could not be located at " . $filePath);
        }

        foreach ($config as $key => $value) {
            $newKey = str_replace('database_', '', $key);
            $this->$newKey = $value;
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param mixed $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param mixed $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return mixed
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @param mixed $driver
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
    }
}
