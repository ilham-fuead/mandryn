<?php
include_once '../vendor/autoload.php';

use Mandryn\db\Query;
use Mandryn\db\constant\QueryType;
use Mandryn\db\constant\DataType;
use Mandryn\db\constant\ConditionType;
use Mandryn\db\constant\AppenderOperator;

$q=new Query(QueryType::UPDATE);
$q->setTable('Pelajar');
//$q->setUpdateField('Nama', 'Ilham', DataType::VARCHAR);
$q->setConditionField('IC', ConditionType::IS_NULL, '', DataType::VARCHAR, AppenderOperator::NONE_OPR);
$q->setConditionField('Nama', ConditionType::LIKE, '%jeng', DataType::VARCHAR, AppenderOperator::AND_OPR);
echo $q->getQueryString();