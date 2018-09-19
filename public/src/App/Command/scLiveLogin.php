<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use mysqli;



/**
 * Class startStream
 *
 * @package App\Command
 */
class scLiveLogin extends SymfonyCommand
{
    function __construct() {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('login')
          ->setDescription('Login to Instagram');
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

      $username = $config['username'];
      $password = $config['password'];
      $debug = $GLOBALS['debug'];
      $truncatedDebug = $GLOBALS['truncateddebug'];
      $ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

      try {
        $output->writeln('Logging in...');
        $loginResponse = $ig->login($username, $password);

        if ($loginResponse !== null && $loginResponse->isTwoFactorRequired()) {
          $twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
          $output->writeln('Please wait for your 2auth code...');
          // The "STDIN" lets you paste the code via terminal for testing.
          // You should replace this line with the logic you want.
          // The verification code will be sent by Instagram via SMS.
          $helper = $this->getHelper('question');
          $question = new Question('Please enter the 2auth code you have received: ', null);
          $auth_code = $helper->ask($input, $output, $question);

          $ig->finishTwoFactorLogin($username, $password, $twoFactorIdentifier, $auth_code);

          return true;
        }
      } catch (\Exception $e) {
        $output->writeln('Something went wrong: '.$e->getMessage());

        return false;
      }

        $output->writeln('Already logged in.');
    }

}
