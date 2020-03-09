<?php

#$jexamxml = '/pub/courses/ipp/jexamxml/jexamxml.jar';
$jexamxml = './jexamxml/jexamxml.jar';
$parsepath = './parse.php';
$intpath = './interpret.py';
$directorypath = './';
$recursive = false;
$parseonly = false;
$intonly = false;

isValidArgs();
if (!file_exists($parsepath) or !file_exists($intpath) or !file_exists($directorypath) or !file_exists($jexamxml)){
    fwrite(STDERR, "Zle zadane cesty v parametroch!\n");
    #TODO CHECK FOR DOBRY ERROR CODE KED CHYBA PARSE.PHP ALEBO INTERPRET.PY
    exit(11);
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

    $counters = array(0,0,0,0,0,0,0);

    $parsepathbool = false;
    $intpathbool = false;

    if ($argc == 1){
        return;
    }

    if ($argc == 2){
        if ($argv[1] == '--help'){
            printHelp();
        }
    }

    $first = true;
    foreach($argv as $arg){
        if ($first){
            $first = false;
        }
        elseif ("--recursive" == $arg){
            $counters[0] += 1;
            $recursive = true;
        }
        elseif ("--parse-only" == $arg){
            $counters[1] += 1;
            $parseonly = true;
        }
        elseif ("--int-only" == $arg){
            $counters[2] += 1;
            $intonly = true;
        }
        elseif (preg_match('/^--parse-script=\S+$/', $arg, $patternmatch)){
            $counters[3] += 1;
            $parsepath = explode('=', $arg)[1];
            $parsepathbool = true;
        }
        elseif (preg_match('/^--int-script=\S+$/', $arg, $patternmatch)){
            $counters[4] += 1;
            $intpath = explode('=', $arg)[1];
            $intpathbool = true;
        }
        elseif (preg_match('/^--directory=\S+$/', $arg, $patternmatch)){
            $counters[5] += 1;
            $directorypath = explode('=', $arg)[1];
        }
        elseif (preg_match('/^--jexamxml=\S+$/', $arg, $patternmatch)){
            $counters[6] += 1;
            $jexamxml = explode('=', $arg)[1];
        }
        else{
            #TODO ERROR CODE PRE NEZNAMY ARGUMENT
            exit(10);
        }
    }

    if(max($counters) > 1) {
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


