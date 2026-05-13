<?php
require_once dirname(__DIR__) . '/includes/core.php';
requireLogin();
$b=json_decode(file_get_contents('php://input'),true)??[];
if(!csrfOk()) jsonErr('CSRF',403);
if ($_SERVER['REQUEST_METHOD']==='POST'){$token=bin2hex(random_bytes(32));dbInsert('share_links',['token'=>$token,'doc_id'=>(int)($b['doc_id']??0),'client_id'=>$b['client_id']?(int)$b['client_id']:null,'expires_at'=>$b['expires_at']?date('Y-m-d 23:59:59',strtotime($b['expires_at'])):null]);jsonOk(['token'=>$token]);}
if ($_SERVER['REQUEST_METHOD']==='DELETE'){dbUpdate('share_links',['is_active'=>0],['id'=>(int)($b['link_id']??0)]);jsonOk();}
jsonErr('Méthode',405);
