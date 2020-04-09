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


class RandomValueSanitizer implements ValueSanitizerInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke($value)
    {
        return rand();
    }
}