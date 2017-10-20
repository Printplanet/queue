<?php

namespace Printplanet\Component\Queue\Exception;

use Psr\Cache\InvalidArgumentException as Psr6CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInterface;

/**
 * Class InvalidArgumentException
 *
 * @package Printplanet\Component\Queue\Exception
 */
class InvalidArgumentException extends \InvalidArgumentException implements Psr6CacheInterface, SimpleCacheInterface
{
}