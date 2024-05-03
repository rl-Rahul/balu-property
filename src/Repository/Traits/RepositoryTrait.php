<?php

namespace App\Repository\Traits;

use App\Utils\Constants;

trait RepositoryTrait
{
    /**
     * Function to do pagination logic
     *
     * @param object $qb
     * @param int|null $startPage
     * @param int|null $count
     *
     * @return array
     */
    private function handlePagination(object $qb, int $startPage = null, int $count = null): array
    {
        if ($startPage === '' || !is_numeric($startPage) || $startPage <= 0) {
            $startPage = null;
        }
        if (is_numeric($count) && $count > 0) {
            $offset = 0;
            if ($startPage > 1) {
                $offset = ($startPage * $count) - $count;
            }
            $qb->setMaxResults($count ? $count : null)
                ->setFirstResult($offset);
        } elseif (Constants::DEFAULT_MAX_RESULT_COUNT) {
            $qb->setMaxResults(Constants::DEFAULT_MAX_RESULT_COUNT);
        }
        return $qb->distinct()->getQuery()->getResult();
    }
     
    /**
     * Function to do count logic
     *
     * @param object $qb 
     * @param string $field 
     *
     * @return array
     */
    private function handleCount(object $qb, string $field): array
    { 
        $qb->select("count($field) as count");
        return $qb->getQuery()->getSingleResult();   
    }
    
    /**
     * Function to  generate regex from text
     * 
     * @param string $text 
     *
     * @return string
     */
    private function generateRegex(string $text): string
    {    
        return implode('|', explode(' ', str_replace([ '/', '*'], '', $text)));
    }
    
    /**
     * Search with id
     *
     * @param object $qb 
     * @param string $text 
     * @param string $field 
     *
     * @return object || null
     */
    private function searchWithId(object $qb, string $text, string $field): ?object
    { 
        if (is_numeric($text) || (is_numeric(str_replace('#', '', $text)))) {
            return $qb->andWhere($field . ' = :id')
                            ->setParameter('id', ltrim(str_replace('#', '', $text), "0"));
        }

        return null;
    }

}
