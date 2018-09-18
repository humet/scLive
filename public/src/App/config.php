<?php
namespace App;
use mysqli;

/**
 * Class startStream
 *
 * @package App\Config
 */
class Config
{
    /**
     *
     * @return array Settings from the database
     */
    public function getSettings()
    {
        // Create connection
      $conn = new mysqli($GLOBALS['servername'], $GLOBALS['username'], $GLOBALS['password'], $GLOBALS['dbname']);
      // Check connection
      if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
      }

      $sql = "SELECT option_name, option_value FROM " . $GLOBALS['tableprefix'] . "config";
      $result = $conn->query($sql);

      if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
            $config[$row['option_name']] = $row['option_value'];
        }
      } else {
        return false;
      }

      $conn->close();

      return $config;
    }

}
