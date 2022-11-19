<?php

namespace Education\Mapper;

use Application\Mapper\BaseMapper;
use Education\Model\Course as CourseModel;

/**
 * Mappers for Courses.
 */
class Course extends BaseMapper
{
    /**
     * Find a course by code.
     *
     * @param string $code
     *
     * @return CourseModel|null
     */
    public function findByCode(string $code): ?CourseModel
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('c, e')
            ->from($this->getRepositoryName(), 'c')
            ->where('c.code = ?1')
            ->leftJoin('c.documents', 'e');
        $qb->setParameter(1, $code);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Search for courses.
     *
     * @param string $query
     *
     * @return array
     */
    public function search(string $query): array
    {
        $query = '%' . $query . '%';
        $qb = $this->getRepository()->createQueryBuilder('c');

        $qb->where('c.code LIKE ?1')
            ->orWhere('c.name LIKE ?1');
        $qb->setParameter(1, $query);

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return CourseModel::class;
    }
}
