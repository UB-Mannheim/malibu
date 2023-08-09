#!/usr/bin/env php
<?php
// usage: php getBNBData.php [target directory]
//
//        if the target directory is omitted, ./BNBDaten is used by default

// adjust if necessary
$debug = false;
$maxretry = 5;
$datadir = count($argv) < 2 ? './BNBDatenTest' : $argv[1];
$urlbase = 'https://www.bl.uk/collection-metadata';
$urlpath = '/new-bnb-records';

// check for required extensions
foreach (['curl', 'zip'] as $ext) {
    if (!extension_loaded($ext)) {
        echo "Missing extension $ext" . PHP_EOL;
        exit(1);
    }
}

function request_wrapper($url, $file = null)
{
    global $debug, $maxretry;
    for ($retry =0; $retry < $maxretry; ++$retry) {
        $ch = curl_init();
        $opt_array = [
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
        ];
        if ($file) {
            $opt_array[CURLOPT_FILE] = $file;
        } else {
            $opt_array[CURLOPT_RETURNTRANSFER] = true;
        }
        curl_setopt_array($ch, $opt_array);
        $data = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        switch ($status) {
            case 429:
                if ($debug) {
                    printf("Got status code %d, retry %d", $status, $retry+1);
                }
                sleep($retry+1);
                continue 2;
            case 200:
                if ($debug) {
                    echo "Got " . strlen($data) . " bytes,"
                                . " type $content_type" . PHP_EOL;
                }
                return [ $data, $content_type ];
            default:
                echo "Status code $status for $url" . PHP_EOL;
                break;
        }
    }
    return false;
}
    
function get_files($page)
{
    global $urlbase;
    $ret = [];
    
    $doc = new DOMDocument();
    $doc->loadHTML($page, LIBXML_NOERROR);
    $xpath = new DOMXpath($doc);
    $li_els = $xpath->query('.//li[contains(text(), "bnbrdf")]');
    if ($li_els === false) {
        echo "No files found. Parse error?" . PHP_EOL;
        return false;
    }
    foreach ($li_els as $el) {
        $rdffile = preg_replace(
            '/.*(bnbrdf_n\d+)\.zip.*/i',
            '$1.rdf',
            $el->nodeValue
        );
        foreach ($el->childNodes as $child_el) {
            if ($child_el->nodeName != "a") {
                continue;
            }
            $zipurl = $child_el->getAttribute("href");
            if (!str_starts_with($zipurl, "http")) {
                $zipurl = $urlbase . "/" . $zipurl;
            }
            array_push($ret, [$zipurl, $rdffile]);
        }
    }
    return $ret;
}

// create datadir if necessary and get list of existing files
if (!is_dir($datadir)) {
    mkdir($datadir, 0777, true);
}

$locfiles = array_map(
    function ($k) {
        return strtolower($k);
    },
    scandir($datadir)
);
$locfiles = array_filter(
    $locfiles,
    function ($k) {
        return str_ends_with($k, ".rdf");
    }
);

// retrieve page
$pageurl = $urlbase . $urlpath;
if ($debug) {
    echo "Downloading summary page $pageurl" . PHP_EOL;
}

$page = request_wrapper($pageurl);
if ($page === false) {
    echo "Error downloading $pageurl" . PHP_EOL;
    exit(1);
}

// extract file links
if ($debug) {
    echo "Parsing result and extracting links" . PHP_EOL;
}
$files = get_files($page[0]);
if ($files === false) {
    echo "Error getting file links from $pageurl" . PHP_EOL;
    exit(1);
}

// download files
$retcode = 0;
foreach ($files as [$zipurl, $rdffile]) {
    if (in_array(strtolower($rdffile), $locfiles)) {
        continue;
    }
    if ($debug) {
        echo "Downloading $rdffile from $zipurl" . PHP_EOL;
    }
    $temp_file = tempnam(sys_get_temp_dir(), 'BNB_');
    $temp_fp = fopen($temp_file, 'wb');
    $r = request_wrapper($zipurl, $temp_fp);
    fclose($temp_fp);
    if ($r === false) {
        echo "Error downloading $rdffile" . PHP_EOL;
        $retcode += 1;
        unlink($temp_file);
        continue;
    }
    if ($r[1] != 'application/x-zip-compressed') {
        echo "Warning: Unexpected content type {$r[1]} for $zipurl" . PHP_EOL;
        $retcode += 1;
        unlink($temp_file);
        continue;
    }
    $zip = new ZipArchive;
    $res = $zip->open($temp_file);
    if ($res !== true) {
        echo "Opening of zip file for $rdffile failed" . PHP_EOL;
        $retcode += 1;
        unlink($temp_file);
        continue;
    }

    $count = $zip->count();
    if ($count == 0) {
        echo "Empty zip file for $rdffile" . PHP_EOL;
        $retcode += 1;
        $zip->close();
        unlink($temp_file);
        continue;
    } elseif ($count > 1) {
        echo "WARNING: " . $zip->count() . " files in zip file for $rdffile"
                         . PHP_EOL;
    }
    
    $zip_rdf = $zip->getStreamIndex(0, ZipArchive::FL_UNCHANGED);
    if (!$zip_rdf) {
        echo $z->getStatusString() . PHP_EOL;
        $retcode += 1;
        $zip->close();
        unlink($temp_file);
        continue;
    }
    $outfile_name = $datadir . '/' . $rdffile;
    if (file_put_contents($outfile_name, $zip_rdf) === false) {
        echo "Error writing $outfile_name" . PHP_EOL;
        $retcode += 1;
        fclose($zip_rdf);
        $zip->close();
        unlink($temp_file);
        continue;
    }
    fclose($zip_rdf);
    $stat = $zip->statIndex(0, ZipArchive::FL_UNCHANGED);
    $zip->close();
    touch($outfile_name, $stat["mtime"]);
    unlink($temp_file);
}
// if any problems occured during download or extraction, retcode is > 0
exit($retcode);
?>;
