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
class scLiveSetup extends SymfonyCommand
{
    function __construct() {
        $this->conn = new mysqli($GLOBALS['servername'], $GLOBALS['username'], $GLOBALS['password']);
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('setup')
          ->setDescription('Set up the live stream')
          ->addArgument('username', InputArgument::REQUIRED, 'Username of Instagram account')
          ->addArgument('password', InputArgument::REQUIRED, 'Password of Instagram account');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Build database and tables
        if($this->createDatabase()) {
            if($this->createTables()) {
              $this->addDetails($input->getArgument('username'), $input->getArgument('password'));
            };
        }
    }

    /**
    * Build new database.
    *
    * @return boolean true or false if database built sucessfully
    */

    private function createDatabase() {
      // Check connection
      if ($this->conn->connect_error) {
          die("Connection failed: " . $conn->connect_error);
      } 

      // Create database
      $sql = "CREATE DATABASE IF NOT EXISTS " . $GLOBALS['dbname'];
      if ($this->conn->query($sql) === TRUE) {
          echo "Database created successfully \n";

          return true;
      } else {
          echo "Error creating database: " . $conn->error . "\n";

          return false;
      }

      $this->conn->close();
    }

    /**
    * Add tables to database
    *
    * @return boolean true or false if database built sucessfully
    */

    private function createTables() {
      // Check connection
      if ($this->conn->connect_error) {
          die("Connection failed: " . $conn->connect_error);
      }

      // sql to create table
      $sql = "CREATE TABLE IF NOT EXISTS " . $GLOBALS['dbname'] . "." . $GLOBALS['tableprefix'] . "config (
      option_id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
      option_name VARCHAR(60) NOT NULL,
      option_value VARCHAR(60) NOT NULL
      )";

      if ($this->conn->query($sql) === TRUE) {
        echo("Config tables created successfully. \n");
      } else {
        echo("Error creating table: " . $this->conn->error . "\n");

        return false;
      }

      // sql to create table
      $sql = "CREATE TABLE IF NOT EXISTS " . $GLOBALS['dbname'] . "." . $GLOBALS['tableprefix'] . "comments (
      comment_id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
      comment_ts VARCHAR(60) NOT NULL,
      comment_content TEXT(120) NOT NULL,
      comment_instaid VARCHAR(60) NOT NULL
      )";

      if ($this->conn->query($sql) === TRUE) {
        echo("Comment tables created successfully. \n");
      } else {
        echo("Error creating table: " . $this->conn->error . "\n");

        return false;
      }

        return true;
    }

    /**
    * Add details to database
    *
    * @param string $username Instagram username
    * @param string $password Instagram password
    *
    * @return boolean true or false if fields added sucessfully
    */

    private function addDetails($username, $password) {
      // Check connection
      if ($this->conn->connect_error) {
          die("Connection failed: " . $conn->connect_error);
      }

      $sql = "INSERT INTO `" . $GLOBALS['tableprefix'] . "config` (`option_id`, `option_name`, `option_value`)
              VALUES
                (1, 'username', '$username'),
                (2, 'password', '$password')
              ON DUPLICATE KEY UPDATE
                option_value=VALUES(option_value)";

      mysqli_select_db($this->conn, $GLOBALS['dbname']);

      if ($this->conn->query($sql) === TRUE) {
        echo("Saved username and password!\n");
      } else {
        echo("Error: " . $sql . "\n" . $this->conn->error . "\n");
      }

      $this->conn->close();
    }
}
