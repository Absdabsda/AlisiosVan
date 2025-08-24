<?php
declare(strict_types=1);
ini_set('display_errors','0'); error_reporting(E_ALL);
require_once __DIR__ . '/../config/bootstrap_env.php';
require __DIR__ . '/../config/db.php';
$pdo = get_pdo();

header('Content-Type: application/json; charset=utf-8');
function jexit(array $p,int $c=200){ http_response_code($c); echo json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }

$key = $_POST['key'] ?? $_GET['key'] ?? '';
if (!$key || !hash_equals(env('ADMIN_KEY',''), (string)$key)) jexit(['ok'=>false,'error'=>'forbidden'],403);
if (($_POST['action'] ?? '')!=='update' || $_SERVER['REQUEST_METHOD']!=='POST') jexit(['ok'=>false,'error'=>'invalid action'],400);

try{
    $id=(int)($_POST['id']??0); if($id<=0) jexit(['ok'=>false,'error'=>'invalid id'],400);
    $priceRaw=trim((string)($_POST['price_per_night']??'')); $priceNorm=str_replace([',',' '],['.',''],$priceRaw);
    if($priceNorm==='' || !is_numeric($priceNorm)) jexit(['ok'=>false,'error'=>'invalid price'],400);
    $price=(float)$priceNorm; if($price<0||$price>10000) jexit(['ok'=>false,'error'=>'price out of range'],400);
    $min=(int)($_POST['min_nights']??0); if($min<1||$min>60) jexit(['ok'=>false,'error'=>'invalid min nights'],400);

    $st=$pdo->prepare("UPDATE campers SET price_per_night=?, min_nights=? WHERE id=?");
    $st->execute([$price,$min,$id]);
    jexit(['ok'=>true,'updated'=>$st->rowCount(),'price_per_night'=>$price,'min_nights'=>$min]);
}catch(Throwable $e){ jexit(['ok'=>false,'error'=>$e->getMessage()],400); }
