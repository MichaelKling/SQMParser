<?php
/**
 * Created by Ascendro S.R.L.
 * User: Michael
 * Date: 19.06.13
 * Time: 09:56
 */
class SQMLexer
{
    const T_SPACE = 0x01;
    const T_CLASS = 0x02;
    const T_ASSIGNMENT = 0x03;
    const T_BLOCKSTART = 0x04;
    const T_BLOCKEND = 0x05;
    const T_ARRAY = 0x06;
    const T_COMMA = 0x07;
    const T_SEMICOLON = 0x08;
    const T_IDENTIFIER = 0x09;
    const T_FLOAT = 0x0A;
    const T_INTEGER = 0x0B;
    const T_STRING = 0x0C;

    protected static $_tokens = array(
        "/^(\s+)/" => SQMLexer::T_SPACE,
        "/^([a-zA-Z_][a-zA-Z0-9_]*)/" => SQMLexer::T_IDENTIFIER,
        "/^([-+]?(([0-9]*)\.([0-9]+)))/" => SQMLexer::T_FLOAT,
        "/^([-+]?([0-9]+))/" => SQMLexer::T_INTEGER,
        '/^(".*")/' => SQMLexer::T_STRING,
    );

    public static function tokenToName($token) {
        switch ($token) {
            case SQMLexer::T_SPACE : return "T_SPACE"; break;
            case SQMLexer::T_CLASS : return "T_CLASS"; break;
            case SQMLexer::T_ASSIGNMENT : return "T_ASSIGNMENT"; break;
            case SQMLexer::T_BLOCKSTART : return "T_BLOCKEND"; break;
            case SQMLexer::T_BLOCKEND : return "T_BLOCKEND"; break;
            case SQMLexer::T_ARRAY : return "T_ARRAY"; break;
            case SQMLexer::T_COMMA : return "T_COMMA"; break;
            case SQMLexer::T_SEMICOLON : return "T_SEMICOLON"; break;
            case SQMLexer::T_IDENTIFIER : return "T_IDENTIFIER"; break;
            case SQMLexer::T_FLOAT : return "T_FLOAT"; break;
            case SQMLexer::T_INTEGER : return "T_INTEGER"; break;
            case SQMLexer::T_STRING : return "T_STRING"; break;
            default: return "UNKNOWN";
        }
    }

    public static function run($source) {
         $tokens = array();

        $number = 0;
        while (($line = fgets($source))) {
            $number++;
            $offset = 0;
            $lineLength = strlen($line);
            while($offset < $lineLength) {
                $result = SQMLexer::_match($line, $number, $offset);
                if($result === false) {
                    throw new Exception("Unable to parse line " . ($number+1) . ":$offset near \"$line\" - next key is '".$line[0]."' '".bin2hex($line[0])."'.");
                }
                if ($result['token'] != SQMLexer::T_SPACE) {
                    $tokens[] = $result;
                }
                $length = $result['chars'];
                $line = substr($line, $length);
                $offset += $length;
            }
        }

        return $tokens;
    }

    protected static function _match($string, $number) {
        //Try first fix matches:
        switch ($string[0]) {
            case "=":
                    return array(
                        'chars' => 1,
                        'token' => SQMLexer::T_ASSIGNMENT,
                        'line' => $number
                    );
                    break;
            case "{":
                    return array(
                        'chars' => 1,
                        'token' => SQMLexer::T_BLOCKSTART,
                        'line' => $number
                    );
                    break;
            case "}":
                    return array(
                        'chars' => 1,
                        'token' => SQMLexer::T_BLOCKEND,
                        'line' => $number
                    );
                    break;

            case ",":
                    return array(
                        'chars' => 1,
                        'token' => SQMLexer::T_COMMA,
                        'line' => $number
                    );
                    break;

            case ";":
                    return array(
                        'chars' => 1,
                        'token' => SQMLexer::T_SEMICOLON,
                        'line' => $number
                    );
                    break;

            case "[":
                    if ($string[1] == "]") {
                        return array(
                            'chars' => 2,
                            'token' => SQMLexer::T_ARRAY,
                            'line' => $number
                        );
                    }
                    break;
            case "c":
                if ($string[1] == "l" && $string[2] == "a" && $string[3] == "s" && $string[4] == "s") {
                    return array(
                        'chars' => 5,
                        'token' => SQMLexer::T_CLASS,
                        'line' => $number
                    );
                }
                if (preg_match("/^([a-zA-Z_][a-zA-Z0-9_]*)/S", $string, $matches)) {
                    return array(
                        'match' => $matches[1],
                        'chars' => strlen($matches[1]),
                        'token' => SQMLexer::T_IDENTIFIER,
                        'line' => $number
                    );
                }
                break;
            case "0":
            case "1":
            case "2":
            case "3":
            case "4":
            case "5":
            case "6":
            case "7":
            case "8":
            case "9":
            case ".":
            case "-":
            case "+":
                if (preg_match("/^([-+]?(([0-9]*)\.([0-9]+)))/S", $string, $matches)) {
                    return array(
                        'match' => (float)($matches[1]),
                        'chars' => strlen($matches[1]),
                        'token' => SQMLexer::T_FLOAT,
                        'line' => $number
                    );
                }
                if (preg_match("/^([-+]?([0-9]+))/S", $string, $matches)) {
                    return array(
                        'match' => (int)($matches[1]),
                        'chars' => strlen($matches[1]),
                        'token' => SQMLexer::T_INTEGER,
                        'line' => $number
                    );
                }
               break;
            case "\"":
                $string = substr($string,1);
                if ($string != "\"") {
                    $string = str_replace('""','XX',$string);
                }
                if (preg_match('/^(.*)"/S', $string, $matches)) {
                    return array(
                        'match' => $matches[1],
                        'chars' => strlen($matches[1])+2,
                        'token' => SQMLexer::T_STRING,
                        'line' => $number
                    );
                }
                break;
            default:
                if (preg_match("/^(\s+)/S", $string, $matches)) {
                    return array(
                        'chars' => strlen($matches[1]),
                        'token' => SQMLexer::T_SPACE,
                        'line' => $number
                    );
                }
                if (preg_match("/^([a-zA-Z_][a-zA-Z0-9_]*)/S", $string, $matches)) {
                    return array(
                        'match' => $matches[1],
                        'chars' => strlen($matches[1]),
                        'token' => SQMLexer::T_IDENTIFIER,
                        'line' => $number
                    );
                }
                break;
        }
        return false;
    }
}
