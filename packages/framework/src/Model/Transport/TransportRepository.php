<?php

namespace Shopsys\FrameworkBundle\Model\Transport;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Model\Transport\Exception\TransportNotFoundException;

class TransportRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getTransportRepository()
    {
        return $this->em->getRepository(Transport::class);
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderForAll()
    {
        return $this->getTransportRepository()->createQueryBuilder('t')
            ->where('t.deleted = :deleted')->setParameter('deleted', false)
            ->orderBy('t.position')
            ->addOrderBy('t.id');
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Transport\Transport[]
     */
    public function getAll()
    {
        return $this->getQueryBuilderForAll()->getQuery()->getResult();
    }

    /**
     * @param array $transportIds
     * @return \Shopsys\FrameworkBundle\Model\Transport\Transport[]
     */
    public function getAllByIds(array $transportIds)
    {
        if (count($transportIds) === 0) {
            return [];
        }

        return $this->getQueryBuilderForAll()
            ->andWhere('t.id IN (:transportIds)')->setParameter('transportIds', $transportIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Transport\Transport[]
     */
    public function getAllByDomainId($domainId)
    {
        return $this->getQueryBuilderForAll()
            ->join(TransportDomain::class, 'td', Join::WITH, 't.id = td.transport AND td.domainId = :domainId')
            ->setParameter('domainId', $domainId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Transport\Transport[]
     */
    public function getAllIncludingDeleted()
    {
        return $this->getTransportRepository()->findAll();
    }

    /**
     * @param int $id
     * @return \Shopsys\FrameworkBundle\Model\Transport\Transport|null
     */
    public function findById($id)
    {
        return $this->getQueryBuilderForAll()
            ->andWhere('t.id = :transportId')->setParameter('transportId', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $id
     * @return \Shopsys\FrameworkBundle\Model\Transport\Transport
     */
    public function getById($id)
    {
        $transport = $this->findById($id);

        if ($transport === null) {
            throw new TransportNotFoundException(
                'Transport with ID ' . $id . ' not found.'
            );
        }

        return $transport;
    }

    /**
     * @param string $uuid
     * @return \Shopsys\FrameworkBundle\Model\Transport\Transport
     */
    public function getOneByUuid(string $uuid): Transport
    {
        $transport = $this->getTransportRepository()->findOneBy(['uuid' => $uuid]);

        if ($transport === null) {
            throw new TransportNotFoundException('Transport with UUID ' . $uuid . ' does not exist.');
        }

        return $transport;
    }

    /**
     * @param string $uuid
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Transport\Transport
     */
    public function getEnabledOnDomainByUuid(string $uuid, int $domainId): Transport
    {
        $queryBuilder = $this->getTransportRepository()->createQueryBuilder('t')
            ->join(TransportDomain::class, 'td', Join::WITH, 't.id = td.transport AND td.domainId = :domainId')
            ->setParameter('domainId', $domainId)
            ->where('t.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->andWhere('t.deleted = false')
            ->andWhere('td.enabled = true')
            ->andWhere('t.hidden = false');

        $transport = $queryBuilder->getQuery()->getOneOrNullResult();

        if ($transport === null) {
            throw new TransportNotFoundException('Transport with UUID ' . $uuid . ' does not exist.');
        }

        return $transport;
    }
}
