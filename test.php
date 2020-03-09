<?php

$jexamxml = '/pub/courses/ipp/jexamxml/jexamxml.jar';
$parsepath = './parse.php';
$intpath = './interpret.py';
$directorypath = './';
$recursive = false;
$parseonly = false;
$intonly = false;



function isValidArgs()
{
    global $directorypath;
    global $intpath;
    global $parsepath;
    global $jexamxml;
    global $argc;
    global $argv;
    global $recursive, $parseonly, $intonly;

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

    foreach($argv as $arg){
        if ("--recursive" == $arg){
            $recursive = true;
        }
        elseif ("--parse-only" == $arg){
            $parseonly = true;
        }
        elseif ("--int-only" == $arg){
            $intonly = true;
        }
        elseif (preg_match('/^--parse-script=\S+$/', $arg, $patternmatch)){
            $parsepath = explode('=', $argv[1])[1];
            $parsepathbool = true;
        }
        elseif (preg_match('/^--int-script=\S+$/', $arg, $patternmatch)){
            $intpath = explode('=', $argv[1])[1];
            $intpathbool = true;
        }
        elseif (preg_match('/^--directory=\S+$/', $arg, $patternmatch)){
            $directorypath = explode('=', $argv[1])[1];
        }
        elseif (preg_match('/^--jexamxml=\S+$/', $arg, $patternmatch)){
            $jexamxml = explode('=', $argv[1])[1];
        }
        else{
            #TODO ERROR CODE PRE NEZNAMY ARGUMENT
            exit(10);
        }
    }

    if (($parseonly and ($intonly or $intpathbool)) or ($intonly and ($parseonly or $parsepathbool))){
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


