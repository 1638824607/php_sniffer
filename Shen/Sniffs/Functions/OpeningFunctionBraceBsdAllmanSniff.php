<?php
namespace PHP_CodeSniffer\Standards\Shen\Sniffs\Functions;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * 检查函数的左括号是否符合函数规范
 * Class OpeningFunctionBraceBsdAllmanSniff
 * @package PHP_CodeSniffer\Standards\Shen\Sniffs\Functions
 * @author shenruxiang
 * @date 2018/4/27 11:45
 */
class OpeningFunctionBraceBsdAllmanSniff implements Sniff
{
    public $checkFunctions = true;

    public $checkClosures = false;

    public function register()
    {
        return [
            T_FUNCTION,
            T_CLOSURE,
        ];

    }


    /**
     * @param File $phpcsFile
     * @param      $stackPtr
     * @author shenruxiang
     * @date 2018/4/27 11:45
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['scope_opener']) === false) {
            return;
        }

        if (($tokens[$stackPtr]['code'] === T_FUNCTION
            && (bool) $this->checkFunctions === false)
            || ($tokens[$stackPtr]['code'] === T_CLOSURE
            && (bool) $this->checkClosures === false)
        ) {
            return;
        }

        $openingBrace = $tokens[$stackPtr]['scope_opener'];
        $closeBracket = $tokens[$stackPtr]['parenthesis_closer'];
        if ($tokens[$stackPtr]['code'] === T_CLOSURE) {
            $use = $phpcsFile->findNext(T_USE, ($closeBracket + 1), $tokens[$stackPtr]['scope_opener']);
            if ($use !== false) {
                $openBracket  = $phpcsFile->findNext(T_OPEN_PARENTHESIS, ($use + 1));
                $closeBracket = $tokens[$openBracket]['parenthesis_closer'];
            }
        }

        $functionLine = $tokens[$closeBracket]['line'];
        $braceLine    = $tokens[$openingBrace]['line'];

        $lineDifference = ($braceLine - $functionLine);

        if ($lineDifference === 0) {

            $error = '函数方法 { 应该另起一行';
            $fix   = $phpcsFile->addFixableError($error, $openingBrace, '函数规范');
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                $indent = $phpcsFile->findFirstOnLine([], $openingBrace);
                if ($tokens[$indent]['code'] === T_WHITESPACE) {
                    $phpcsFile->fixer->addContentBefore($openingBrace, $tokens[$indent]['content']);
                }

                $phpcsFile->fixer->addNewlineBefore($openingBrace);
                $phpcsFile->fixer->endChangeset();
            }

            $phpcsFile->recordMetric($stackPtr, 'Function opening brace placement', 'same line');
        } else if ($lineDifference > 1) {
            $error = '函数声明与"{"存在空行,发现%s空行';
            $data  = [($lineDifference - 1)];
            $fix   = $phpcsFile->addFixableError($error, $openingBrace, '函数规范', $data);
            if ($fix === true) {
                for ($i = ($tokens[$stackPtr]['parenthesis_closer'] + 1); $i < $openingBrace; $i++) {
                    if ($tokens[$i]['line'] === $braceLine) {
                        $phpcsFile->fixer->addNewLineBefore($i);
                        break;
                    }

                    $phpcsFile->fixer->replaceToken($i, '');
                }
            }
        }

        $next = $phpcsFile->findNext(T_WHITESPACE, ($openingBrace + 1), null, true);
        if ($tokens[$next]['line'] === $tokens[$openingBrace]['line']) {
            if ($next === $tokens[$stackPtr]['scope_closer']) {
                return;
            }

            $error = '函数结构体开始语句应该另启一行,不与"{"同行';
            $fix   = $phpcsFile->addFixableError($error, $openingBrace, '函数规范');
            if ($fix === true) {
                $phpcsFile->fixer->addNewline($openingBrace);
            }
        }

        if ($lineDifference !== 1) {
            return;
        }

        $lineStart = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);

        $startColumn = $tokens[$lineStart]['column'];
        $braceIndent = $tokens[$openingBrace]['column'];

        if ($braceIndent !== $startColumn) {
            $expected = ($startColumn - 1);
            $found    = ($braceIndent - 1);

            $error = '函数{}没有正确对齐;规定%s空格,现有%s空格';
            $data  = [
                $expected,
                $found,
            ];

            $fix = $phpcsFile->addFixableError($error, $openingBrace, '函数规范', $data);
            if ($fix === true) {
                $indent = str_repeat(' ', $expected);
                if ($found === 0) {
                    $phpcsFile->fixer->addContentBefore($openingBrace, $indent);
                } else {
                    $phpcsFile->fixer->replaceToken(($openingBrace - 1), $indent);
                }
            }
        }
        $phpcsFile->recordMetric($stackPtr, 'Function opening brace placement', 'new line');

    }
}
