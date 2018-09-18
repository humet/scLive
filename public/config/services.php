<?php
use Zend\ServiceManager\ServiceManager;
use App\Command\scLiveSetup;
use App\Command\scLiveLogin;
use App\Command\streamStart;
use App\Command\streamRecord;

return [
    'factories' => [
        scLiveSetup::class => function (ServiceManager $serviceManager) {
            return new scLiveSetup();
        },
        scLiveLogin::class => function (ServiceManager $serviceManager) {
            return new scLiveLogin();
        },
        streamStart::class => function (ServiceManager $serviceManager) {
            return new streamStart();
        },
        streamRecord::class => function (ServiceManager $serviceManager) {
            return new streamRecord();
        },
    ],
];
