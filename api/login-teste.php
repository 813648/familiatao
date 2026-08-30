<?php
session_start();
$_SESSION['user']='testuser';
$_SESSION['role']='admin';
echo json_encode(['status'=>'ok','user'=>$_SESSION['user'],'role'=>$_SESSION['role']]);