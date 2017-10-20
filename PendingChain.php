<?php

namespace Printplanet\Component\Queue;

class PendingChain
{
    /**
     * The class name of the job being dispatched.
     *
     * @var string
     */
    public $class;

    /**
     * The jobs to be chained.
     *
     * @var array
     */
    public $chain;

    /**
     * Create a new PendingChain instance.
     *
     * @param  string  $class
     * @param  array  $chain
     */
    public function __construct($class, $chain)
    {
        $this->class = $class;
        $this->chain = $chain;
    }

    /**
     * Dispatch the job with the given arguments.
     *
     * @return PendingDispatch
     */
    public function dispatch()
    {

        $reflect  = new \ReflectionClass($this->class);
        $job = $reflect->newInstanceArgs(func_get_args());

        $pendingDispatch = new PendingDispatch($job);

        return $pendingDispatch->chain($this->chain);
    }
}
