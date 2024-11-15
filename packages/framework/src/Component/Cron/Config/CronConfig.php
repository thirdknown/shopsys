<?php

namespace Shopsys\FrameworkBundle\Component\Cron\Config;

use DateTimeInterface;
use Shopsys\FrameworkBundle\Component\Cron\Config\Exception\CronModuleConfigNotFoundException;
use Shopsys\FrameworkBundle\Component\Cron\CronTimeResolver;
use Shopsys\FrameworkBundle\Component\Cron\Exception\InvalidCronModuleException;
use Shopsys\FrameworkBundle\Component\Deprecations\DeprecationHelper;
use Shopsys\Plugin\Cron\IteratedCronModuleInterface;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;

class CronConfig
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Cron\CronTimeResolver
     */
    protected $cronTimeResolver;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Cron\Config\CronModuleConfig[]
     */
    protected $cronModuleConfigs;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Cron\CronTimeResolver $cronTimeResolver
     */
    public function __construct(CronTimeResolver $cronTimeResolver)
    {
        $this->cronTimeResolver = $cronTimeResolver;
        $this->cronModuleConfigs = [];
    }

    /**
     * @param \Shopsys\Plugin\Cron\SimpleCronModuleInterface|\Shopsys\Plugin\Cron\IteratedCronModuleInterface|mixed $service
     * @param string $serviceId
     * @param string $timeHours
     * @param string $timeMinutes
     * @param string $instanceName
     * @param string|null $readableName
     * @phpstan-ignore-next-line
     * @param int $runEveryMin
     * @phpstan-ignore-next-line
     * @param int $timeoutIteratedCronSec
     */
    public function registerCronModuleInstance(
        $service,
        string $serviceId,
        string $timeHours,
        string $timeMinutes,
        string $instanceName,
        ?string $readableName = null,
        /*
        int $runEveryMin,
        int $timeoutIteratedCronSec,
        */
    ): void {
        if (!$service instanceof SimpleCronModuleInterface && !$service instanceof IteratedCronModuleInterface) {
            throw new InvalidCronModuleException($serviceId);
        }

        $runEveryMin = DeprecationHelper::triggerNewArgumentInMethod(
            __METHOD__,
            '$runEveryMin',
            'int',
            func_get_args(),
            6,
            CronModuleConfig::RUN_EVERY_MIN_DEFAULT,
            true
        );

        $timeoutIteratedCronSec = DeprecationHelper::triggerNewArgumentInMethod(
            __METHOD__,
            '$timeoutIteratedCronSec',
            'int',
            func_get_args(),
            7,
            CronModuleConfig::TIMEOUT_ITERATED_CRON_SEC_DEFAULT,
            true
        );

        $this->cronTimeResolver->validateTimeString($timeHours, 23, 1);
        $this->cronTimeResolver->validateTimeString($timeMinutes, 55, 5);

        $cronModuleConfig = new CronModuleConfig($service, $serviceId, $timeHours, $timeMinutes, $readableName, $runEveryMin, $timeoutIteratedCronSec);
        $cronModuleConfig->assignToInstance($instanceName);

        $this->cronModuleConfigs[] = $cronModuleConfig;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Cron\Config\CronModuleConfig[]
     */
    public function getAllCronModuleConfigs()
    {
        return $this->cronModuleConfigs;
    }

    /**
     * @param \DateTimeInterface $roundedTime
     * @return \Shopsys\FrameworkBundle\Component\Cron\Config\CronModuleConfig[]
     */
    public function getCronModuleConfigsByTime(DateTimeInterface $roundedTime)
    {
        $matchedCronConfigs = [];

        foreach ($this->cronModuleConfigs as $cronConfig) {
            if ($this->cronTimeResolver->isValidAtTime($cronConfig, $roundedTime)) {
                $matchedCronConfigs[] = $cronConfig;
            }
        }

        return $matchedCronConfigs;
    }

    /**
     * @param string $serviceId
     * @return \Shopsys\FrameworkBundle\Component\Cron\Config\CronModuleConfig
     */
    public function getCronModuleConfigByServiceId($serviceId)
    {
        foreach ($this->cronModuleConfigs as $cronConfig) {
            if ($cronConfig->getServiceId() === $serviceId) {
                return $cronConfig;
            }
        }

        throw new CronModuleConfigNotFoundException($serviceId);
    }

    /**
     * @param string $instanceName
     * @return \Shopsys\FrameworkBundle\Component\Cron\Config\CronModuleConfig[]
     */
    public function getCronModuleConfigsForInstance(string $instanceName): array
    {
        $matchedCronConfigs = [];

        foreach ($this->cronModuleConfigs as $cronModuleConfig) {
            if ($cronModuleConfig->getInstanceName() === $instanceName) {
                $matchedCronConfigs[] = $cronModuleConfig;
            }
        }

        return $matchedCronConfigs;
    }
}
