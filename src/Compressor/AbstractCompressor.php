<?php

namespace Aligent\DBToolsBundle\Compressor;

abstract class AbstractCompressor implements CompressorInterface
{
    /**
     * @inheritdoc
     */
    abstract public function getCompressingCommand($command, $pipe = true);

    /**
     * @inheritdoc
     */
    abstract public function getDecompressingCommand($command, $fileName, $pipe = true);

    /**
     * @inheritdoc
     */
    abstract public function getFileName($fileName, $pipe = true);

    /**
     * @Todo replace with Symfony Process
     * Check whether pv is installed
     *
     * @return bool
     */
    protected function hasPipeViewer()
    {
        $out = null;
        $return = null;
        @exec('which pv', $out, $return);

        return $return === 0;
    }
}
