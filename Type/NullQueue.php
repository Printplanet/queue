<?php

namespace Printplanet\Component\Queue\Type;

/**
 * Class NullQueue
 *
 * @package Printplanet\Component\Queue\Types
 */
class NullQueue extends Queue implements QueueInterface
{
    /**
     * @inheritDoc
     */
    public function size($queue = null)
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function push($job, $data = '', $queue = null)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function pushRaw($payload, $queue = null, array $options = array())
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function pop($queue = null)
    {
        //
    }
}