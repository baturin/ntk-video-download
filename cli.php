#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;
use Symfony\Component\Console\Command\Command;
use Abaturin\NtkVideoDownloader\Downloader;

(new SingleCommandApplication())
    ->addOption(
        'login', 
        null, 
        InputOption::VALUE_REQUIRED, 
        'Your login'
    )
    // Attention: it is insecure to pass a password as command-line argument. Use at your own risk.
    ->addOption(
        'password', 
        null, 
        InputOption::VALUE_REQUIRED, 
        'Your password'
    )
    ->addOption(
        'camera-id', 
        null, 
        InputOption::VALUE_REQUIRED, 
        'Camera identifier'
    )
    ->addOption(
        'output-file', 
        null, 
        InputOption::VALUE_REQUIRED, 
        'Path to put video file to'
    )
    ->addOption(
        'start-date',
         null, 
         InputOption::VALUE_REQUIRED, 
         'Start date to download in "dd-mm-yyyy" or "dd-mm-yyy H:i:s" format'
    )
    ->addOption(
        'length-hours', 
        null, 
        InputOption::VALUE_REQUIRED, 
        'Length of the video in hours'
    )
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $downloader = new Downloader();
        
        $login = $input->getOption('login');

        if (empty($login)) {
            throw new \Exception("Please specify valid login with --login option");
        }

        $password = $input->getOption('password');

        if (empty($password)) {
            throw new \Exception("Please specify valid password with --password option");
        }

        $cameraId = $input->getOption('camera-id');


        if (!is_numeric($cameraId)) {
            throw new \Exception("Please specify valid camera ID with --camera-id option");
        }

        $outputFilename = $input->getOption('output-file');

        if (empty($outputFilename)) {
            throw new \Exception("Please specify valid output filename with --output-file option");
        }

        if (file_exists($outputFilename)) {
            throw new \Exception("Specified file already exists");
        }

        $startDate = $input->getOption('start-date');

        if (empty($startDate)) {
            throw new \Exception("Please specify valid start date with --start-date option");
        }

        $dateTime = DateTime::createFromFormat('d-m-Y H:i:s', $startDate . ' 00:00:00');
        if (!$dateTime) {
            throw new \Exception("Start date specified with --start-date option is invalid");
        }

        $startTimestamp = (string)$dateTime->getTimestamp();

        $hours = (int)$input->getOption('length-hours');
        if (!is_numeric($hours)) {
            throw new \Exception("Please specify video length with --length-hours option");
        }
        $stopTime = $hours * 60;

        $output->writeln("Logging in as $login...");
        $downloader->login($login, $password);
        $output->writeln("Fetch video URL...");
        $url = $downloader->fetchDownloadUrl($cameraId, $startTimestamp, $stopTime);
        $output->writeln("Video URL: $url");
        $output->writeln("Download video...");
        $url = $downloader->downloadVideoByUrl($url, $outputFilename);
        $output->writeln("Success!");

        return Command::SUCCESS;
    })
    ->run();