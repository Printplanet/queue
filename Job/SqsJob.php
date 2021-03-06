<?php

namespace Printplanet\Component\Queue\Job;

use Aws\Sqs\SqsClient;
use Printplanet\Component\Container\Container;

/**
 * Class SqsJob
 *
 * @package Printplanet\Component\Queue\Jobs
 */
class SqsJob extends Job implements JobsInterface
{
    /**
     * The Amazon SQS client instance.
     *
     * @var \Aws\Sqs\SqsClient
     */
    protected $sqs;

    /**
     * The Amazon SQS job data.
     *
     * @var array
     */
    protected $jobData;

    /**
     * Create a new job instance.
     *
     * @param  Container $container
     * @param  \Aws\Sqs\SqsClient $sqs
     * @param  array              $jobData
     * @param  string             $connectionName
     * @param  string             $queue
     */
    public function __construct(Container $container, SqsClient $sqs, array $jobData, $connectionName, $queue)
    {
        $this->sqs = $sqs;
        $this->jobData = $jobData;
        $this->queue = $queue;
        $this->container = $container;
        $this->connectionName = $connectionName;
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $this->sqs->changeMessageVisibility(array(
            'QueueUrl' => $this->queue,
            'ReceiptHandle' => $this->jobData['ReceiptHandle'],
            'VisibilityTimeout' => $delay,
        ));
    }

    /**
     * Delete the job from the queue.
     */
    public function delete()
    {
        parent::delete();

        $this->sqs->deleteMessage(array('QueueUrl' => $this->queue, 'ReceiptHandle' => $this->jobData['ReceiptHandle']));
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return (int) $this->jobData['Attributes']['ApproximateReceiveCount'];
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->jobData['MessageId'];
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->jobData['Body'];
    }

    /**
     * Get the underlying SQS client instance.
     *
     * @return \Aws\Sqs\SqsClient
     */
    public function getSqs()
    {
        return $this->sqs;
    }

    /**
     * Get the underlying raw SQS job.
     *
     * @return array
     */
    public function getSqsJob()
    {
        return $this->jobData;
    }
}
