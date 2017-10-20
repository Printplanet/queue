<?php

namespace Printplanet\Component\Queue;

/**
 * Class QueueErrorInterface
 *
 * @package Printplanet\Component\Queue
 */
interface QueueErrorInterface
{
    /**
     * @param \Exception $e
     * @param mixed      $payload
     */
    public function failed(\Exception $e, $payload = null);
}