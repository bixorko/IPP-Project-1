<?php
#$jexamxml = '/pub/courses/ipp/jexamxml/jexamxml.jar';
$jexamxml = './jexamxml/jexamxml.jar';
$parsepath = './parse.php';
$intpath = './interpret.py';
$directorypath = './';
$recursive = false;
$parseonly = false;
$intonly = false;
$parsepathbool = false;
$intpathbool = false;

$html = '';

isValidArgs();

if (!file_exists($parsepath) or !file_exists($intpath) or !file_exists($directorypath) or !file_exists($jexamxml)) {
    fwrite(STDERR, "Zle zadane cesty v parametroch!\n");
    #TODO CHECK FOR DOBRY ERROR CODE KED CHYBA PARSE.PHP ALEBO INTERPRET.PY
    exit(11);
}

$generate = findFilesSrc($directorypath, $recursive);

function getPaths()
{
    global $directorypath;
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorypath));

    $files = array();

    foreach ($rii as $file) {

        if ($file->isDir()){
            continue;
        }

        $files[] = $file->getPathname();

    }

    return $files;
}

function findFilesSrc($directorypath, $recursive)
{
    global $html;
    $srcarray = array();
    if ($recursive) {
        $it = new RecursiveDirectoryIterator($directorypath);
        foreach (new RecursiveIteratorIterator($it) as $file) {
            if (is_dir($file)) {
                $files1 = scandir($file);
                $firsttime = true;
                foreach ($files1 as $findsrc) {
                    if (is_dir($findsrc)){
                        if ($findsrc == 'atest') {
                            if(in_array("atest", $files1)){
                                echo "TRUE";
                            }
                        }
                    }
                    if (preg_match('/^.+\.src$/', $findsrc, $matchsrc)) {
                        if ($firsttime){
                            echo "<b>TESTED FOLDER: $directorypath </b>";
                            $firsttime = false;
                        }
                        if (in_array($findsrc, $srcarray)){
                            continue;
                        }
                        array_push($srcarray, $findsrc);
                        $nameoftest = explode(".src", $findsrc, 2)[0];
                        $rc = checkIfRcExists($nameoftest, $files1, $file, 'rc', '0');
                        $in = checkIfRcExists($nameoftest, $files1, $file, 'in', '');
                        $out = checkIfRcExists($nameoftest, $files1, $file, 'out', '');
                        $rcVal = fgets(fopen($rc, 'r'));
                        $html .= parsesOnly($rcVal, "$file/$nameoftest");
                    }
                }
            }
        }

        return $html;
    }
    else{
        if(is_dir($directorypath)){
            $files = scandir($directorypath);
            $firsttime = true;
            foreach ($files as $findsrc) {
                if (preg_match('/^.+\.src$/', $findsrc, $matchsrc)) {
                    if ($firsttime){
                        echo "<b>TESTED FOLDER: $directorypath</b>";
                        $firsttime = false;
                    }
                    array_push($srcarray, $findsrc);
                    $nameoftest = explode(".src", $findsrc, 2)[0];
                    $rc = checkIfRcExists($nameoftest, $files, $directorypath, 'rc', '0');
                    $in = checkIfRcExists($nameoftest, $files, $directorypath, 'in', '');
                    $out = checkIfRcExists($nameoftest, $files, $directorypath, 'out', '');
                    $rcVal = fgets(fopen($rc, 'r'));
                    $html .= parsesOnly($rcVal, "$directorypath/$nameoftest");
                }
            }
        }

        return $html;
    }
}

function parsesOnly($rcVal, $nameoftest)
{
    global $parsepath;
    global $jexamxml;

    exec("php $parsepath <$nameoftest.src >./$nameoftest.tmpfileforxmlcheck", $output, $returned);
    if ($rcVal == $returned){
        if ($rcVal == 0) {
            exec("java -jar $jexamxml $nameoftest.out ./$nameoftest.tmpfileforxmlcheck diffs.xml /D jexamxml/options", $output, $returnXML);
            if ($returnXML == 0) {
                return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#00FF00\">GOT: $returned EXPECTED: $rcVal</td><td bgcolor=\"#00FF00\">JEXAMXML: SUCCESS</td></tr></tbody></table>";
            }
            else{
                #TODO AK NEBUDU XML ROVNAKE TAK ZELENA RETURN CODE CERVENA XML
            }
        }
        return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#00FF00\">GOT: $returned EXPECTED: $rcVal</td><td bgcolor=\"#00FF00\">JEXAMXML: SUCCESS</td></tr></tbody></table>";
    }

    return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#FF0000\">GOT: $returned EXPECTED: $rcVal</td><td bgcolor=\"#FF0000\">JEXAMXML: FAILED</td></tr></tbody></table>";

}

function checkIfRcExists($nameoftest, $files, $directorypath, $end, $data)
{
    $path = "$directorypath/$nameoftest.$end";
    if (!file_exists("$path")){
        file_put_contents("$path", $data);
    }


    return $path;
}

function isValidArgs()
{
    global $directorypath;
    global $intpath;
    global $parsepath;
    global $jexamxml;
    global $argc;
    global $argv;
    global $recursive, $parseonly, $intonly;

    $counters = array(0, 0, 0, 0, 0, 0, 0);

    global $parsepathbool;
    global $intpathbool;

    if ($argc == 1) {
        return;
    }
    if ($argc == 2) {
        if ($argv[1] == '--help') {
            printHelp();
        }
    }

    $first = true;
    foreach ($argv as $arg) {
        if ($first) {
            $first = false;
        } elseif ("--recursive" == $arg) {
            $counters[0] += 1;
            $recursive = true;
        } elseif ("--parse-only" == $arg) {
            $counters[1] += 1;
            $parseonly = true;
        } elseif ("--int-only" == $arg) {
            $counters[2] += 1;
            $intonly = true;
        } elseif (preg_match('/^--parse-script=\S+$/', $arg, $patternmatch)) {
            $counters[3] += 1;
            $parsepath = explode('=', $arg)[1];
            $parsepathbool = true;
        } elseif (preg_match('/^--int-script=\S+$/', $arg, $patternmatch)) {
            $counters[4] += 1;
            $intpath = explode('=', $arg)[1];
            $intpathbool = true;
        } elseif (preg_match('/^--directory=\S+$/', $arg, $patternmatch)) {
            $counters[5] += 1;
            $directorypath = explode('=', $arg)[1];
        } elseif (preg_match('/^--jexamxml=\S+$/', $arg, $patternmatch)) {
            $counters[6] += 1;
            $jexamxml = explode('=', $arg)[1];
        } else {
            #TODO ERROR CODE PRE NEZNAMY ARGUMENT
            exit(10);
        }
    }
    if (max($counters) > 1) {
        fwrite(STDERR, "Argument bol zadany viac ako 1!\n");
        #TODO ERROR CODE PRE VELA ARUGMENTOV
        exit(10);
    }

    if (($parseonly and ($intonly or $intpathbool)) or ($intonly and ($parseonly or $parsepathbool))){
        fwrite(STDERR, "--parse a --int argumenty sa nesmu kombinovat!\n");
        #TODO CHECK FOR RIGHT ERROR CODE
        exit(10);
    }

    return;
}

function printHelp()
{
    echo "PARSE.PHP [PHP 7.4]\n";
    echo "Autor: Peter Vinarcik\n";
    echo "Login: xvinar00\n\n";
    echo "O programe:\n";
    echo "Program nacita zo standardneho vstupu zdrojovy kod v IPPcode20\n";
    echo "a spusti sa lexikalna a syntakticka analyza, ktora overi spravnost kodu\n";
    echo "a vypise na standardni vystup XML reprezentaciu programu.\n\n";
    echo "Parametre:\n";
    echo "--help - Vypise tuto napovedu\n\n";
    echo "Chybove navratove kody:\n";
    echo "21 - Chybajuca hlavicka v zdrojovom kode\n";
    echo "22 - Neznamy alebo chybny operacny kod v zdrojovom kode\n";
    echo "23 - Ina lexikalna alebo syntakticka chyba v zdrojovom kode\n";
    exit(0);
}

?>

<html>
    <style>
        table.Table {
            table-layout: fixed;
            border: 1px solid #1C6EA4;
            background-color: #EEEEEE;
            width: 100%;
            text-align: left;
        }
        table.Table td, table.Table th {
            border: 1px solid #AAAAAA;
            padding: 3px 2px;
        }
        table.Table tbody td {
            font-size: 13px;
        }
        table.Table tr:nth-child(even) {
            background: #D0E4F5;
        }
        table.Table tfoot td {
            font-size: 14px;
        }
        table.Table tfoot .links {
            text-align: right;
        }
        table.Table tfoot .links a {
            display: inline-block;
            background: #1C6EA4;
            color: #FFFFFF;
            padding: 2px 8px;
            border-radius: 5px;
        }
    </style>
    <?php echo $html; ?>
</html>
