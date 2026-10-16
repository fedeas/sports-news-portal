<?php

namespace App\Services\SportsData;

use RuntimeException;

/**
 * The provider could not be reached, reported an error, or returned data we cannot use.
 *
 * Messages never contain credentials or raw provider responses.
 */
class SportsDataException extends RuntimeException {}
