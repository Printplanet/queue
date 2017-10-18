<?php

namespace Printplanet\Component\Queue\Repository;

use Printplanet\Component\Queue\Entity\DatabaseQueueEntityInterface;

/**
 * Interface DatabaseQueueInterface
 *
 * @package Printplanet\Component\Queue\Repository
 */
interface DatabaseQueueRepositoryInterface
{
    /**
     * Returns count of the jobs in given queue name.
     *
     * @param $queue
     *
     * @return int
     */
    public function getCount($queue);

    /**
     * @param $data
     *
     * @return DatabaseQueueEntityInterface $entity
     */
    public function createRecord(array $data);

    /**
     * @param DatabaseQueueEntityInterface $entity
     */
    public function delete(DatabaseQueueEntityInterface $entity);

    /**
     * @param DatabaseQueueEntityInterface[] $entities
     *
     * @return mixed
     */
    public function saveInBulk(array $entities);

    /**
     * @param DatabaseQueueEntityInterface $entity
     */
    public function save(DatabaseQueueEntityInterface $entity);

    /**
     * @param $queue
     * @param $retryAfter
     *
     * @return DatabaseQueueEntityInterface
     */
    public function getNextAvailableJob($queue, $retryAfter);

    /**
     * @param $id
     *
     * @return DatabaseQueueEntityInterface
     */
    public function findById($id);
}
