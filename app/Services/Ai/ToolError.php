<?php

namespace App\Services\Ai;

/** A tool call the model got wrong (unknown client, bad date...). Reported back to the model. */
class ToolError extends \RuntimeException
{
}
