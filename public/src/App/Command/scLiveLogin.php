<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
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
      $io = new SymfonyStyle($input, $output);

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

          $io->success('Logged in successfully!');

          return true;
        }
      } catch (\Exception $e) {
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            if ($e instanceof ChallengeRequiredException && $e->getResponse()->getErrorType() === 'checkpoint_challenge_required') {
                $response = $e->getResponse();
                $output->writeln("Your account has been flagged by Instagram. We can attempt to verify your account by a text or an email. Would you like to do that?");
                $output->writeln("Note: If you already did this, and you think you entered the right code, do not attempt this again! Try logging into instagram.com from this same computer or enabling 2FA.");
                
                $helper = $this->getHelper('question');
                $question = new Question("Type \"yes\" to do so or anything else to not!: ", null);
                $attemptBypass = $helper->ask($input, $output, $question);

                if ($attemptBypass == 'yes') {
                    $output->writeln("Please wait while we prepare to verify your account.");
                    sleep(3);
                    $helper = $this->getHelper('question');
                    $question = new Question("Type \"sms\" for text verification or \"email\" for email verification.\nNote: If you do not have a phone number or an email address linked to your account, don't use that method ;) You can also just press enter to abort.", null);
                    $choice = $helper->ask($input, $output, $question);
          
                    if ($choice === "sms") {
                        $verification_method = 0;
                    } elseif ($choice === "email") {
                        $verification_method = 1;
                    } else {
                        $io->error("You have selected an invalid verification type. Aborting!");
                        exit();
                    }
                    /** @noinspection PhpUndefinedMethodInspection */
                    $checkApiPath = substr($response->getChallenge()->getApiPath(), 1);
                    $customResponse = $ig->request($checkApiPath)
                        ->setNeedsAuth(false)
                        ->addPost('choice', $verification_method)
                        ->addPost('_uuid', $ig->uuid)
                        ->addPost('guid', $ig->uuid)
                        ->addPost('device_id', $ig->device_id)
                        ->addPost('_uid', $ig->account_id)
                        ->addPost('_csrftoken', $ig->client->getToken())
                        ->getDecodedResponse();
                    try {
                        if ($customResponse['status'] === 'ok' && isset($customResponse['action'])) {
                            if ($customResponse['action'] === 'close') {
                                $io->success("Challenge Bypassed! Run the script again.");
                                exit();
                            }
                        }
                        
                        $helper = $this->getHelper('question');
                        $question = new Question("Please enter the code you received via " . ($verification_method ? 'email' : 'sms') . "!", null);
                        $cCode = $helper->ask($input, $output, $question);

                        $ig->changeUser($username, $password);
                        $customResponse = $ig->request($checkApiPath)
                            ->setNeedsAuth(false)
                            ->addPost('security_code', $cCode)
                            ->addPost('_uuid', $ig->uuid)
                            ->addPost('guid', $ig->uuid)
                            ->addPost('device_id', $ig->device_id)
                            ->addPost('_uid', $ig->account_id)
                            ->addPost('_csrftoken', $ig->client->getToken())
                            ->getDecodedResponse();
                        $io->success("Provided you entered the correct code, your login attempt has probably been successful. Please try re-running the script!");
                        exit();
                    } catch (Exception $ex) {
                         $io->error($ex->getMessage());
                        exit;
                    }
                } else {
                    $io->warning("Account Flagged: Please try logging into instagram.com from this exact computer before trying to run this script again!");
                    exit();
                }
            }
        } catch (\LazyJsonMapper\Exception\LazyJsonMapperException $mapperException) {
            $io->error('Error While Logging in to Instagram: ' . $e->getMessage());
            exit();
        }
        $io->error('Error While Logging in to Instagram: ' . $e->getMessage());
        exit();
}

        $output->writeln('Already logged in.');
    }

}
