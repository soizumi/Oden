$(window).on('load',function(){
        $('body').removeClass('preload');
        $('body').removeClass('preload');
        
        //購入画面・エラーの出た部分のinputにclass追加
        if( document.URL.match( "/casher") ){
            $(".form_element_wrapper").each(function(){
                if( $(this).children(".error_msg").length ){
                    $(this).children("input").css( "background","#ffe9e4" );
                }
            });
            if( $("#user_mail_confirmation").nextAll(".error_msg").length ){
                $("#user_mail").css( "background","#ffe9e4" );
            }
        }
      });
      
$(function() {
//10+ならセレクトボックスをテキストボックスへ変換、トップページ
    $('.product_form .number_wrapper select[name="number"]').change(function(){
        var number = $(this).val();
        if( number == "10+" ){
            $(this).removeAttr('name','number');
            $(this).hide();
            $(this).parent('.number_wrapper').append('<input type="number" pattern="\d*" name="number" value="10" >');
            $(this).parent('.number_wrapper').addClass("number_wrapper_changed");
            $(this).parent('.number_wrapper').removeClass("number_wrapper");
        }
    });
//10+ならセレクトボックスをテキストボックスへ変換、数値変更時、更新ボタン表示、カート
    $('.cart_product_control .number_wrapper_cart select[name="number"]').change(function(){
        if( !($(this).parent('.number_wrapper_cart').nextAll('input[name="submit_update"]').length) ){
            $(this).parent('.number_wrapper_cart').after('<input type="submit" name="submit_update" value="更新" >');
        }
        var number = $(this).val();
        if( number == "10+" ){
            $(this).removeAttr('name','number');
            $(this).hide();
            $(this).parent('.number_wrapper_cart').append('<input type="number" pattern="\d*" name="number" value="10" >');
            $(this).parent('.number_wrapper_cart').addClass("number_wrapper_cart_changed");
            $(this).parent('.number_wrapper_cart').removeClass("number_wrapper_cart");
        }
    });
        $('.cart_product_control .number_wrapper_cart_10over input[name="number"]').on({
        "input" : function(e) {
            if( !($(this).parent('.number_wrapper_cart_10over').nextAll('input[name="submit_update"]').length) ){
                $(this).parent('.number_wrapper_cart_10over').after('<input type="submit" name="submit_update" value="更新" >');
            }
        }
    });
    
    $(document).on("keypress","input[name='number'], input[name='casher_postcode'], input[name='casher_phonenumber'], input[name='edit_price']", function(event){return leaveOnlyNumber(event);});
    
    $(document).on("blur","input[name='number'], input[name='casher_postcode'], input[name='casher_phonenumber'], input[name='edit_price']", function(event){return leaveOnlyNumber(event);});

    function leaveOnlyNumber(e){
      // 数字以外の不要な文字を削除
      var st = String.fromCharCode(e.which);
      if ("0123456789".indexOf(st,0) < 0) { return false; }
      return true;
    }
  //ファイルアップロードボタン
  $('.input_edit_image_button').on('click',function() {
        $('#input_edit_image').trigger('click');
     });

  $('#input_edit_image').on('change',function() {
      var val = $(this).val();
      var path = val.replace(/\\/g, '/');
      var match = path.lastIndexOf('/');
      $("#input_edit_image_filename").val(match !== -1 ? val.substring(match + 1) : val);
  });
  
  //画像ファイルをアップロード前にプレビュー
  $('#input_edit_image').change(function(e){
    //ファイルオブジェクトを取得する
    var file = e.target.files[0];
    var reader = new FileReader();
 
    //画像でない場合は処理終了
    var permit_type = ['image/jpeg', 'image/png', 'image/gif'];
    if(file && permit_type.indexOf(file.type) == -1){
      return false;
    }
 
    //アップロードした画像を設定する
    reader.onload = (function(file){
      return function(e){
        $(".product_image_genereted").attr("src", e.target.result);
        $(".product_image_genereted").attr("title", file.name);
      };
    })(file);
    reader.readAsDataURL(file);
 
  });

});