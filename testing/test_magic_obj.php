<?php
include_once '../vendor/autoload.php';

use Mandryn\MagicObject;

$mo=new MagicObject();
$mo->message='Testing';
echo $mo->getJsonString();
