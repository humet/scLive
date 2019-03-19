<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class startStream
 *
 * @package App\Command
 */
class scLiveLogout extends SymfonyCommand
{
    function __construct() {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('logout')
          ->setDescription('Logout of Instagram');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $configSettings = new \App\Config();
      $config = $configSettings->getSettings();
      $debug = $GLOBALS['debug'];
      $truncatedDebug = $GLOBALS['truncateddebug'];
      $ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);
      $ig->logout();
    }

}
