<?php

namespace Shopsys\FrameworkBundle\Command;

use Shopsys\FrameworkBundle\Component\Plugin\PluginDataFixtureFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadPluginDataFixturesCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'shopsys:plugin-data-fixtures:load';

    /**
     * @var \Shopsys\FrameworkBundle\Component\Plugin\PluginDataFixtureFacade
     */
    private $pluginDataFixtureFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Plugin\PluginDataFixtureFacade $pluginDataFixtureFacade
     */
    public function __construct(PluginDataFixtureFacade $pluginDataFixtureFacade)
    {
        $this->pluginDataFixtureFacade = $pluginDataFixtureFacade;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Loads data fixtures of all registered plugins');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->pluginDataFixtureFacade->loadAll();

        return CommandResultCodes::RESULT_OK;
    }
}
