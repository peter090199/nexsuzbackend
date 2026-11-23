<?php

namespace App\Services\CvImport;

use RuntimeException;

/**
 * A CV / resume file whose text could not be read. `reason` is a stable code the screen can react to; the message is
 * written for the job seeker (never a stack trace, never a file path).
 *
 *   unsupported : the file type is not one we can read
 *   corrupt     : the file is damaged or not really that type
 *   scanned     : a PDF with no text layer (a picture of a page)
 *   unreadable  : a PDF that does have text, but whose fonts give no way to turn it into letters
 *   empty       : readable, but there is hardly any text in it
 */
class CvDocumentException extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }
}
