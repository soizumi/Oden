<?php

require 'config.php';
require 'common.php';

$key_str = "";

//カートへ数量格納
if ( isset($_POST["submit_buy"]) ){
$id = clean($_POST["id"]);
$number = clean($_POST["number"]);
$number_kana = mb_convert_kana($number, 'n');
$number_str = preg_replace('/[^0-9a-zA-Z]/', '', $number_kana);
$number_converted = ltrim($number_str, '0');
$number_key = $id;
if( $number_converted != 0 && is_numeric($number_converted) ){
    if( isset( $_SESSION['product_number']["ID$number_key"] ) ){
        $_SESSION['product_number']["ID$number_key"] += $number_converted;
    }else{
        $_SESSION['product_number']["ID$number_key"] = $number_converted;
    }
}
krsort($_SESSION);
//$_SESSION['product_number'] = array();
}

//カートを空にする]
if ( isset($_POST["submit_cart_empty"]) ){
$_SESSION['product_number'] = array();
}

//商品の削除
if ( isset($postdata["submit_cart_delete"]) ){
    $cart_id = clean($_POST["cart_control_id"]);
    unset($_SESSION['product_number']["$cart_id"]);
}

//商品の数の変更
if ( isset($postdata["submit_update"]) ){
    $cart_id = clean($_POST["cart_control_id"]);
    $number = clean($_POST["number"]);
    $number_kana = mb_convert_kana($number, 'n');
    $number_str = preg_replace('/[^0-9a-zA-Z]/', '', $number_kana);
    $number_converted = ltrim($number_str, '0');
    if( is_numeric($number_converted) && isset($number_converted) ){
            $_SESSION['product_number']["$cart_id"] = $number_converted;
    }else{
            unset($_SESSION['product_number']["$cart_id"]);
    }
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="js/ecsite_script.js"></script>
    <title>ショッピングカート | おでん処　祖泉</title>
</head>
<body class="preload">
    <header>
        <div class="header_menu">
        <h1><a href="index.php"><img src="img/logo.png" alt=""></a></h1>
            <div class="cart">
                <a href="index.php" >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 83.51 76.25" class="icon_home"><defs><style>.cls-1{fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:5px;}</style></defs><title></title><g id="" data-name=""><g id="" data-name=""><polyline class="cls-1" points="2.5 34.76 41.76 2.5 81.01 34.76"/><polyline class="cls-1" points="2.5 51.76 41.76 19.5 81.01 51.76"/><line class="cls-1" x1="13.71" y1="42.57" x2="13.71" y2="73.75"/><line class="cls-1" x1="69.8" y1="42.57" x2="69.8" y2="73.75"/><path class="cls-1" d="M31.25,73.25v-22c0-5,6-9,11-9h0c5,0,11,4,11,9v22"/></g></g></svg>
                <div class="text_button_attached">ホームへ</div>
                </a>
            </div>
        </div>
        <div class="subpage_pagetitle">
            <h2 class="subpage_heading_pagetitle">ショッピングカート</h2>
        </div>
    </header>
    <main>
        <div class="product_wrapper">
            <div class="cart_wrapper">
                <?php if( !empty($_SESSION['product_number']) ): ?>
                    <?php foreach( $_SESSION['product_number'] as $key => $data){ ?>
                    <?php
                        $key_str =  str_replace('ID','',$key);
                        //商品データの取得
                        try {
                            $stmt = $pdo->prepare("SELECT * FROM ECsite WHERE id = ?");
                            $stmt->execute([$key_str]);
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            } catch (Exception $e) {
                              echo $e->getMessage() . PHP_EOL;
                            }
                        $sum = $row['price'] * $data;
                        $total["$key"] = $sum;
                    ?>
                        <div class="cart_conteiner">
                            <div class="cart_product_image">
                                <?php echo image_number($key_str); ?>
                            </div>
                            <div class="cart_information">
                                <div class="cart_product_header">
                                    <div class="cart_product_name"><?php echo $row['name']; ?></div>
                                    <div class="cart_product_price">¥<?php echo $row['price']; ?></div>
                                </div>
                                <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] ); ?>" method="post" class="cart_product_control">
                                    <?php if( $data < 10 ): ?>
                                        <span class="number_wrapper_cart">
                                        <select name="number">
                                            <?php
                                                for($i = 1; $i <= 9; $i++){
                                                    if( $data == $i ){
                                                    echo "<option selected>$i</option>";
                                                    }else{
                                                    echo "<option>$i</option>";
                                                    }
                                                }
                                            ?>
                                            <option>10+</option>
                                        </select>
                                        </span>
                                    <?php else: ?>
                                            <span class="number_wrapper_cart_10over">
                                            <input name="number" value="<?php echo $data; ?>" type="number" pattern="\d*">
                                            </span>
                                    <?php endif; ?>
                                        <button type="submit" name="submit_cart_delete">
                                            <span>削除する</span>
                                        </button>
                                        <input type="hidden" name="cart_control_id" value="<?php echo $key ?>">
                                </form>
                                <div class="cart_product_subtotalprice">小計:¥<?php echo $sum; ?></div>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="cart_product_totalprice">合計(<?php echo array_sum($_SESSION['product_number']) ; ?>点):
                        <span>
                            ¥<?php echo array_sum($total) ; ?>
                        </span>
                    </div>
                    <div class="cart_control">
                        <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] );?>" method="post">
                            <input type="submit" name="submit_cart_empty" value="カートを空にする">
                        </form>
                        <form action="casher.php" method="post">
                            <input type="submit" name="submit_cart_buy" value="レジに進む">
                        </form>
                    </div>
                <?php else: ?>
                    <div class="cart_empty">カートに商品はありません。</div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <footer>
        <div class="copyright"> copyright &copy; Tomo Soizumi.<br class="indention_sp">all rights reserved.</div>
        <div class="admin_link"><a href="admin.php"><span>管理画面へ</span></a></div>
    </footer>
</body>
</html>