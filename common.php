<?php

date_default_timezone_set('Asia/Tokyo');
$now = new DateTime('', new DateTimeZone('Asia/Tokyo'));

//サニタイズ

$postdata = array_map('htmlspecialchars',$_POST);

function clean($data){
  return htmlspecialchars($data, ENT_QUOTES, 'utf-8');
}

//画像ファイル名生成
function image_number($product_id){
    $image_list = glob("img/uploaded/*");
    $display_image_filename  = preg_grep ("/image_ID_{$product_id}_/i", $image_list);
    if( !empty($display_image_filename) ){
        arsort($display_image_filename);
        $image_file = reset($display_image_filename);
    }else{
        $image_file = "img/noimage.png";
    }
    return '<img src="'.$image_file.'" class="product_image_genereted" alt="" >';
}

//データベースへのアクセス、テーブルがない場合作成
try {
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("create table if not exists ECsite(
      id serial,
      name varchar(30) NOT NULL,
      price int(30) NOT NULL,
      caption varchar(128) NOT NULL,
      created timestamp not null default current_timestamp)");
    $pdo->exec("create table if not exists ECsite_orderData(
      orderid serial,
      name varchar(30) NOT NULL,
      postalcode varchar(30) NOT NULL,
      address varchar(128) NOT NULL,
      phonenumber varchar(30) NOT NULL,
      mail varchar(128) NOT NULL,
      salesamount int(60) NOT NULL,
      created timestamp not null default current_timestamp)");
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}

?>