<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use mysqli;

/**
 * Class startStream
 *
 * @package App\Command
 */
class streamStart extends SymfonyCommand
{
    private $ig;
    private $broadcastId;
    private $pinnedcommentID;

    function __construct() {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        // Setup the command
        $this->setName('stream:start')
            ->setDescription('Start the stream');
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
        if($this->streamLogin()) {
            $io->writeln(array('<info>Logged in.</info>','Fetching key...'));
            
            if($streamURL = $this -> fetchKey()) {
                $io->section('URL');
                $io->writeln($streamURL['url']);
                $io->section('KEY');
                $io->writeln($streamURL['key']);
                $io->success('Please copy and paste the URL and Key into OBS');
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Ready to start streaming? [Enter \'y\' to proceed]: ', false);

                if (!$helper->ask($input, $output, $question)) {
                    $io->writeln('Closing stream...');
                    $this->stopStream();
                    return;
                }

                $io->writeln('Starting stream...');
                
                if($this->startStream()) {
                    $io->success("Stream is now live!");
                }

                $response = true;
                do {
                    // Keep asking for new comments
                    $helper = $this->getHelper('question');
                    $question = new Question('>: ');
                    $comment = $helper->ask($input, $output, $question);
                    $response = $this->sendComment($comment);
                } while ($response != false);

                $io->writeln('Stopping stream...');
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Do you want to save this stream? [Enter \'y\' to save]: ', false);

                if (!$helper->ask($input, $output, $question)) {
                    $this->stopStream();
                } else {
                    $this->stopStream(true);
                    $io->writeln('Stream saved succesfully.');
                }
                $io->success('Stream ended');

            }
        }
    }

    private function streamLogin()
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

    private function fetchKey()
    {
        try {

        //Create Instagram stream
        $stream = $this->ig->live->create();

        // Fetch the broadcast ID
        $this->broadcastId = $stream->getBroadcastId();

        // Connect to database to save broadcast ID
        $conn = new mysqli($GLOBALS['servername'], $GLOBALS['username'], $GLOBALS['password'], $GLOBALS['dbname']);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Setup SQL statement
        $sql = "INSERT INTO `" . $GLOBALS['tableprefix'] . "config` (`option_id`, `option_name`, `option_value`)
              VALUES
                (3, 'broadcastID', '$this->broadcastId')
              ON DUPLICATE KEY UPDATE
                option_value=VALUES(option_value)";

        // Process SQL
        if ($conn->query($sql) === TRUE) {
            echo("Saved Broadcast ID\n");
        } else {
            echo("Error: " . $sql . "\n" . $conn->error . "\n");
        }

        // Close connection
        $conn->close();

        // Switch from RTMPS to RTMP upload URL, since RTMPS doesn't work well.
        $streamUploadUrl = preg_replace(
            '#^rtmps://([^/]+?):443/#ui',
            'rtmp://\1:80/',
            $stream->getUploadUrl()
        );

        // Split URL and key
        $pos = strrpos($streamUploadUrl, '/');

        $url = substr($streamUploadUrl, 0, $pos+1);
        $key = substr($streamUploadUrl, $pos+1);

        // Return URL and Key as array
        return array('url' => $url, 'key' => $key);

        } catch (\Exception $e) {
            echo 'Something went wrong: '.$e->getMessage()."\n";
        }
    }

    private function startStream() {

        // Start live stream
        $this->ig->live->start($this->broadcastId);

        // Start asynchronouse process of recording comments
        $process = new Process('php scLive stream:record');
        $process->start(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'ERR > '.$buffer;
            } else {
                echo 'MSG IN > '.$buffer;
            }
        });

        if($process->isRunning()) {
            echo "Recording comments started...\n";
        } else {
            echo "Recording comments failed.\n";
            return false;
        }

        return true;
    }

    private function stopStream($save = false) {
        $this->ig->live->getFinalViewerList($this->broadcastId);
    
        $this->ig->live->end($this->broadcastId);

        if($save) {
            $this->ig->live->addToPostLive($this->broadcastId);
        }
    }

    private function sendComment($comment) {
        if($comment == '-stop') {
            return false;
        }

        if(strpos($comment, '-pin')) {
            $cleanedComment = str_replace('-pin', '', $comment);
            $sentComment = $this->ig->live->comment($this->broadcastId, $cleanedComment);
            $commentResponse = $sentComment->getComment();
            $this->pinnedcommentID = $commentResponse->getPk();
            $this->ig->live->pinComment($this->broadcastId, $this->pinnedcommentID);
            echo(">>> COMMENT PINNED\n");
            return true;
        }

        if($comment == '-unpin') {
            if($this->pinnedcommentID) {
                $this->ig->live->unpinComment($this->broadcastId, $this->pinnedcommentID);
                echo(">>> COMMENT UNPINNED\n");
            } else {
                echo(">>> NO COMMENT CURRENTLY PINNED\n");
            }
            return true;
        }

        $this->ig->live->comment($this->broadcastId, $comment);
        return true;
    }

}
