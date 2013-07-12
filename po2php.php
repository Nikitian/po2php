<?php
header('Content-Type: text/html; Charset=UTF-8');
ob_start();?>
<html>
    <head>
        <title>po2php converter</title>
    </head>
    <body>
        <form action="./<?php echo basename(__FILE__);?>" enctype="multipart/form-data" method="post">
            File (*.po): <input type="file" name="file" /><br />
            <fieldset>
                <legend>
                    Result *.php return to:
                </legend>
                <label><input type="radio" name="returnto" value="download" checked="checked" /> Download</label><br />
                <label><input type="radio" name="returnto" value="browser" /> Browser output</label><br />
                <label><input type="radio" name="returnto" value="file" /> File:</label> <input type="text" name="filename" value="" /><br />
            </fieldset>
            <input type="submit" />
        </form>
<?php
if(!array_key_exists('file',$_FILES)){
    ?>
<?php
    die;
}
function mb_trim( $string, $replace=null ){
    $pattern = "/(^\s+)|(\s+$)/us";
    if($replace!==null){
        $pattern = "/(^\s+)|(\s+$)|".preg_quote($replace)."/us";
    }
    $string = preg_replace( $pattern, "", $string );

    return $string;
}
$errors = array();
$file = $_FILES['file']['tmp_name'];
if($_POST['returnto']=='file'){
    if(file_exists($_POST['filename']) && !is_writable($_POST['filename'])){
        $errors[]='Result file exists and not writable';
    }
    if(!file_exists($_POST['filename']) && !is_writable(dirname($_POST['filename']))){
        $errors[]='Result file not exists, but directory not writeable';
    }
}
if(sizeof($errors)>0){
    ?>
        <div>
            Some errors:<ul><li><?php echo implode('</li><li>',$errors);?></li></ul>
        </div>
    <?php
}
else{
    $translations = array();
    $po = file($file);
    $current = null;
    $lastitem = '';
    foreach ($po as $line) {
        if (mb_substr($line,0,5) == 'msgid') {
            $current = mb_trim(mb_substr(mb_trim(mb_substr($line,5)),1,-1));
            $lastitem = 'msgid';
        }
        else if (mb_substr($line,0,6) == 'msgstr') {
            $translations[$current] = mb_trim(mb_substr(mb_trim(mb_substr($line,6)),1,-1));
            $lastitem = 'msgstr';
        }
        elseif(mb_substr($line,0,1)=='"'){
            if($lastitem == 'msgid'){
                $current .= mb_trim($line,'"');
            }
            else{
                $translations[$current] .= mb_trim($line,'"');
            }
        }
    }
    $form = ob_get_contents();
    ob_clean();
    ob_start();
    echo '<?php'.PHP_EOL.'return ';
    foreach ($translations as $msgid => $msgstr) {
        if($msgid==''){
            continue;
        }
        $lang[$msgid] = stripslashes($msgstr);
    }
    var_export($lang);
    echo ';';
    $content = ob_get_contents();
    ob_clean();
    switch($_POST['returnto']){
        case'file':file_put_contents($_POST['filename'],$content);break;
        case'browser':$form.='<textarea rows="20" cols="100">'.$content.'</textarea><br />';break;
        case'download':
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=lang.php');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: '.strlen($content));
            echo$content;
            break;
    }
    echo $form;
    echo 'Done<br /><a href="./'.basename(__FILE__).'">Back</a>';
}
?>
    </body>
</html>