<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class startStream
 *
 * @package App\Command
 */
class getQuestions extends SymfonyCommand
{
    private $ig;

    function __construct() {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        // Setup the command
        $this->setName('getquestions')
            ->setDescription('Get current story question details.')
            ->addOption('list', 'l', NULL, 'List all the responses.');
    }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return void
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        if($this->igLogin()) {
            $io->writeln(array('<info>Logged in.</info>','Fetching story info...'));
            $story = $this->ig->story->getUserReelMediaFeed($this->ig->account_id);

            if($story->hasItems()){
              $items = $story->getItems();
              foreach ($items as $item) {
                $question = $item->getStoryQuestions();
                $io->writeln(array('<info>' . $question[0]->getQuestionSticker()->getQuestion() . '</info>'));
                if($item->hasStoryQuestionResponderInfos()) {
                  $responseCount = $item->getStoryQuestionResponderInfos();
                  $io->writeln(array('Responses: ' . $responseCount[0]->getQuestionResponseCount()));
                    if($input->getOption('list')) {
                      foreach ($responseCount[0]->getResponders() as $response) {
                        $io->writeln(array($response->getUser()->getUsername() . ': ' . $response->getResponse()));
                      }
                    }
                } else {
                  $io->writeln(array('No responses'));
                }
              }
            } else {
              $io->writeln(array('<info>There are no story items</info>'));
            }
        }
    }

    private function igLogin()
    {
        echo("Logging in...\n");
        $configSettings = new \App\Config();
        $config = $configSettings->getSettings();
        $debug = $GLOBALS['debug'];
        $truncatedDebug = $GLOBALS['truncateddebug'];

        $this->ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

        try {
            $this->ig->login($config['username'], $config['password']);
            return true;
        } catch (\Exception $e) {
            echo 'Something went wrong: '.$e->getMessage()."\n";
            exit(0);
        }
    }

}
