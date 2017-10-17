<?php

namespace PP\Component\Queue;

/**
 * Class QueueErrorInterface
 *
 * @package PP\Component\Queue
 */
interface QueueErrorInterface
{
    /**
     * @param \Exception $e
     * @param mixed      $payload
     */
    public function failed(\Exception $e, $payload = null);
}