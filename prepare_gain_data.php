<?php
/**
 * @file extractor.php
 * Extract tick data from gaincapital repository.
 * Syntax: extractor.php PAIR FROM TO
 * PAIR = EURUSD for instance
 * FROM = YYYYMM like 200901
 * TO = YYYYM like 200906
 * cli usage only
 * requires ZIP and ICONV extensions
 * */
define('RATEDATABASE', './');
ini_set('memory_limit', '128M');


// FUNCTIONS HERE
function process($ye, $mo) {
    global $pair;
    echo "processing [$ye]/[$mo]\n";

    // check if we have dir for this year and month
    switch($mo) {
        case 1:
            $mo2="01 January";
            break;
        case 2:
            $mo2="02 February";
            break;
        case 3:
            $mo2="03 March";
            break;
        case 4:
            $mo2="04 April";
            break;
        case 5:
            $mo2="05 May";
            break;
        case 6:
            $mo2="06 June";
            break;
        case 7:
            $mo2="07 July";
            break;
        case 8:
            $mo2="08 August";
            break;
        case 9:
            $mo2="09 September";
            break;
        case 10:
            $mo2="10 October";
            break;
        case 11:
            $mo2="11 November";
            break;
        case 12:
            $mo2="12 December";
            break;
        default:
            echo "fuck $mo";
            exit(1);
    }

    $path=RATEDATABASE."$ye/$mo2/";
    //echo "$path\n";
    if (!is_dir($path)) {
        echo "error: we dont have path [$path]\n";
        exit(1);
    }

    // check if we have da file
    $pair2=substr($pair, 0, 3)."_".substr($pair, 3);
    //echo "$pair2";

    $fullfile = "$path{$pair2}.zip";
    if (file_exists($fullfile)) {
        processZip($fullfile, $csvname);
    }
    else {
        // now check for weeks
        for($week=1;$week<=5;$week++) {
            $fname="$path{$pair2}_Week$week.zip";
            if (!file_exists($fname)) break;
            $csvname="{$pair2}_Week$week.csv";
            processZip($fname, $csvname);

        }
        if ($week < 4) {
            $die = true;
            if ($week == 2) {
                $fname="$path{$pair2}_Week2-4.zip"; // fix for 2004/03
                if (file_exists($fname)) {
                    $csvname="{$pair2}_Week2-4.csv";
                    processZip($fname, $csvname);
                    $die = false;
                }
            }
            if ($die) {
                die("error: week $week not found for $ye/$mo2/$pair2\n");
            }
        }
    }

}

function processZip($fname, $csvname) {
    global $outf;
    echo "process zip [$fname] [$csvname]\n";

    $zip = new ZipArchive;
    $res = $zip->open($fname);
    $csvname = $zip->getNameIndex(0);
    if ($res!==true) {
        echo "failed to open [$fname] code [$res]\n";
        exit(1);
    }
    $fp = $zip->getStream($csvname);
    if (!$fp) {
        echo "unable to get stream\n";
        exit(1);
    }
    echo "actual CSV name: $csvname\n";
    $contents=stream_get_contents($fp);
    // convert to ascii if needed
    if (substr($contents, 0, 4)=='lTid') {
        // we dont convert from unicode, however we need to cut the first line off
        // so lets find the first \r\n
        $lbrpos=strpos($contents, "\r\n");
        //echo "lbrpos: $lbrpos\n";
        if ($lbrpos!==false) {
            $contents=substr($contents, $lbrpos+2);
        }
    } else {
        if (substr($contents, 0, 1)==chr(0xFF)) {
            $contents=iconv("UTF-16", "ASCII", $contents);
        }
    }

    if (strlen($contents)==0) {
        echo "error: empty content\n";
        exit(1);
    }

    echo substr($contents, 0, 1024)."\n";
    // write to outf
    fputs($outf, $contents);
    $zip->close();
}



// RUNNING FROM HERE

// load parameters
if ($argc!=4) {
    echo "argument count invalid\n";
    exit(1);
}

// get parameters and sanity check them
$pair=$argv[1];
if (!preg_match("/[A-Z]{6}/", $pair)) {
    echo "invalid pair\n";
    exit(1);
}

$from=$argv[2];
if (!preg_match("/\d{6}/", $from)) {
    echo "invalid from specification\n";
    exit(1);
}
$fromint=intval($from);

$to=$argv[3];
if (!preg_match("/\d{6}/", $to)) {
    echo "invalid from specification\n";
    exit(1);
}
$toint=intval($to);

if ($fromint>$toint) {
    echo "from>to!\n";
    exit(1);
}

echo "pair [$pair] from [$from] to [$to]\n";

$yearfrom=intval(substr($from, 0, 4));
$yearto=intval(substr($to, 0, 4));
$monthfrom=intval(substr($from, 4));
if ($monthfrom<1 or $monthfrom>12) {
    echo "invalid month in from\n";
    exit(1);
}

$monthto=intval(substr($to, 4));
if ($monthto<1 or $monthto>12) {
    echo "invalid month in to\n";
    exit(1);
}
echo "$yearfrom/$monthfrom to $yearto/$monthto\n";

// open out file
$outfname="{$pair}_ticks.csv";
$outf=fopen($outfname, "w");

// lets LOOP
$ye=$yearfrom;
$mo=$monthfrom;
while($ye<=$yearto) {
    if ($ye==$yearto and $mo>$monthto) break;
    process($ye, $mo);

    $mo++;
    if ($mo>12) {
        $mo=1;
        $ye++;
    }

}

fclose($outf);

// all done
exit(0);


?>