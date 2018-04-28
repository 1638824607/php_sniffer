<?php
namespace PHP_CodeSniffer\Standards\Shen\Sniffs\Functions;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * 检查调用方法和函数的间隔是否正确(包括方法内参数间隔)
 * Class FunctionCallArgumentSpacingSniff
 * @package PHP_CodeSniffer\Standards\Shen\Sniffs\Functions
 * @author shenruxiang
 * @date 2018/4/27 10:12
 */
class FunctionCallArgumentSpacingSniff implements Sniff
{
    public function register()
    {
        # 监听函数所用的令牌列表
        $tokens = Tokens::$functionNameTokens;

        $tokens[] = T_VARIABLE;
        $tokens[] = T_CLOSE_CURLY_BRACKET;
        $tokens[] = T_CLOSE_PARENTHESIS;

        return $tokens;

    }

    /**
     * @param File $phpcsFile
     * @param      $stackPtr
     * @author shenruxiang
     * @date 2018/4/27 10:21
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $functionName    = $stackPtr;
        $ignoreTokens    = Tokens::$emptyTokens;
        $ignoreTokens[]  = T_BITWISE_AND;
        $functionKeyword = $phpcsFile->findPrevious($ignoreTokens, ($stackPtr - 1), null, true);

        # 如果当前栈位置是function关键字或者class关键字返回
        if ($tokens[$functionKeyword]['code'] === T_FUNCTION || $tokens[$functionKeyword]['code'] === T_CLASS) {
            return;
        }

        # 带{}说明是声明函数 不是调用 不做处理
        if ($tokens[$stackPtr]['code'] === T_CLOSE_CURLY_BRACKET
            && isset($tokens[$stackPtr]['scope_condition']) === true
        ) {
            # 监听到}符号结构体返回不做处理
            return;
        }

        $openBracket = $phpcsFile->findNext(Tokens::$emptyTokens, ($functionName + 1), null, true);

        if ($tokens[$openBracket]['code'] !== T_OPEN_PARENTHESIS) {
            # 监听到{符号返回不做处理
            return;
        }

        # 监听到(继续处理
        if (isset($tokens[$openBracket]['parenthesis_closer']) === false) {
            return;
        }

        $closeBracket  = $tokens[$openBracket]['parenthesis_closer'];

        if ($tokens[($openBracket + 1)]['code'] === T_WHITESPACE) {
            $error = '调用函数参数与"("有空格';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, '函数参数规范');
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                for ($i = ($stackPtr + 1); $i < $openBracket; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
                $phpcsFile->fixer->replaceToken($openBracket, '(');
                $phpcsFile->fixer->endChangeset();
            }
        }

        if ($tokens[($openBracket - 1)]['code'] === T_WHITESPACE) {
            $error = '调用函数方法名"("有空格';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, '函数参数规范');
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                for ($i = ($stackPtr + 1); $i < $openBracket; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
                $phpcsFile->fixer->replaceToken($openBracket, '(');
                $phpcsFile->fixer->endChangeset();
            }
        }

        if ($tokens[($closeBracket - 1)]['code'] === T_WHITESPACE) {
            $error = '调用函数参数")"有空格';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, '函数参数规范');
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                for ($i = ($stackPtr + 1); $i < $openBracket; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
                $phpcsFile->fixer->replaceToken($openBracket, '(');
                $phpcsFile->fixer->endChangeset();
            }
        }

        $nextSeparator = $openBracket;

        $find = [
            T_COMMA,
            T_VARIABLE,
            T_CLOSURE,
            T_OPEN_SHORT_ARRAY,
        ];

        while (($nextSeparator = $phpcsFile->findNext($find, ($nextSeparator + 1), $closeBracket)) !== false) {
            if ($tokens[$nextSeparator]['code'] === T_CLOSURE) {
                $nextSeparator = $tokens[$nextSeparator]['scope_closer'];
                continue;
            } else if ($tokens[$nextSeparator]['code'] === T_OPEN_SHORT_ARRAY) {
                $nextSeparator = $tokens[$nextSeparator]['bracket_closer'];
                continue;
            }

            # 确保逗号或变量直接属于此函数调用，并且不在嵌套函数调用或数组内
            $brackets    = $tokens[$nextSeparator]['nested_parenthesis'];
            $lastBracket = array_pop($brackets);
            if ($lastBracket !== $closeBracket) {
                continue;
            }

            if ($tokens[$nextSeparator]['code'] === T_COMMA) {
                if ($tokens[($nextSeparator - 1)]['code'] === T_WHITESPACE) {
                    $prev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($nextSeparator - 2), null, true);
                    if (isset(Tokens::$heredocTokens[$tokens[$prev]['code']]) === false) {
                        $error = '函数参数","前存在空号';
                        $fix   = $phpcsFile->addFixableError($error, $nextSeparator, '函数参数规范');
                        if ($fix === true) {
                            $phpcsFile->fixer->replaceToken(($nextSeparator - 1), '');
                        }
                    }
                }

                if ($tokens[($nextSeparator + 1)]['code'] !== T_WHITESPACE) {
                    $error = '在函数逗号后面没有加空格';
                    $fix   = $phpcsFile->addFixableError($error, $nextSeparator, '函数参数规范');
                    if ($fix === true) {
                        $phpcsFile->fixer->addContent($nextSeparator, ' ');
                    }
                } else {
                    $next = $phpcsFile->findNext(Tokens::$emptyTokens, ($nextSeparator + 1), null, true);
                    if ($tokens[$next]['line'] === $tokens[$nextSeparator]['line']) {
                        $space = strlen($tokens[($nextSeparator + 1)]['content']);
                        if ($space > 1) {
                            $error = '函数参数","后规定一个空格,现有%s空格';
                            $data  = [$space];
                            $fix   = $phpcsFile->addFixableError($error, $nextSeparator, '函数参数规范', $data);
                            if ($fix === true) {
                                $phpcsFile->fixer->replaceToken(($nextSeparator + 1), ' ');
                            }
                        }
                    }
                }
            } else {
                $nextToken = $phpcsFile->findNext(Tokens::$emptyTokens, ($nextSeparator + 1), $closeBracket, true);
                if ($nextToken !== false) {
                    if ($tokens[$nextToken]['code'] === T_EQUAL) {
                        if (($tokens[($nextToken - 1)]['code']) !== T_WHITESPACE) {
                            $error = '参数默认值在=号前面规定一个空格';
                            $fix   = $phpcsFile->addFixableError($error, $nextToken, '函数参数规范');
                            if ($fix === true) {
                                $phpcsFile->fixer->addContentBefore($nextToken, ' ');
                            }
                        }

                        if ($tokens[($nextToken + 1)]['code'] !== T_WHITESPACE) {
                            $error = '参数默认值在=号后面规定一个空格';
                            $fix   = $phpcsFile->addFixableError($error, $nextToken, 'NoSpaceAfterEquals');
                            if ($fix === true) {
                                $phpcsFile->fixer->addContent($nextToken, ' ');
                            }
                        }
                    }
                }
            }
        }

    }
}
