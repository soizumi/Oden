<?php

require 'config.php';
require 'common.php';

//PHPmailer
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/POP3.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/OAuth.php';
require 'PHPMailer/language/phpmailer.lang-ja.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$name = $postcode = $address = $phonenumber = $mail = $mail_confirmation = "";

//送信データを変数に格納
if( isset($_POST['submit_confirm']) || isset($_POST['submit_return']) || isset($_POST['submit_finaldecision']) ) {
    $buydata = array_map('htmlspecialchars',$_POST);
    $name = clean($_POST["casher_name"]);
    $postcode = clean($_POST["casher_postcode"]);
    $address =  clean($_POST["casher_address"]);
    $phonenumber = clean($_POST["casher_phonenumber"]);
    $mail = clean($_POST["casher_mail"]);
    $mail_confirmation = clean($_POST["casher_mail_confirmation"]);
    $salesamount = clean($_POST["casher_total"]);
    //バリデーション
    $errors = varidation($buydata);
}

function varidation($buydata){
    $errors = array();
        if( empty($buydata["casher_name"]) ){
            $errors['error_name'] = 'お名前をご入力ください';
        }
        if( empty($buydata['casher_postcode']) ){
            $errors['error_postcode'] = '郵便番号をご入力ください';
        }elseif (!preg_match('/^[0-9０-９\ー-]+$/u', $buydata['casher_postcode'])) {
            $errors['error_postcode'] = '郵便番号にハイフン、数字以外が含まれています';
        }
        if( empty($buydata['casher_address']) ){
            $errors['error_address'] = '住所をご入力ください';
        }
        if( empty($buydata['casher_phonenumber']) ){
            $errors['error_phone_number'] = 'お電話番号をご入力ください';
        }elseif (!preg_match('/^[0-9０-９\ー-]+$/u', $buydata['casher_phonenumber'])) {
            $errors['error_phone_number'] = 'お電話番号にハイフン、数字以外が含まれています';
        }
        if( empty($buydata['casher_mail']) ){
            $errors['error_mail'] = 'メールアドレスをご入力ください';
        }elseif( empty($buydata['casher_mail_confirmation']) ){
            $errors['error_mail'] = '確認用メールアドレスをご入力ください';
        }elseif( $buydata['casher_mail'] != $buydata['casher_mail_confirmation'] ){
            $errors['error_mail'] = 'メールアドレスが一致しません';
        }elseif (!preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/', $buydata['casher_mail'])) {
          $errors['error_mail'] = 'メールアドレスは正しい形式で入力してください';
        }
        return $errors;
}
    
if( empty( $errors ) &&  ( isset($buydata["submit_confirm"]) || isset($buydata["submit_finaldecision"]) ) ){
    $display_flag = 1;
}else{
    $display_flag = 0;
}

if( $display_flag == 1 && isset($buydata['submit_finaldecision']) && !isset($buydata['submit_return']) ){
//データベースへ注文内容格納
    $numbers_column = "";
    $numbers_value = "";
    $keys = array_keys($buydata);
    $casher_number_keys  = preg_grep ('/^casher_number_/i', $keys);
    foreach( $casher_number_keys as $key => $value ){
        $value_str =  str_replace('casher_number_ID','',$value);
        try {
            $stmt = $pdo->prepare("SELECT * FROM ECsite WHERE id = ?");
            $stmt->execute([$value_str]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
        $value_sum = $buydata["$value"] * $row['price'];
        $numbers_column .= ", product_ID".$value_str.", product_ID".$value_str."_sales";
        $numbers_value .= ", ".$buydata["$value"].", ".$value_sum;
    }
    try {
        $stmt = $pdo->prepare("insert into ECsite_orderData(name, postalcode, address, phonenumber, mail, salesamount $numbers_column) value(?, ?, ?, ?, ?, ? $numbers_value)");
        $stmt->execute([$name, $postcode, $address, $phonenumber, $mail, $salesamount]);
    } catch (\Exception $e) {
        echo $e->getMessage() . PHP_EOL;
    }
    
//メール送信
    //メールのステータス生成
    $subject_user = "注文を受け付けました【おでん処　祖泉】";
    $subject_admin = "WEBサイトから商品の発注がありました【おでん処　祖泉】";
    
    $automail_admin_start = "下記のお客様よりご注文をいただきました。"."\n\n"."
    ━━━━━━□■□　ご注文内容　□■□━━━━━━"."\n\n";
    $automail_user_start = '※このメールはシステムからの自動返信です。'."\n\n".'ご注文ありがとうございました。'."\n\n".'以下の内容でご注文を受け付けいたしました。'."\n\n".'━━━━━━□■□　ご注文内容　□■□━━━━━━'."\n\n";
    $automail_end = '━━━━━━━━━━━━━━━━━━━━━━━━━━━━';
    
    $body = "";
    
    if( isset($name) ){
        $body .= "お名前： ".$name."\n\n";
    }
    if( isset($postcode) ){
        $body .= "郵便番号： ".$postcode."\n\n";
    }
    if( isset($address) ){
        $body .= "ご住所： ".$address."\n\n";
    }
    if( isset($phonenumber) ){
        $body .= "お電話番号： ".$phonenumber."\n\n";
    }
    if( isset($mail) ){
        $body .= "メールアドレス： ".$mail."\n\n";
    }
    if( isset($casher_number_keys) ){
        $body .= "□□□注文内容□□□"."\n\n";
        foreach( $casher_number_keys as $key => $value ){
            $item_name_key =  str_replace('casher_number_ID','',$value);
            try {
                $stmt = $pdo->prepare("SELECT * FROM ECsite WHERE id = ?");
                $stmt->execute([$item_name_key]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
            $item_name = $row['name'];
            $item_number = $buydata["$value"];
            $body .= $item_name."： 数量 / ".$item_number."\n";
        }
        $body .= "\n";
    }
    if( isset($salesamount) ){
        $body .= "購入代金： ¥".$salesamount."円"."\n\n";
    }
    
    $automail_admin =$automail_admin_start.$body.$automail_end;
    $automail_user =$automail_user_start.$body.$automail_end;
    
    //PHPmailerで送信
    $mailer_admin = new PHPMailer();//インスタンス生成
    $mailer_admin->IsSMTP();//SMTPを作成
    $mailer_admin->Host = 'smtp.gmail.com';//Gmailを使うのでメールの環境に合わせてね
    $mailer_admin->CharSet = 'utf-8';//文字セットこれでOK
    $mailer_admin->SMTPAuth = TRUE;//SMTP認証を有効にする
    $mailer_admin->Username = 'amadatarou.hachiouji@gmail.com'; // Gmailのユーザー名
    $mailer_admin->Password = '2Q7GZPVz'; // Gmailのパスワード
    $mailer_admin->SMTPSecure = 'tls';//SSLも使えると公式で言ってます
    $mailer_admin->Port = 587;//tlsは587でOK
//    $mailer_admin->SMTPDebug = 2;//2は詳細デバッグ1は簡易デバッグ本番はコメントアウトして
    $mailer_admin->From     = 'amadatarou.hachiouji@gmail.com'; //差出人の設定
    $mailer_admin->FromName = mb_convert_encoding("おでん処　祖泉　WEBサイト","UTF-8","AUTO");//表示名おまじない付…
    $mailer_admin->Subject  = mb_convert_encoding($subject_admin,"UTF-8","AUTO");//件名の設定
    $mailer_admin->Body     = mb_convert_encoding($automail_admin,"UTF-8","AUTO");//メッセージ本体
    $mailer_admin->AddAddress("amadatarou.hachiouji@gmail.com"); // To宛先
    $mailer_admin->Send();
    
    $mailer_user = new PHPMailer();//インスタンス生成
    $mailer_user->IsSMTP();//SMTPを作成
    $mailer_user->Host = 'smtp.gmail.com';//Gmailを使うのでメールの環境に合わせてね
    $mailer_user->CharSet = 'utf-8';//文字セットこれでOK
    $mailer_user->SMTPAuth = TRUE;//SMTP認証を有効にする
    $mailer_user->Username = 'amadatarou.hachiouji@gmail.com'; // Gmailのユーザー名
    $mailer_user->Password = '2Q7GZPVz'; // Gmailのパスワード
    $mailer_user->SMTPSecure = 'tls';//SSLも使えると公式で言ってます
    $mailer_user->Port = 587;//tlsは587でOK
//    $mailer_user->SMTPDebug = 2;//2は詳細デバッグ1は簡易デバッグ本番はコメントアウトして
    $mailer_user->From     = 'amadatarou.hachiouji@gmail.com'; //差出人の設定
    $mailer_user->FromName = mb_convert_encoding("おでん処　祖泉　WEBサイト","UTF-8","AUTO");//表示名おまじない付…
    $mailer_user->Subject  = mb_convert_encoding($subject_user,"UTF-8","AUTO");//件名の設定
    $mailer_user->Body     = mb_convert_encoding($automail_user,"UTF-8","AUTO");//メッセージ本体
    $mailer_user->AddAddress($mail); // To宛先
    $mailer_user->Send();
    
    $_SESSION['product_number'] = array();
    $display_flag = 2;
}

if($display_flag == 0){
    $current_page = "お届け先の入力";
}elseif($display_flag == 1){
    $current_page = "入力内容の確認";
}elseif($display_flag == 2){
    $current_page = "購入処理完了";
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
    <title><?php echo $current_page ?> | おでん処　祖泉</title>
</head>
<body class="preload">
    <header>
        <div class="header_menu">
        <h1><a href="index.php"><img src="img/logo.png" alt=""></a></h1>
            <div class="cart">
                <?php if( $display_flag == 2 || empty($_SESSION['product_number']) ): ?>
                <a href="index.php" >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 83.51 76.25" class="icon_home"><defs><style>.cls-1{fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:5px;}</style></defs><title></title><g id="" data-name=""><g id="" data-name=""><polyline class="cls-1" points="2.5 34.76 41.76 2.5 81.01 34.76"/><polyline class="cls-1" points="2.5 51.76 41.76 19.5 81.01 51.76"/><line class="cls-1" x1="13.71" y1="42.57" x2="13.71" y2="73.75"/><line class="cls-1" x1="69.8" y1="42.57" x2="69.8" y2="73.75"/><path class="cls-1" d="M31.25,73.25v-22c0-5,6-9,11-9h0c5,0,11,4,11,9v22"/></g></g></svg>
                <div class="text_button_attached">ホームへ</div>
                </a>
                <?php else: ?>
                <a href="cart.php" >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 77.5 66.5" class="icon_cart"><title></title><g id="" data-name=""><g id="" data-name=""><path d="M77.37,1.3A2,2,0,0,0,74.8.13l-8,3a2,2,0,0,0-1.19,1.21L63.82,9.5H2A2,2,0,0,0,.14,12.24l12,30A2,2,0,0,0,14,43.5H52L50.08,49H15.5a2,2,0,0,0,0,4h36a2,2,0,0,0,1.89-1.34L69.08,6.55,76.2,3.87A2,2,0,0,0,77.37,1.3Zm-62,38.2L5,13.5H62.43l-9,26Z"/><circle cx="20" cy="61" r="5.5"/><circle cx="46" cy="61" r="5.5"/></g></g></svg>
                <?php
                    if( !empty($_SESSION['product_number']) ){
                        echo "<div class='cart_status'><span>".array_sum($_SESSION['product_number'])."</span></div>" ;
                    }
                ?>
                <div class="text_button_attached">カートへ</div>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="subpage_pagetitle">
            <h2 class="subpage_heading_pagetitle"><?php echo $current_page ?></h2>
        </div>
    </header>
    <main>
        <div class="product_wrapper">
            <div class="cart_wrapper">
            <?php if( !empty($_SESSION['product_number']) || isset($buydata['submit_finaldecision']) ): ?>
                <?php if($display_flag != 2): ?>
                    <?php if($display_flag != 1): ?>
                    <h2 class="h2_casher">お届けに必要な情報を<br class="indention_sp">下記よりご入力ください</h2>
                    <?php endif; ?>
                    <form class="form_casher<?php if($display_flag == 1){ echo " casher_confirmation"; } ?>" action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] );?>" method="post">
                        <div class="form_element_wrapper">
                            <label for="user_name">お名前<span class="required">必須</span></label>
                            <?php if($display_flag != 1): ?>
                                <input type="text" id="user_name" name="casher_name" placeholder="例)山田太郎" value="<?php echo $name ; ?>">
                            <?php else: ?>
                                <div class="confirmation_contents"><?php echo $name ; ?></div>
                            <?php endif; ?>
                            <?php
                                if( !empty($errors["error_name"]) ){
                                    echo "<div class='error_msg'>".$errors['error_name']."</div>";
                                }
                            ?>
                        </div>
                        <div class="form_element_wrapper">
                            <label for="user_postcode">郵便番号<span class="required">必須</span></label>
                            <?php if($display_flag != 1): ?>
                                <input type="text" id="user_postcode" name="casher_postcode" placeholder="ハイフンなし数字7桁" value="<?php echo $postcode ; ?>">
                            <?php else: ?>
                                <div class="confirmation_contents"><?php echo $postcode ; ?></div>
                            <?php endif; ?>
                            <?php
                                if( !empty($errors["error_postcode"]) ){
                                    echo "<div class='error_msg'>".$errors['error_postcode']."</div>";
                                }
                            ?>
                        </div>
                        <div class="form_element_wrapper">
                            <label for="user_address">住所<span class="required">必須</span></label>
                            <?php if($display_flag != 1): ?>
                                <input type="text" id="user_address" name="casher_address" placeholder="都道府県、市区町村、番地、建物名" value="<?php echo $address ; ?>">
                            <?php else: ?>
                                <div class="confirmation_contents"><?php echo $address ; ?></div>
                            <?php endif; ?>
                            <?php
                            if( !empty($errors["error_address"]) ){
                                echo "<div class='error_msg'>".$errors['error_address']."</div>";
                            }
                        ?>
                        </div>
                        <div class="form_element_wrapper">
                            <label for="user_phonenumber">電話番号<span class="required">必須</span></label>
                            <?php if($display_flag != 1): ?>
                                <input type="text" id="user_phonenumber" name="casher_phonenumber" placeholder="例)0901234567 ハイフンなし" value="<?php echo $phonenumber ; ?>">
                            <?php else: ?>
                                <div class="confirmation_contents"><?php echo $phonenumber ; ?></div>
                            <?php endif; ?>
                            <?php
                            if( !empty($errors["error_phone_number"]) ){
                                echo "<div class='error_msg'>".$errors['error_phone_number']."</div>";
                            }
                            ?>
                        </div>
                        <div class="form_element_wrapper">
                            <label for="user_mail">メールアドレス<span class="required">必須</span></label>
                            <?php if($display_flag != 1): ?>
                                <input type="text" id="user_mail" name="casher_mail"  placeholder="例)yamada@mail.com" value="<?php echo $mail ; ?>">
                            <?php else: ?>
                                <div class="confirmation_contents"><?php echo $mail ; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form_element_wrapper">
                        <?php if($display_flag != 1): ?>
                            <label for=""></label>
                            <input type="text" id="user_mail_confirmation" name="casher_mail_confirmation" placeholder="上と同じものをご入力ください" value="<?php echo $mail_confirmation ; ?>">
                        <?php endif; ?>
                        <?php
                            if( !empty($errors["error_mail"]) ){
                                echo "<div class='error_msg '>".$errors['error_mail']."</div>";
                            }
                        ?>
                        </div>
                            <h3 class="h3_casher">購入商品</h3>
                               <div class="goods_wrapper">
                                    <table class="goods_conteiner">
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
                                        <tr>
                                            <td class="goods_image">
                                                <?php echo image_number($key_str); ?>
                                            </td>
                                            <td><?php echo $row['name']; ?></td>
                                            <td>数量:<?php echo $data; ?></td>
                                            <td>単価:¥<?php echo $row['price']; ?></td>
                                            <td class="goods_subtotal">小計:¥<?php echo $sum; ?></td>
                                        </tr>
                                        <?php echo "<input type='hidden' id='$key' name='casher_number_$key' value='$data'>"; ?>
                                        <?php } ?>
                                      </table>
                                </div>
                        <div class="casher_product_totalprice">合計(<?php echo array_sum($_SESSION['product_number']) ; ?>点):
                            <span>
                                <?php
                                    $total_caliculated = array_sum($total);
                                    echo '¥'.$total_caliculated.'円';
                                    echo "<input type='hidden' id='user_total' name='casher_total' value='$total_caliculated'>";
                                ?>
                            </span>
                        </div>
        <!--                確認画面からフォームデータを渡す-->
                        <?php if($display_flag == 1): ?>
                            <input type="hidden" id="user_name" name="casher_name" value="<?php echo $name ; ?>">
                            <input type="hidden" id="user_postcode" name="casher_postcode" value="<?php echo $postcode ?>">
                            <input type="hidden" id="user_address" name="casher_address" value="<?php echo $address; ?>">
                            <input type="hidden" id="user_phonenumber" name="casher_phonenumber" value="<?php echo $phonenumber ; ?>">
                            <input type="hidden" id="user_mail" name="casher_mail" value="<?php echo $mail ; ?>">
                            <input type="hidden" id="user_mail_confirmation" name="casher_mail_confirmation" value="<?php echo $mail_confirmation ; ?>">
                        <?php endif; ?>
                        <div class="button_wrapper">
                            <?php if($display_flag != 1): ?>
                                <button type="submit" name="submit_confirm">
                                    <span>確認画面へ</span>
                                </button>
                            <?php else: ?>
                                <button type="submit" name="submit_return">
                                    <span>戻る</span>
                                </button>
                                <button type="submit" name="submit_finaldecision">
                                    <span>購入を確定する</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="cart_empty">購入処理が完了しました。</div>
                <?php endif; ?>
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