<?php
  $config = array(
    "mysql_host" => "", //url of your mysql server
    "mysql_user" => "", //username on your mysql server
    "mysql_pwd"  => "", //password for specified user
    "mysql_db"   => "", //database to use
    "mysql_pre"  => "", // prefix for mysql tables
    "addSpendingPassword" => "", //the password you'll have to enter to add a spending
);

$cigSettings = array(
    "startedHuman" => "01.01.2015 13:00", // date when you started to smoke electronic cigarettes, format "dd.mm.yyyy hh:mm"
    "cigsPerDay" => 20, //how many cigarettes did you smoke a day?
    "cigsInBox" => 20, //how many cigarettes did a box of your blend contain?
    "pricePerBox" => 5, //how much did a box of your blend cost?
    "BoxesPerCarton" => 10, //how much boxes did a carton contain?
    "currency" => "", //the currency in your country
);
?>
