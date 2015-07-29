<?php
ini_set('memory_limit','1024M');
if ($argc!=3) {
    echo "Usage: ".__FILE__." <input.csv> <output.csv>\n";
    exit(1);
}

if (!file_exists($argv[1])) {
    echo "Cannot find ".$argv[1]."\n";
    exit(1);
}

$outfd = fopen('out.csv','w+');
if (!$outfd) {
    echo "Cannot write to ".$argv[2]."\n";
    exit(1);
}

$fd = fopen($argv[1], 'r');
echo "Reading...\n";
$arr = array();
while($line = fgets($fd))
{
    $s = explode(',',$line);
    $p = strptime($s[2], '%Y-%m-%d %H:%M:%S');
    $t = mktime($p['tm_hour'], $p['tm_min'], $p['tm_sec'], $p['tm_mon'] + 1, $p['tm_mday'], $p['tm_year'] + 1900);
    $arr[$t] = $line;
    unset($t);
    unset($s);
    unset($p);
}
fclose($fd);
echo "Sorting...\n";
ksort($arr);
echo "Saving...\n";
foreach($arr as $t => $v) {
    fwrite($outfd, $v);
    if ($prevt == 0) {
        $prevt = $t;
    }
    if ($t - 300 > $prevt) {
        echo "Possible error at " . strftime("%D %T %A", $prevt) . " -> " . strftime("%D %T %A", $t) . "\n";
    }
    $prevt = $t;
}
fclose($outfd);
?>
