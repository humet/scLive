<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use mysqli;

/**
 * Class startStream
 *
 * @package App\Command
 */
class streamRecord extends SymfonyCommand
{

    function __construct() {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        // Setup the command
        $this->setName('stream:record');
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
        $this->ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

        try {
            $this->ig->login($config['username'], $config['password']);
        } catch (\Exception $e) {
            echo 'Something went wrong: '.$e->getMessage()."\n";
            exit(0);
        }

        $lastCommentTs = 0;
        $lastLikeTs = 0;
        do {
            // Get broadcast comments.
            // - The latest comment timestamp will be required for the next
            //   getComments() request.
            // - There are two types of comments: System comments and user comments.
            //   We compare both and keep the newest (most recent) timestamp.
            $commentsResponse = $this->ig->live->getComments($config['broadcastID'], $lastCommentTs);
            $systemComments = $commentsResponse->getSystemComments();
            $comments = $commentsResponse->getComments();
            if (!empty($systemComments)) {
                $lastCommentTs = end($systemComments)->getCreatedAt();
            }
            if (!empty($comments) && end($comments)->getCreatedAt() > $lastCommentTs) {
                $lastCommentTs = end($comments)->getCreatedAt();
            }
            // Get broadcast heartbeat and viewer count.
            $this->ig->live->getHeartbeatAndViewerCount($config['broadcastID']);
            // Get broadcast like count.
            // - The latest like timestamp will be required for the next
            //   getLikeCount() request.
            $likeCountResponse = $this->ig->live->getLikeCount($config['broadcastID'], $lastLikeTs);
            $lastLikeTs = $likeCountResponse->getLikeTs();

            echo("STOPPING...\n");

            sleep(2);
        } while (1);
        
    }

}
