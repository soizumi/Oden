<?php

require 'config.php';
require 'common.php';

//変更か新規登録か判定
$edit_id = "";
$edit_status = "商品の編集";
if( isset($_GET['id']) ){
    if( $_GET['id'] == "new" ){
        $edit_status = "商品の新規登録";
    }
}

//指定されたIDの商品が存在するか
if( isset($_GET['id']) && $_GET['id'] != "new" ){
    $edit_id = clean($_GET['id']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM ECsite WHERE id = ?");
        $stmt->execute([$edit_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if( empty($row) ){
            $error_id = "その商品IDは存在しません。";
        }
    } catch (Exception $e) {
          echo $e->getMessage() . PHP_EOL;
    }
}elseif( isset($_GET['id']) && $_GET['id'] == "new" ){
    $edit_id = "new";
}else{
    $error_id = "商品IDが不明です。";
}

//画像アップロード、変数に投稿データ格納
if( isset($_POST['submit_edit_change']) || isset($_POST['submit_edit_register']) ){
    
    $editdata = array_map('htmlspecialchars',$_POST);
    $name_edit = clean($_POST["edit_name"]);
    $price_edit = clean($_POST["edit_price"]);
    $caption_edit = clean($_POST["edit_caption"]);
    //バリデーション
    $errors = varidation($editdata);
}

//バリデーション
function varidation($editdata){
    $errors = array();
        if( $_FILES['edit_image']['error'] == 0 ){
            $imagefile_type = exif_imagetype($_FILES['edit_image']['tmp_name']);
            if( !$imagefile_type ){
                $errors['error_image'] = '画像ファイルを選択してください';
            }elseif( $imagefile_type > 4 ){
                $errors['error_image'] = 'アップロードできるのはGIFファイル、JPEGファイル、PNGファイルのみです';
            }
        }
        if( empty($editdata["edit_name"]) ){
            $errors['error_name'] = '商品名をご入力ください';
        }
        if( empty($editdata['edit_price']) ){
            $errors['error_price'] = '価格をご入力ください';
        }elseif (!preg_match('/^[0-9０-９]+$/u', $editdata['edit_price'])) {
            $errors['error_price'] = '価格に数字以外が含まれています';
        }
        if( empty($editdata['edit_caption']) ){
            $errors['error_caption'] = '説明文をご入力ください';
        }
        return $errors;
}


//エラーがなければデータベースにデータ格納、管理画面へリダイレクト
if( ( isset($_POST['submit_edit_change']) || isset($_POST['submit_edit_register']) ) && empty($errors) ){
    if( isset($_POST['submit_edit_change']) ){
        try {
            //商品情報を商品データへ登録
            $stmt = $pdo->prepare("UPDATE ECsite SET name = ?, price = ?, caption=? WHERE id = $edit_id");
            $stmt->execute([$name_edit, $price_edit, $caption_edit]);
        }catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
        $_SESSION['edit_changed'] = true;
    }
    if( isset($_POST['submit_edit_register']) ){
        try {
            //商品情報を商品データへ登録
            $stmt = $pdo->prepare("insert into ECsite(name, price, caption) value(?, ?, ?)");
            $stmt->execute([$name_edit, $price_edit, $caption_edit]);
            //注文データへID登録
            $stmt_order_latest = $pdo->prepare("SELECT id FROM ECsite ORDER by created DESC LIMIT 1");
            $stmt_order_latest->execute();
            $row_latest = $stmt_order_latest->fetch(PDO::FETCH_ASSOC);
            $id_register = $row_latest['id'];
            $stmt_order = $pdo->prepare("alter table ECsite_orderData add ( product_ID{$id_register} int(30) NOT NULL, product_ID{$id_register}_sales int(30) NOT NULL )");
            $stmt_order->execute();
            $_SESSION['edit_register'] = true;
        }catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }
    //ファイルアップロード
    if( isset($_FILES['edit_image']) && is_uploaded_file( $_FILES['edit_image']['tmp_name'] ) ){
         //既存ファイル削除
         $image_list_delete = glob("img/uploaded/*");
         $delete_image_filename  = preg_grep ("/image_ID_{$edit_id}_/i", $image_list_delete);
         if( isset($delete_image_filename) ){
             foreach( $delete_image_filename as $delete_name ){
             unlink($delete_name);
             }
         }
         //ファイル名生成
         if( isset($_POST['submit_edit_change']) ){
             $file_name = "image_ID_".$edit_id."_";
         }elseif( isset($_POST['submit_edit_register']) ){
             $file_name = "image_ID_".$id_register."_";
         }
         $file_name .= $now->format('YmdHis');
         $file_name .= mt_rand();
         switch(exif_imagetype($_FILES['edit_image']['tmp_name'])){
            case IMAGETYPE_JPEG:
                $file_name .= '.jpg';
                break;
            case IMAGETYPE_GIF:
                $file_name .= '.gif';
                break;
            case IMAGETYPE_JPEG:
                $file_name .= '.png';
                break;
         }
        move_uploaded_file ( $_FILES['edit_image']['tmp_name'], "img/uploaded/$file_name" );
    }
    //管理画面へリダイレクト
    header('Location: admin.php');
    exit;
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
    <title>管理画面 | <?php echo $edit_status ; ?></title>
</head>
<body class="preload page_edit">
    <header>
        <div class="header_menu">
        <h1><a href="index.php"><img src="img/logo.png" alt=""></a></h1>
            <div class="cart">
                <a href="admin.php" >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.34 80.34"><defs><style>.cls-1{fill:none;stroke:#231815;stroke-linejoin:round;stroke-width:4px;}</style></defs><title>アセット 13</title><g id="レイヤー_2" data-name="レイヤー 2"><g id="レイヤー_1-2" data-name="レイヤー 1"><path class="cls-1" d="M78,44.93a36.7,36.7,0,0,0,0-9.52c-2.73-1.29-6-2.46-8.29-3.78a30.31,30.31,0,0,0-2.66-6.4c.67-2.59,2.12-5.72,3.15-8.54a38.65,38.65,0,0,0-6.58-6.58c-2.82,1-5.95,2.48-8.54,3.15a30.31,30.31,0,0,0-6.4-2.66C47.39,8.3,46.22,5,44.93,2.31a36.7,36.7,0,0,0-9.52,0C34.12,5,33,8.3,31.63,10.6a30.31,30.31,0,0,0-6.4,2.66c-2.59-.67-5.72-2.12-8.54-3.15a38.65,38.65,0,0,0-6.58,6.58c1,2.82,2.48,5.95,3.15,8.54a30.31,30.31,0,0,0-2.66,6.4C8.3,33,5,34.12,2.31,35.41a36.7,36.7,0,0,0,0,9.52c2.73,1.29,6,2.46,8.29,3.78a30.31,30.31,0,0,0,2.66,6.4c-.67,2.59-2.12,5.72-3.15,8.54a38.65,38.65,0,0,0,6.58,6.58c2.82-1,5.95-2.48,8.54-3.15a30.31,30.31,0,0,0,6.4,2.66C33,72,34.12,75.3,35.41,78a36.7,36.7,0,0,0,9.52,0c1.29-2.73,2.46-6,3.78-8.29a30.31,30.31,0,0,0,6.4-2.66c2.59.67,5.72,2.12,8.54,3.15a38.65,38.65,0,0,0,6.58-6.58c-1-2.82-2.48-5.95-3.15-8.54a30.31,30.31,0,0,0,2.66-6.4C72,47.39,75.3,46.22,78,44.93Z"/><circle class="cls-1" cx="40.17" cy="40.17" r="11.2"/></g></g></svg>
                <div class="text_button_attached">管理画面</div>
                </a>
            </div>
        </div>
        <div class="subpage_pagetitle">
            <h2 class="subpage_heading_pagetitle">管理画面 | <?php echo $edit_status ; ?></h2>
        </div>
    </header>
    <main>
        <div class="product_wrapper">
            <div class="edit_wrapper">
                <?php if( isset($_SESSION['loggedin']) ) : ?>
                    <?php if( !isset($error_id) ): ?>
                        <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'])."?id=$edit_id" ; ?>" method="post" enctype="multipart/form-data" class="admin_edit">
                            <div class="edit_element_wrapper">
                                <label for="input_edit_image">商品画像</label>
                                <div class="edit_image_display">
                                    <?php echo image_number($edit_id); ?>
                                </div>
                                <div class="input_edit_image_wrapper">
                                    <input type="file" id="input_edit_image" name="edit_image">
                                    <div class="input_edit_image_button"><span>ファイルを選択</span></div>
                                    <input type="text" id="input_edit_image_filename" placeholder="選択されていません" readonly/>
                                </div>
                                <?php
                                    if( !empty($errors["error_image"]) ){
                                        echo "<div class='error_msg'>".$errors['error_image']."</div>";
                                    }
                                 ?>
                            </div>
                            <div class="edit_element_wrapper">
                                <label for="input_edit_name">商品名</label>
                                <input type="text" class="input_edit_text" id="input_edit_name" name="edit_name" placeholder="" value="<?php
                                if( isset( $name_edit ) ){
                                    echo $name_edit;
                                }elseif ( isset($row['name']) ){
                                    echo $row['name'];
                                }
                                ?>">
                                <?php
                                    if( !empty($errors["error_name"]) ){
                                        echo "<div class='error_msg'>".$errors['error_name']."</div>";
                                    }
                                 ?>
                            </div>
                            <div class="edit_element_wrapper">
                                <label for="input_edit_price">単価</label>
                                <input type="text" class="input_edit_text" id="input_edit_price" name="edit_price" placeholder="" value="<?php
                                if( isset( $price_edit ) ){
                                    echo $price_edit;
                                }elseif( isset($row['price']) ){
                                    echo $row['price'];
                                }
                                ?>">
                                <?php
                                    if( !empty($errors["error_price"]) ){
                                        echo "<div class='error_msg'>".$errors['error_price']."</div>";
                                    }
                                 ?>
                            </div>
                            <div class="edit_element_wrapper">
                                <label for="textarea_edit_caption">説明文</label>
                                <textarea class="input_edit_text" id="textarea_edit_caption" name="edit_caption" placeholder=""><?php
                                    if( isset($caption_edit) ){
                                        echo $caption_edit;
                                    }elseif( isset($row['caption']) ){
                                        echo $row['caption'];
                                    }
                                    ?></textarea>
                                <?php
                                    if( !empty($errors["error_caption"]) ){
                                        echo "<div class='error_msg'>".$errors['error_caption']."</div>";
                                    }
                                 ?>
                            </div>
                            <?php if( $_GET['id'] == "new" ): ?>
                                <button type="submit" name="submit_edit_register"><span>新規登録</span></button>
                            <?php else: ?>
                                <button type="submit" name="submit_edit_change"><span>更新</span></button>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <div><?php echo $error_id ; ?></div>
                    <?php endif; ?>
                <?php else: ?>
                    <div>このページは管理画面にログインしなければ閲覧できません。</div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <footer>
        <div class="copyright"> copyright &copy; Tomo Soizumi.<br class="indention_sp">all rights reserved.</div>
    </footer>
</body>
</html>