<?php

namespace App\Repository;

use App\Data\SearchData;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    /**
     * @var PaginatorInterface
     */
    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Product::class);
        $this->paginator = $paginator;
    }

    /**
     * Get all products in link with a search
     * @param SearchData $search
     * @return PaginationInterface
     */
    public function findSearch(SearchData $search): PaginationInterface
    {
        $query = $this->getSearchQuery($search)->getQuery();
        return $this->paginator->paginate(
            $query,
            $search->page,
            9
        );
    }

    /**
     * Get minimum price and maximum price from a search
     * @param SearchData $search
     * @return integer[]
     */
    public function findMinMax(SearchData $search): array
    {
        $results = $this->getSearchQuery($search)
            ->select('MIN(p.price) as min, MAX(p.price) as max')
            ->getQuery()
            ->getScalarResult();

        return [(int)$results[0]['min'], (int)$results[0]['max']];
    }

    private function getSearchQuery(SearchData $search): QueryBuilder
    {
        $query = $this->createQueryBuilder('p')
            ->select('c', 'p')
            ->join('p.categories', 'c');

        if(!empty($search->q)) {
            $query = $query
                ->andWhere('p.name LIKE :q')
                ->setParameter('q', "%{$search->q}%")
                ->orderBy('p.name', 'ASC');

        }

        if(!empty($search->min)) {
            $query = $query
                ->andWhere('p.price >= :min')
                ->setParameter('min', $search->min)
                ->orderBy('p.price', 'ASC');
        }
        if(!empty($search->max)) {
            $query = $query
                ->andWhere('p.price <= :max')
                ->setParameter('max', $search->max)
                ->orderBy('p.price', 'ASC');
        }
        if(!empty($search->promo)) {
            $query = $query
                ->andWhere('p.promo = 1')
                ->orderBy('p.promo', 'ASC');
        }
        if(!empty($search->categories)) {
            $query = $query
                ->andWhere('c.id IN (:categories)')
                ->setParameter('categories', $search->categories);
        }

        return $query;
    }
}
