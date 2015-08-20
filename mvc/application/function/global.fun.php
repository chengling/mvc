<?php 
/**
 
 *  PHP-HTTP断点续传实现
 
 *  @param string $path: 文件所在路径
 
 *  @param string $file: 文件名
 
 *  @return void
 
 */
 
function download($path,$file) {
 
    $real = $path.'/'.$file;
 
    if(!file_exists($real)) {
 
        return false;
 
    }
 
    $size = filesize($real);
 
    $size2 = $size-1;
 
    $range = 0;
 
    if(isset($_SERVER['HTTP_RANGE'])) {
 
        header('HTTP /1.1 206 Partial Content');
 
        $range = str_replace('=','-',$_SERVER['HTTP_RANGE']);
 
        $range = explode('-',$range);
 
        $range = trim($range[1]);
 
        header('Content-Length:'.$size);
 
        header('Content-Range: bytes '.$range.'-'.$size2.'/'.$size);
 
    } else {
 
        header('Content-Length:'.$size);
 
        header('Content-Range: bytes 0-'.$size2.'/'.$size);
 
    }
 
    header('Accenpt-Ranges: bytes');
 
    header('application/octet-stream');
 
    header("Cache-control: public");
 
    header("Pragma: public");
 
    //解决在IE中下载时中文乱码问题
 
    $ua = $_SERVER['HTTP_USER_AGENT'];
 
    if(preg_match('/MSIE/',$ua)) {
 
        $ie_filename = str_replace('+','%20',urlencode($file));
 
        header('Content-Dispositon:attachment; filename='.$ie_filename);
 
    }  else {
 
        header('Content-Dispositon:attachment; filename='.$file);
 
    }
 
    $fp = fopen($real,'rb+');
 
    fseek($fp,$range);
 
    while(!feof($fp)) {
        set_time_limit(0);
 
        print(fread($fp,1024));
 
        flush();
 
        ob_flush();
 
    }
 
    fclose($fp);
 
}
 
/*End of PHP*/