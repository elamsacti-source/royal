<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$dni = $_GET['dni'] ?? '';
if(empty($dni)) { echo json_encode(['success'=>false]); exit; }

$token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIzOTMyNiIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vd3MvMjAwOC8wNi9pZGVudGl0eS9jbGFpbXMvcm9sZSI6ImNvbnN1bHRvciJ9.aJCVJFvYRxvqcWH2gBOXMEdbgs9OOEBp3YG5yY_S68Y";
$url = "https://api.factiliza.com/v1/dni/info/" . trim($dni);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
echo curl_exec($ch);
curl_close($ch);
?>