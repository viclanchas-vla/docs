<?php
require_once dirname(__DIR__) . '/includes/core.php';
requireLogin();
$b=json_decode(file_get_contents('php://input'),true)??[];
if(!csrfOk()) jsonErr('CSRF',403);
$docId=(int)($b['doc_id']??0); $cliId=(int)($b['client_id']??0);
if ($_SERVER['REQUEST_METHOD']==='POST'){$ex=dbGet("SELECT doc_id FROM doc_clients WHERE doc_id=? AND client_id=?",[$docId,$cliId]);if($ex)dbUpdate('doc_clients',['permission'=>'view'],['doc_id'=>$docId,'client_id'=>$cliId]);else dbInsert('doc_clients',['doc_id'=>$docId,'client_id'=>$cliId,'permission'=>'view']);jsonOk();}
if ($_SERVER['REQUEST_METHOD']==='DELETE'){dbDelete('doc_clients',['doc_id'=>$docId,'client_id'=>$cliId]);jsonOk();}
jsonErr('Méthode',405);
