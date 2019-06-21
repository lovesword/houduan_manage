<?php
header("content-type:text/html;charset=utf-8");

$a = [8,9,10,11];
foreach ($a as $day) {
    $file = __DIR__."/mysql/{$day}2.sql";
    $file2 = __DIR__."/mysql/{$day}3.sql";

###判断该文件是否存在

    if(file_exists($file)){
//            $res_new = str_replace("/*!*/","",$res);  ###注意，该替换要用双引号，即"".
        $file = fopen($file, "r"); // 以只读的方式打开文件
        if(empty($file)){
            return;
        }
        $file2 = fopen($file2,"w");  ###已追加的方式打开

        $lag = false;
        while(!feof($file)) {
            $itemStr = fgets($file); //fgets()函数从文件指针中读取一行
                $itemStr = str_replace("/*!*/","",$itemStr);

//                判断数据库
            if (strpos($itemStr,'use `cs_lzywzb_co') === 0
                || strpos($itemStr,'use `ceshi56_fzh_fun') === 0
            ){
                $lag = true;
            }
            if (strpos($itemStr,'use `eolinker_os') === 0){
                $lag = false;
            }
            if ($lag){
                continue;
            }

//跳过
            if (strpos($itemStr,'/*!*/') === 0){
                continue;
            }
            if (strpos($itemStr,'# at ') === 0){
                continue;
            }
            if (strpos($itemStr,'#18') === 0){
                continue;
            }
            if (strpos($itemStr,'SET TIMESTAMP=') === 0){
                continue;
            }
            if (strpos($itemStr,'BEGIN') === 0){
                continue;
            }
            if (strpos($itemStr,'COMMIT') === 0){
                continue;
            }
            if (strpos($itemStr,'DELETE') === 0){
                continue;
            }

//补充
            if (strpos($itemStr,'INSERT') === 0){
                $itemStr .= ";";
            }
            if (strpos($itemStr,'UPDATE') === 0){
                $itemStr .= ";";
            }

            fwrite($file2,$itemStr);
        }
        fclose($file);
        fclose($file2);
    }else{
        echo "file not exists!";
    }
}

exit();
