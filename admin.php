<?php

require 'config.php';
require 'common.php';

if( isset($_SERVER['HTTP_REFERER']) ){
    if( strpos($_SERVER['HTTP_REFERER'],'edit.php') == true && isset($_SESSION['edit_changed']) ){
        $system_msg = '商品を変更しました';
        unset($_SESSION['edit_changed']);
    }
    if( strpos($_SERVER['HTTP_REFERER'],'edit.php') == true && isset($_SESSION['edit_register']) ){
        $system_msg = '商品を登録しました';
        unset($_SESSION['edit_register']);
    }
        $_SERVER['HTTP_REFERER'] = "";
}

//パスワード認証

$set_password = "";

$hashes = [
    'admin' => '$2y$10$kW50hO7GxfVVInGLFmbuNeC5HTxMizze/ei8kggHR9NXdF7eexXYi',
];

function generate_token(){
    // セッションIDからハッシュを生成
    return hash('sha256', session_id());
}

function validate_token($token){
    // 送信されてきた$tokenがこちらで生成したハッシュと一致するか検証
    return $token === generate_token();
}

$adminid = clean( filter_input(INPUT_POST,'authentication_id') );
$password = clean( filter_input(INPUT_POST,'authentication_password') );

if ( isset($_POST['submit_login']) ) {
    if( isset($hashes["$adminid"]) ){
        $set_password = $hashes["$adminid"];
    }
    if(validate_token( filter_input(INPUT_POST, 'token') ) && password_verify($password,$set_password) ){
        session_regenerate_id(true);
        $_SESSION['loggedin'] = true;
        $system_msg = 'ログインに成功しました';
    }else{
        $error_login = "IDかパスワードに誤りがあります";
    }
}

//ログアウト
if( isset($_POST['submit_logout']) ){
    unset($_SESSION['loggedin']);
}

//データ編集

if( isset($_POST['submit_admin_delete']) ){
    $control_id = clean($_POST['submit_admin_control_id']);
    $control_id_key = "ID".$control_id;
    try {
        $stmt = $pdo->prepare("DELETE FROM ECsite WHERE id = ?");
        $stmt->execute([$control_id]);
        unset($_SESSION['product_number']["$control_id_key"]);
        $system_msg = '商品を削除しました';
    } catch (Exception $e) {
        echo $e->getMessage() . PHP_EOL;
    }
}

//1ページごとの表示数
$perpage = 10;
require 'paging.php';

//商品の表示用データの取得
try {
    $offset = ($page - 1) * 10;
    $stmt = $pdo->prepare("SELECT * FROM ECsite ORDER BY id DESC LIMIT 10 OFFSET $offset");
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
    <title>おでん屋 | 管理画面</title>
</head>
<body class="preload page_admin">
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
            <h2 class="subpage_heading_pagetitle">管理画面</h2>
        </div>
    </header>
    <main>
        <div class="admin_enclosure">
            <?php if( isset($_SESSION['loggedin']) ) : ?>
                <div class="admin_wrapper">
                    <?php if( !empty($system_msg) ){
                        echo "<div class='system_msg_admin'>".$system_msg."</div>";
                        }
                    ?>
                    <table class="admin_conteiner">
                        <?php if( !empty($row) ): ?>
                            <tr>
                                <th>商品画像</th>
                                <th>商品名</th>
                                <th>単価</th>
                                <th>説明文</th>
                                <th></th>
                            </tr>
                            <?php foreach($row as $rowdata){ ?>
                                <tr>
                                    <td class="admin_image">
                                        <?php echo image_number($rowdata['id']); ?>
                                    </td>
                                    <td><?php echo $rowdata['name']; ?></td>
                                    <td><?php echo $rowdata['price']; ?></td>
                                    <td><?php echo $rowdata['caption']; ?></td>
                                    <td>
                                        <form action="edit.php?id=<?php echo $rowdata['id'] ?>" method="post" class="admin_control_change">
                                            <input type="hidden" name="submit_admin_control_id" value="<?php echo $rowdata['id'] ?>">
                                            <button type="submit" name="submit_admin_change">
                                                <span>変更</span>
                                            </button>
                                        </form>
                                        <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] );?>" method="post" class="admin_control_delete">
                                            <input type="hidden" name="submit_admin_control_id" value="<?php echo $rowdata['id'] ?>">
                                            <button type="submit" name="submit_admin_delete">
                                                <span>削除</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php else: ?>
                            <tr>
                                <td>商品が登録されていません。</td>
                            </tr>
                        <?php endif ; ?>
                        <tr>
                            <td class="new_register" colspan="5">
                                <form action="edit.php?id=new" method="post" class="new_register">
                                    <button type="submit" name="submit_admin_new_register">
                                        <span>新規登録</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                     </table>
                     <!--            ページャー-->
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
                 </div>
            <?php else: ?>
                <div class="admin_wrapper_login">
                    <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] );?>" method="post" class="admin_authentication">
                        <input type="text" id="admin_id" name="authentication_id" placeholder="ID" value="">
                        <input type="password" id="admin_password" name="authentication_password" placeholder="パスワード" value="">
                        <?php if( !empty($error_login) ){
                            echo "<div class='error_msg_admin'>".$error_login."</div>";
                            }
                        ?>
                        <input type="hidden" name="token" value="<?=clean(generate_token())?>">
                        <button type="submit" name="submit_login">
                            <span>ログイン</span>

                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <?php if( isset($_SESSION['loggedin']) ) : ?>
            <div class="admin_link">
                <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] );?>" method="post" class="admin_logout">
                    <button type="submit" name="submit_logout">
                                <span>ログアウト</span>
                    </button>
                </form>
            </div>
        <?php endif; ?>
        <div class="copyright"> copyright &copy; Tomo Soizumi.<br class="indention_sp">all rights reserved.</div>
    </footer>
</body>
</html>