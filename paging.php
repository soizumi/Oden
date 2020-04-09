<?php

//商品数取得
try {
    $stmt_count = $pdo->prepare('SELECT count(*) FROM ECsite');
    $stmt_count->execute();
    $posts_number = $stmt_count->fetchColumn();
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}

//ページング
//総ページ数
$totalpage = ceil($posts_number / $perpage);

//現在のページ数
if( isset($_GET['page']) ){
    if( $_GET['page'] == "" || $_GET['page'] == "0"){
        $page = 1;
    }elseif( $_GET['page'] > $totalpage ){
        $page = 1;
    }else{
        $page = (int) $_GET['page'];
    }
}else{
    $page = 1;
}
$page = clean($page);

//ページャー生成
$prev = max($page - 1, 1);
$next = min($page + 1, $totalpage);

if($page == 1){
    $pagerange = 2;
}elseif($page == $totalpage){
    $pagerange = 2;
}else{
    $pagerange = 1;
}
$start = $page - $pagerange;
$end =$page + $pagerange;
$start = max($page -$pagerange,1);
$end = min($page + $pagerange,$totalpage);
$nums = []; // ページ番号格納用
for ($i = $start; $i <= $end; $i++) {
  $nums[] = $i;
}

?>