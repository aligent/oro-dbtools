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


use Aligent\DBToolsBundle\Helper\Compressor\Compressor;

class CompressionServiceProvider
{
    protected $compressors = [];

    /**
     * @param $type
     * @param Compressor $compressor
     */
    public function addCompressor($type, Compressor $compressor)
    {
        if (isset($this->compressors[$type])) {
            throw new \InvalidArgumentException("Compressor of $type already exists.");
        }

        $this->compressors[$type] = $compressor;
    }

    /**
     * @todo pull out to service
     * @param string $type
     * @return Compressor
     * @throws InvalidArgumentException
     */
    public function getCompressor($type)
    {
        if (!isset($this->compressors[$type])) {
            throw new \InvalidArgumentException("Compressor of $type does not exist.");
        }

        return $this->compressors[$type];
    }
}