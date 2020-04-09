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

namespace Aligent\DBToolsBundle\Sanitizer;

interface ValueSanitizerInterface
{
    /**
     * @param mixed $value
     * @return mixed
     */
    public function __invoke($value);
}