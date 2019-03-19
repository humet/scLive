<?php
use Zend\ServiceManager\ServiceManager;
use App\Command\scLiveSetup;
use App\Command\scLiveLogin;
use App\Command\scLiveLogout;
use App\Command\streamStart;
use App\Command\streamRecord;
use App\Command\getQuestions;

return [
    'factories' => [
        scLiveSetup::class => function (ServiceManager $serviceManager) {
            return new scLiveSetup();
        },
        scLiveLogin::class => function (ServiceManager $serviceManager) {
            return new scLiveLogin();
        },
        scLiveLogout::class => function (ServiceManager $serviceManager) {
            return new scLiveLogout();
        },
        streamStart::class => function (ServiceManager $serviceManager) {
            return new streamStart();
        },
        streamRecord::class => function (ServiceManager $serviceManager) {
            return new streamRecord();
        },
        getQuestions::class => function (ServiceManager $serviceManager) {
            return new getQuestions();
        },
    ],
];
