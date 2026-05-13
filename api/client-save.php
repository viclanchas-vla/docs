<?php
require_once dirname(__DIR__) . '/includes/core.php';
requireLogin();
$b=json_decode(file_get_contents('php://input'),true)??[];
if(!csrfOk()) jsonErr('CSRF',403);
if(empty($b['name'])||empty($b['email'])) jsonErr('Champs requis');
$d=['name'=>trim($b['name']),'email'=>trim($b['email']),'company'=>trim($b['company']??'')?:null,'color'=>$b['color']??'#233874'];
if($b['id']) dbUpdate('clients',$d,['id'=>(int)$b['id']]);
else{$d['portal_token']=bin2hex(random_bytes(32));dbInsert('clients',$d);}
jsonOk();
