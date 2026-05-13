<?php
require_once dirname(__DIR__) . '/includes/core.php';
requireLogin();
$b=json_decode(file_get_contents('php://input'),true)??[];
if(!csrfOk()) jsonErr('CSRF',403);
$id=(int)($b['id']??0);
$c=dbGet("SELECT portal_token FROM clients WHERE id=?",[$id]);
$data=['portal_active'=>1];
if(empty($c['portal_token'])) $data['portal_token']=bin2hex(random_bytes(32));
dbUpdate('clients',$data,['id'=>$id]);
jsonOk();
