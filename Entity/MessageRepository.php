<?php

declare(strict_types=1);

namespace Monolith\Module\WebForm\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class MessageRepository extends EntityRepository
{
    /**
     * @param WebForm $webForm
     * @param int     $status
     *
     * @return Query
     */
    public function getFindByStatusQuery(WebForm $webForm, int $status): Query
    {
        $qb = $this
            ->createQueryBuilder('m')
            ->where('m.web_form = :web_form')
            ->andWhere('m.status = :status')
            ->orderBy('m.id', 'ASC')
            ->setParameter('status', $status)
            ->setParameter('web_form', $webForm)
        ;

        return $qb->getQuery();
    }

    /**
     * @param WebForm $webForm
     * @param int     $status
     *
     * @return int
     */
    public function getCountByStatus(WebForm $webForm, int $status): int
    {
        $qb = $this
            ->createQueryBuilder('m')
            ->select('count(m.id)')
            ->where('m.web_form = :web_form')
            ->andWhere('m.status = :status')
            ->orderBy('m.id', 'ASC')
            ->setParameter('status', $status)
            ->setParameter('web_form', $webForm)
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
