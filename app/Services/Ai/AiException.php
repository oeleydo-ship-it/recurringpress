<?php

namespace App\Services\Ai;

use RuntimeException;

/** An AI feature failure whose message is safe to show the tenant. */
class AiException extends RuntimeException
{
}
