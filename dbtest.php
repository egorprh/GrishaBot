<?php

include('classes/dbmanage.php');

$dbManager = new dbmanage();

$tablename = 'usersdata';

$dbManager->get_records($tablename);
