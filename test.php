<?php
#$jexamxml = '/pub/courses/ipp/jexamxml/jexamxml.jar';
#$jexamxmloptions = '/pub/courses/ipp/jexamxml/options';
$jexamxml = './jexamxml/jexamxml.jar';
$jexamxmloptions = './jexamxml/options';
$parsepath = './parse.php';
$intpath = './interpret.py';
$directorypath = './';
$recursive = false;
$parseonly = false;
$intonly = false;
$parsepathbool = false;
$intpathbool = false;

$tested = 0;
$passed = 0;
$failed = 0;

$html = '';

isValidArgs();

if (!file_exists($parsepath) or !file_exists($intpath) or !file_exists($directorypath) or !file_exists($jexamxml)) {
    fwrite(STDERR, "Zle zadane cesty v parametroch!\n");
    #TODO CHECK FOR DOBRY ERROR CODE KED CHYBA PARSE.PHP ALEBO INTERPRET.PY
    exit(11);
}

$generate = findFilesSrc($directorypath, $recursive);

function getPaths($directorypath)
{
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorypath));

    $files = array();

    foreach ($rii as $file) {

        if ($file->isDir()){
            continue;
        }

        array_push($files, $file->getPathname());
    }

    return $files;
}

function findFilesSrc($directorypath, $recursive)
{
    global $html;
    global $parseonly;
    global $intonly;

    $srcarray = array();

    if ($recursive) {
        $filefnc = getPaths($directorypath);
        foreach ($filefnc as $file) {
            if (preg_match('/.*\.src$/', $file, $matchsrc)) {
                $findsrc = substr(strrchr($file, "/"), 1);
                if (in_array($file, $srcarray)) {
                    continue;
                }
                $filepath = substr($file, 0, strrpos( $file, '/') );
                array_push($srcarray, $findsrc);
                $nameoftest = explode(".src", $findsrc, 2)[0];
                $rc = checkIfRcExists($nameoftest,  $filepath, 'rc', '0');
                $in = checkIfRcExists($nameoftest,  $filepath, 'in', '');
                $out = checkIfRcExists($nameoftest, $filepath, 'out', '');
                $rcVal = fgets(fopen($rc, 'r'));
                if ($parseonly) {
                    $html .= parsesOnly($rcVal, "$filepath/$nameoftest");
                }
                elseif ($intonly){
                    $html .= intOnly($rcVal, "$filepath/$nameoftest");
                }
                else{
                    $html .= both($rcVal, "$filepath/$nameoftest");
                }
            }
        }

        return $html;
    }
    else{
        if(is_dir($directorypath)){
            $files = scandir($directorypath);
            foreach ($files as $findsrc) {
                if (preg_match('/^.+\.src$/', $findsrc, $matchsrc)) {
                    array_push($srcarray, $findsrc);
                    $nameoftest = explode(".src", $findsrc, 2)[0];
                    $rc = checkIfRcExists($nameoftest,  $directorypath, 'rc', '0');
                    $in = checkIfRcExists($nameoftest, $directorypath, 'in', '');
                    $out = checkIfRcExists($nameoftest, $directorypath, 'out', '');
                    $rcVal = fgets(fopen($rc, 'r'));
                    if ($parseonly) {
                        $html .= parsesOnly($rcVal, "$directorypath/$nameoftest");
                    }
                    elseif ($intonly) {
                        $html .= intOnly($rcVal, "$directorypath/$nameoftest");
                    }
                    else{
                        $html .= both($rcVal, "$directorypath/$nameoftest");
                    }
                }
            }
        }

        return $html;
    }
}

function both($rcVal, $nameoftest)
{
    global $intpath;
    global $parsepath;

    global $passed, $failed, $tested;

    exec("php $parsepath <$nameoftest.src >./$nameoftest.tmpfileforxmlcheck", $output, $returnPHP);
    exec("python3 $intpath --source=./$nameoftest.tmpfileforxmlcheck --input=$nameoftest.in >./$nameoftest.tmpfileforretcheck", $output, $returnINT);
    if ($returnINT == $rcVal){
        if ($returnINT == 0){
            exec("diff -w $nameoftest.out ./$nameoftest.tmpfileforretcheck", $output, $returnDIFF);
            if ($returnDIFF == 0) {
                $tested += 1;
                $passed += 1;
                removeTmpFiles("./$nameoftest.tmpfileforxmlcheck");
                removeTmpFiles("./$nameoftest.tmpfileforretcheck");
                return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#00FF00\">GOT: $returnINT EXPECTED: $rcVal</td><td bgcolor=\"#00FF00\">DIFF: SUCCESS</td></tr></tbody></table>";
            }
            else {
                $tested += 1;
                $failed += 1;
                removeTmpFiles("./$nameoftest.tmpfileforxmlcheck");
                removeTmpFiles("./$nameoftest.tmpfileforretcheck");
                return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#00FF00\">GOT: $returnINT EXPECTED: $rcVal</td><td bgcolor=\"#FF0000\">DIFF: FAILED</td></tr></tbody></table>";
            }
        }
        $tested += 1;
        $passed += 1;
        removeTmpFiles("./$nameoftest.tmpfileforxmlcheck");
        removeTmpFiles("./$nameoftest.tmpfileforretcheck");
        return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#00FF00\">GOT: $returnINT EXPECTED: $rcVal</td><td bgcolor=\"#00FF00\">DIFF: SUCCESS</td></tr></tbody></table>";
    }
    $tested += 1;
    $failed += 1;
    removeTmpFiles("./$nameoftest.tmpfileforxmlcheck");
    removeTmpFiles("./$nameoftest.tmpfileforretcheck");
    return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#FF0000\">GOT: $returnINT EXPECTED: $rcVal</td><td bgcolor=\"#FF0000\">DIFF: FAILED</td></tr></tbody></table>";
}

function removeTmpFiles($dir)
{
    unlink($dir);
}

function intOnly($rcVal, $nameoftest)
{
    global $intpath;

    global $passed, $failed, $tested;

    exec("python3 $intpath --source=$nameoftest.src --input=$nameoftest.in >./$nameoftest.tmpfileforretcheck", $output, $returned);
    if ($returned == $rcVal) {
        if ($returned == 0) {
            exec("diff -w $nameoftest.out ./$nameoftest.tmpfileforretcheck", $output, $returnDIFF);
            if ($returnDIFF == 0) {
                $tested += 1;
                $passed += 1;
                removeTmpFiles("./$nameoftest.tmpfileforretcheck");
                return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#00FF00\">GOT: $returned EXPECTED: $rcVal</td><td bgcolor=\"#00FF00\">DIFF: SUCCESS</td></tr></tbody></table>";
            }
            else {
                $tested += 1;
                $failed += 1;
                removeTmpFiles("./$nameoftest.tmpfileforretcheck");
                return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#00FF00\">GOT: $returned EXPECTED: $rcVal</td><td bgcolor=\"#FF0000\">DIFF: FAILED</td></tr></tbody></table>";
            }
        }
        $tested += 1;
        $passed += 1;
        removeTmpFiles("./$nameoftest.tmpfileforretcheck");
        return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#00FF00\">GOT: $returned EXPECTED: $rcVal</td><td bgcolor=\"#00FF00\">DIFF: SUCCESS</td></tr></tbody></table>";
    }
    $tested += 1;
    $failed += 1;
    removeTmpFiles("./$nameoftest.tmpfileforretcheck");
    return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#FF0000\">GOT: $returned EXPECTED: $rcVal</td><td bgcolor=\"#FF0000\">DIFF: FAILED</td></tr></tbody></table>";
}

function parsesOnly($rcVal, $nameoftest)
{
    global $parsepath;
    global $jexamxml;
    global $jexamxmloptions;

    global $passed, $failed, $tested;

    exec("php $parsepath <$nameoftest.src >./$nameoftest.tmpfileforxmlcheck", $output, $returned);
    if ($rcVal == $returned){
        if ($rcVal == 0) {
            exec("java -jar $jexamxml $nameoftest.out ./$nameoftest.tmpfileforxmlcheck diffs.xml /D $jexamxmloptions", $output, $returnXML);
            if ($returnXML == 0) {
                $tested += 1;
                $passed += 1;
                removeTmpFiles("./$nameoftest.tmpfileforxmlcheck");
                return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#00FF00\">GOT: $returned EXPECTED: $rcVal</td><td bgcolor=\"#00FF00\">JEXAMXML: SUCCESS</td></tr></tbody></table>";
            }
            else{
                $tested += 1;
                $failed += 1;
                removeTmpFiles("./$nameoftest.tmpfileforxmlcheck");
                return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#00FF00\">GOT: $returned EXPECTED: $rcVal</td><td bgcolor=\"#FF0000\">JEXAMXML: FAILED</td></tr></tbody></table>";
            }
        }
        $tested += 1;
        $passed += 1;
        removeTmpFiles("./$nameoftest.tmpfileforxmlcheck");
        return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#00FF00\">GOT: $returned EXPECTED: $rcVal</td><td bgcolor=\"#00FF00\">JEXAMXML: SUCCESS</td></tr></tbody></table>";
    }
    $tested += 1;
    $failed += 1;
    removeTmpFiles("./$nameoftest.tmpfileforxmlcheck");
    return "<table class=\"Table\"><tbody><tr><td>TEST: $nameoftest.src</td><td bgcolor=\"#FF0000\">GOT: $returned EXPECTED: $rcVal</td><td bgcolor=\"#FF0000\">JEXAMXML: FAILED</td></tr></tbody></table>";
}

function checkIfRcExists($nameoftest, $directorypath, $end, $data)
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
            fwrite(STDERR, "Neznamy argument!\n");
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

<html lang="cs">
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

    <table class="finalTable">
        <thead>
        <tr>
            <th><b>TESTED:</b></th>
            <th><p style="color:#3bff19">PASSED:</p></th>
            <th><p style="color:#ff827f">FAILED:</p></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><b><?php echo $tested; ?></b></td>
            <td><b><?php echo $passed; ?></b></td>
            <td><b><?php echo $failed; ?></b></td>
        </tr>
        </tbody>
    </table>

    <style>
        table.finalTable {
            border: 1px solid #1C6EA4;
            background-color: #EEEEEE;
            width: 100%;
            text-align: center;
            border-collapse: collapse;
        }
        table.finalTable td, table.finalTable th {
            border: 1px solid #AAAAAA;
            padding: 3px 2px;
        }
        table.finalTable tbody td {
            font-size: 13px;
        }
        table.finalTable tr:nth-child(even) {
            background: #D0E4F5;
        }
        table.finalTable thead {
            background: #1C6EA4;
            background: -moz-linear-gradient(top, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
            background: -webkit-linear-gradient(top, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
            background: linear-gradient(to bottom, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
            border-bottom: 2px solid #444444;
        }
        table.finalTable thead th {
            font-size: 15px;
            font-weight: bold;
            color: #FFFFFF;
            text-align: center;
            border-left: 2px solid #D0E4F5;
        }
        table.finalTable thead th:first-child {
            border-left: none;
        }

        table.finalTable tfoot td {
            font-size: 14px;
        }
        table.finalTable tfoot .links {
            text-align: right;
        }
        table.finalTable tfoot .links a{
            display: inline-block;
            background: #1C6EA4;
            color: #FFFFFF;
            padding: 2px 8px;
            border-radius: 5px;
        }
    </style>
</html>
