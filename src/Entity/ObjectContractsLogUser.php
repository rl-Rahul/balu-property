<?php

namespace App\Entity;

use App\Repository\ObjectContractsLogUserRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Base\Base;

/**
 * @ORM\Entity(repositoryClass=ObjectContractsLogUserRepository::class)
 */
class ObjectContractsLogUser extends Base
{

    /**
     * @ORM\ManyToOne(targetEntity=ObjectContractsLog::class, inversedBy="objectContractsLogUsers")
     */
    private ?ObjectContractsLog $log;

    /**
     * @ORM\ManyToOne(targetEntity=UserIdentity::class)
     */
    private ?UserIdentity $user;

    /**
     * @ORM\ManyToOne(targetEntity=ObjectContracts::class)
     */
    private ?ObjectContracts $contract;
    
    /**
     * 
     * @return ObjectContractsLog|null
     */
    public function getLog(): ?ObjectContractsLog
    {
        return $this->log;
    }
    
    /**
     * 
     * @param ObjectContractsLog|null $log
     * @return self
     */
    public function setLog(?ObjectContractsLog $log): self
    {
        $this->log = $log;

        return $this;
    }
    
    /**
     * 
     * @return UserIdentity|null
     */
    public function getUser(): ?UserIdentity
    {
        return $this->user;
    }
    
    /**
     * 
     * @param UserIdentity|null $user
     * @return self
     */
    public function setUser(?UserIdentity $user): self
    {
        $this->user = $user;

        return $this;
    }
    
    /**
     * 
     * @return ObjectContracts|null
     */
    public function getContract(): ?ObjectContracts
    {
        return $this->contract;
    }
    
    /**
     * 
     * @param ObjectContracts|null $contract
     * @return self
     */
    public function setContract(?ObjectContracts $contract): self
    {
        $this->contract = $contract;

        return $this;
    }
}
