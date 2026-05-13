<?php
require_once dirname(__DIR__) . '/includes/core.php';
requireLogin();
$b=json_decode(file_get_contents('php://input'),true)??[];
if(!csrfOk()) jsonErr('CSRF',403);
dbDelete('clients',['id'=>(int)($b['id']??0)]);
jsonOk();
