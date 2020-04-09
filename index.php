<?php

require 'config.php';
require 'common.php';

//1ページごとの表示数
$perpage = 6;
require 'paging.php';

//商品の表示用データの取得
try {
    $offset = ($page - 1) * 6;
    $stmt = $pdo->prepare("SELECT * FROM ECsite ORDER BY id DESC LIMIT 6 OFFSET $offset");
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      echo $e->getMessage() . PHP_EOL;
}

?>

<!DOCTYPE html>
<html lang="jp">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/style_ec.css">
    <link rel="icon" href="img/favicon.ico">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js">
    </script>
    <script src="js/ecsite_script.js">
    </script>
    <title>おでん処　祖泉</title>
</head>
<body class="preload">
    <header>
        <div class="header_menu">
        <h1><a href="index.php"><img src="img/logo.png" alt=""></a></h1>
            <div class="cart">
                <a href="cart.php" >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 77.5 66.5" class="icon_cart"><title></title><g id="" data-name=""><g id="" data-name=""><path d="M77.37,1.3A2,2,0,0,0,74.8.13l-8,3a2,2,0,0,0-1.19,1.21L63.82,9.5H2A2,2,0,0,0,.14,12.24l12,30A2,2,0,0,0,14,43.5H52L50.08,49H15.5a2,2,0,0,0,0,4h36a2,2,0,0,0,1.89-1.34L69.08,6.55,76.2,3.87A2,2,0,0,0,77.37,1.3Zm-62,38.2L5,13.5H62.43l-9,26Z"/><circle cx="20" cy="61" r="5.5"/><circle cx="46" cy="61" r="5.5"/></g></g></svg>
                <?php
                    if( !empty($_SESSION['product_number']) ){
                        echo "<div class='cart_status'><span>".array_sum($_SESSION['product_number'])."</span></div>" ;
                    }
                ?>
                <div class="text_button_attached">カートへ</div>
                </a>
            </div>
        </div>
        <div class="main_visual">
            <h2 class="main_visual_copy">おひとつ<span class="punctuation_pack">、</span>いかが。</h2>
            <div class="main_visual_steam"><img src="img/steam.svg" alt=""></div>
            <div class="main_visual_steam2"><img src="img/steam2.svg" alt=""></div>
        </div>
    </header>
    <main>
        <div class="product_wrapper">
            <?php if( !empty($row) ): ?>
                <?php foreach($row as $rowdata){ ?>
                    <div class="product_conteiner">
                            <div class="product_image">
                                <?php echo image_number($rowdata['id']); ?>
                            </div>
                            <div class="product_infomation">
                                <div class="product_infomation_header">
                                    <div class="product_name"><?php echo $rowdata['name']; ?></div>
                                    <div class="product_price">¥<?php echo $rowdata['price']; ?></div>
                                </div>
                                <div class="product_caption"><?php echo $rowdata['caption']; ?></div>
                                <form class="product_form" action="cart.php" method="post">
                                    <span class="number_wrapper">
                                        <select name="number">
                                            <?php
                                                for($i = 1; $i <= 9; $i++){
                                                    echo "<option>$i</option>";
                                                }
                                            ?>
                                            <option>10+</option>
                                        </select>
                                    </span>
                                    <input type="hidden"  name="id" value="<?php echo $rowdata['id'] ; ?>">
                                    <button type="submit" name="submit_buy" value="">
                                    <span>カートに入れる</span>
                                    </button>
                                </form>
                            </div>
                    </div>
                <?php } ?>
                <?php if( $totalpage > 1) : ?>
                    <div class="pager_conteiner">
                        <?php
                            if($page != 1){
                                echo '<a class="pager_prev" href="?page=' . $prev . '">&laquo; 前へ</a>';
                            } ?>
                        <div class="pager_num_conteiner">
                            <?php
                            foreach ($nums as $num) {
                                if ($num == $page) {
                                    echo '<div class="current">' . $num . '</div>';
                                } else {
                                    echo '<div><a href="?page='. $num .'" class="num">' . $num . '</a></div>';
                                }
                            }
                            ?>
                        </div>
                        <?php
                            if($totalpage > $page){
                                echo '<a class="pager_next" href="?page=' . $next . '">次へ &raquo;</a>';
                            }
                        ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="cart_wrapper">
                    <div class="cart_empty">
                        商品が登録されていません。
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <div class="copyright"> copyright &copy; Tomo Soizumi.<br class="indention_sp">all rights reserved.</div>
        <div class="admin_link"><a href="admin.php"><span>管理画面へ</span></a></div>
    </footer>
</body>
</html>