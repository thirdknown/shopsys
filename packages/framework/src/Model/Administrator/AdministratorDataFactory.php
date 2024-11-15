<?php

namespace Shopsys\FrameworkBundle\Model\Administrator;

class AdministratorDataFactory implements AdministratorDataFactoryInterface
{
    /**
     * @return \Shopsys\FrameworkBundle\Model\Administrator\AdministratorData
     */
    protected function createInstance(): AdministratorData
    {
        return new AdministratorData();
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Administrator\AdministratorData
     */
    public function create(): AdministratorData
    {
        return $this->createInstance();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Administrator\Administrator $administrator
     * @return \Shopsys\FrameworkBundle\Model\Administrator\AdministratorData
     */
    public function createFromAdministrator(Administrator $administrator): AdministratorData
    {
        $administratorData = $this->createInstance();
        $this->fillFromAdministrator($administratorData, $administrator);

        return $administratorData;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Administrator\AdministratorData $administratorData
     * @param \Shopsys\FrameworkBundle\Model\Administrator\Administrator $administrator
     */
    protected function fillFromAdministrator(AdministratorData $administratorData, Administrator $administrator)
    {
        $administratorData->email = $administrator->getEmail();
        $administratorData->realName = $administrator->getRealName();
        $administratorData->username = $administrator->getUsername();
        $administratorData->roles = $administrator->getRoles();
    }
}
