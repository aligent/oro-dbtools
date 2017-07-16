<?php

namespace Aligent\DBToolsBundle\Helper\Compressor;

abstract class AbstractCompressor implements Compressor
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
     * Check whether pv is installed
     *
     * @return bool
     */
    protected function hasPipeViewer()
    {
        $out = null;
        $return = null;
        @exec('which pv', $out, $return);

        return $return === 0;    }
}
