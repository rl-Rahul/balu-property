<?php


namespace App\Entity\Base;

/**
 * Represents the interface that all entities must implement.
 *
 * This interface is useful because all entities can eliminate redundant codes
 *
 *
 * @author Rahul R L <rahul.rl@pitsolutions.com>
 */
interface FixedDataInterface
{
    /**
     * Returns the created date of an entity
     */
    public function getCreatedAt();

    /**
     * Returns the created date of an entity
     */
    public function getUpdatedAt();

    /**
     * Returns the deleted status of an entity
     */
    public function getDeleted();
}