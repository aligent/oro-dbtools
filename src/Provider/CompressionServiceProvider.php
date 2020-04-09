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


use Aligent\DBToolsBundle\Compressor\CompressorInterface;

class CompressionServiceProvider
{
    protected $compressors = [];

    /**
     * @param $type
     * @param CompressorInterface $compressor
     */
    public function addCompressor($type, CompressorInterface $compressor)
    {
        if (isset($this->compressors[$type])) {
            throw new \InvalidArgumentException("Compressor of $type already exists.");
        }

        $this->compressors[$type] = $compressor;
    }

    /**
     * @param string $type
     * @return CompressorInterface
     * @throws \InvalidArgumentException
     */
    public function getCompressor($type)
    {
        if (!isset($this->compressors[$type])) {
            throw new \InvalidArgumentException("Compressor of $type does not exist.");
        }

        return $this->compressors[$type];
    }
}