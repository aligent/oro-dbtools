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


class Sanitizer
{
    /**
     * @var ValueSanitizerInterface[]
     */
    protected $sanitizers;

    /**
     * Sanitizer constructor.
     */
    public function __construct()
    {
        $this->sanitizers = [
            'attachment' => new AttachmentSanitizer(),
            'datetime' => new DateTimeSanitizer(),
            'email' => new EmailSanitizer(),
            'md5' => new MD5Sanitizer(),
            'password' => new PasswordSanitizer(),
            'random' => new RandomValueSanitizer(),
        ];
    }

    /**
     * @param string $function
     * @param $value
     * @return string
     */
    public function sanitize(string $function, $value): string
    {
        if (!isset($this->sanitizers[$function])) {
            throw new \InvalidArgumentException("$function sanitizer does not exist.");
        }

        $beforeType = gettype($value);
        $sanitizedValue = $this->sanitizers[$function]($value);

        if ($beforeType !== gettype($sanitizedValue)) {
            throw new \InvalidArgumentException(
                "$function has altered the type of this value. This can cause unexpected behaviour when attempting to import the database. Please choose another a sanitization function."
            );
        }

        return $sanitizedValue;
    }
}