<?php

function downloadFile ($url, $path) {

  $newfname = $path;

  $file = fopen ($url, "rb");
  if ($file) {
    $newf = fopen ($newfname, "wb");

    if ($newf)

    while(!feof($file)) {
      fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );
    }
  }

  if ($file) {
    fclose($file);
  }

  if ($newf) {
    fclose($newf);
  }
 }
$mainpath = '/home/ubuntu/memex/';
$savepath = $mainpath . 'img/';
$image_url = $_GET["url"];
$query_num = $_GET['num'];
$vis = $_GET['visualize'];
$fast = $_GET['fast'];
$nodup = $_GET['nodup'];
$dup = 1;
$dupstr = '_dup';
if ($nodup>0){
	$dup =0;
	$dupstr = '';
}

if ($query_num<1){
	$query_num = 30;
}


if ($vis<1){
	$vis = 0;
}
else{
	$vis = 1;
}
if ($fast<1){
	$fast = 0;
}
else{
	$fast = 1;
}
//echo $query_num . ' ' . $vis; 

$name = basename($image_url);
$fullname = $savepath . $name;
$pos = strrpos($fullname, ".");
if ($pos === false) { // note: three equal signs
    // not found...
        $fullname=$fullname.'.jpg';
        $pos = strrpos($fullname, ".");

}
$fullnamet = substr_replace($fullname, "_" . Rand(), $pos, 0);
downloadFile($image_url,$fullnamet);

$output = shell_exec("md5sum " . $fullnamet );

list($md5, $tmp) = split(" ", $output);
$fullname = substr_replace($fullname, "_" . $md5, $pos, 0);
if (file_exists($fullname)) {
    //echo "The file $filename exists";
	unlink($fullnamet);
} else {
    //echo "The file $filename does not exist";
	rename($fullnamet, $fullname);

}
$fgval = fopen ("global_var.json", "rb");
$gread=fread($fgval,filesize("global_var.json"));
$global_var = json_decode($gread);
if ($fast){
	$ratio = $global_var->{'fast_ratio'};
}
else {
	$ratio = $global_var->{'ratio'};
}

shell_exec("cd " . $mainpath . " && export LD_LIBRARY_PATH=/usr/local/cuda/lib64 && python getSimilar.py " . $fullname . " " . $query_num. " ".$ratio. " ".$dup);
$outname = substr_replace($fullname, "-sim_".$query_num."_".$ratio.$dupstr.".json", -4, 4);


$fout = fopen ($outname, "rb");
 if ($fout) {
	$json = fread($fout,filesize($outname));
	if ($vis==0){
		echo $json;
	}
	else {
		$obj = json_decode($json);
		echo '<font size="6"><b>Query Image</b></font><br><a href="'.$image_url.'"><img src="'.$image_url.'" style="margin:3;border:0;height:120px;" title="Query Image"></a><br><br><font size="6"><b>Query Results:</b><br>';
		$imglist = $obj->{'images'}[0]->{'similar_images'}->{'cached_image_urls'};
		$orilist = $obj->{'images'}[0]->{'similar_images'}->{'page_urls'};
		$uidlist = $obj->{'images'}[0]->{'similar_images'}->{'ht_images_id'};
		$sha1list = $obj->{'images'}[0]->{'similar_images'}->{'sha1'};

		for ($i=0; $i<sizeof($imglist); $i++) {
			$dupurl = 'getDuplicate.php?htid='.$uidlist[$i].'&visualize=1';
			echo '<a href="'.$dupurl.'"><img src="'.$imglist[$i].'" style="margin:3;border:0;height:120px;" title="'.$orilist[$i].'"></a>';
		}
	}
	
}

?>
