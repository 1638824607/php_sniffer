<?php
namespace PHP_CodeSniffer\Standards\Shen\Sniffs\ControlStructures;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * 验证控制语句是否符合其编码标准
 * Class ControlSignatureSniff
 * @package PHP_CodeSniffer\Standards\Shen\Sniffs\ControlStructures
 * @author shenruxiang
 * @date 2018/4/27 14:44
 */
class ControlSignatureSniff implements Sniff
{

    # 如果使用替代语法，应在冒号前面留出多少空格
    public $requiredSpacesBeforeColon = 1;

    # 监听格式
    public $supportedTokenizers = [
        'PHP',
        'JS',
    ];

    public function register()
    {
        return [
            T_TRY,
            T_CATCH,
            T_FINALLY,
            T_DO,
            T_WHILE,
            T_FOR,
            T_IF,
            T_FOREACH,
            T_ELSE,
            T_ELSEIF,
            T_SWITCH,
        ];

    }

    /**
     * @param File $phpcsFile
     * @param      $stackPtr
     * @author shenruxiang
     * @date 2018/4/27 14:45
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[($stackPtr + 1)]) === false) {
            return;
        }

        $isAlternative = false;
        if (isset($tokens[$stackPtr]['scope_opener']) === true
            && $tokens[$tokens[$stackPtr]['scope_opener']]['code'] === T_COLON
        ) {
            $isAlternative = true;
        }

        # 在if else foreach等关键字后面规定0空格
        $expected = 0; //规定0
        if (isset($tokens[$stackPtr]['parenthesis_closer']) === false && $isAlternative === true) {
            $expected = (int) $this->requiredSpacesBeforeColon;
        }

        $found = 1;
        if ($tokens[($stackPtr + 1)]['code'] !== T_WHITESPACE) {
            $found = 0;
        } else if ($tokens[($stackPtr + 1)]['content'] !== ' ') {
            if (strpos($tokens[($stackPtr + 1)]['content'], $phpcsFile->eolChar) !== false) {
                $found = 'newline';
            } else {
                $found = strlen($tokens[($stackPtr + 1)]['content']);
            }
        }
        # 判断关键字后面不需要空格的
        $check_arr = ['ELSE'];
        if(in_array(strtoupper($tokens[$stackPtr]['content']),$check_arr)){
            $expected = 1;
        }
        if ($found !== $expected ) {
            $arrayStart = $tokens[$stackPtr]['scope_opener'];
            $lineStart  = $tokens[$arrayStart]['line'];
            $arrayEnd   = $tokens[$stackPtr]['scope_closer'];
            $lineEnd  = $tokens[$arrayEnd]['line'];

            if(($lineEnd-$lineStart -1) > 3){
                if($found !== 'newline'){
//                    $error = $tokens[$stackPtr]['content'] . '语句主体超过三行换行';
                    $error = $tokens[$stackPtr]['content'] . '语句后面没有空格';
                }
            }else{
                if($found === 'newline'){
                    $error = '%s 语句主体不超过三行规定不能换行';
                }else{
                    $error = '在 %s 关键字后面规定 %s 空格; 只有 %s 空格';
                }
            }

            $data  = [
                strtoupper($tokens[$stackPtr]['content']),
                $expected,
                $found,
            ];

            $fix = $phpcsFile->addFixableError($error, $stackPtr, '控制语句规范', $data);
            if ($fix === true) {
                if ($found === 0) {
                    $phpcsFile->fixer->addContent($stackPtr, str_repeat(' ', $expected));
                } else {
                    $phpcsFile->fixer->replaceToken(($stackPtr + 1), str_repeat(' ', $expected));
                }
            }
        }

        // Single space after closing parenthesis.
        if (isset($tokens[$stackPtr]['parenthesis_closer']) === true
            && isset($tokens[$stackPtr]['scope_opener']) === true
        ) {
            $expected = 1;
            if ($isAlternative === true) {
                $expected = (int) $this->requiredSpacesBeforeColon;
            }

            $closer  = $tokens[$stackPtr]['parenthesis_closer'];
            $opener  = $tokens[$stackPtr]['scope_opener'];
            $content = $phpcsFile->getTokensAsString(($closer + 1), ($opener - $closer - 1));

            if (trim($content) === '') {
                if (strpos($content, $phpcsFile->eolChar) !== false) {
                    $found = 'newline';
                } else {
                    $found = strlen($content);
                }
            } else {
                $found = '"'.str_replace($phpcsFile->eolChar, '\n', $content).'"';
            }

            $arrayStart = $tokens[$stackPtr]['scope_opener'];   //shenruxiang
            $lineStart  = $tokens[$arrayStart]['line'];
            $arrayEnd   = $tokens[$stackPtr]['scope_closer'];
            $lineEnd  = $tokens[$arrayEnd]['line'];

            $error ='';
            if(($lineEnd-$lineStart -1) > 3){
                //$error = '在右括号后面规定 %s 空格;只有 %s 空格1'.$found;
                if($found !== 'newline'){
                    $error = $tokens[$stackPtr]['content'] . '语句主体超过三行换行';
                }
            }else{
                if($found !== $expected){
                    if($found === 'newline'){
                        $error = $tokens[$stackPtr]['content'] . '语句主体不超过三行不需要换行';
                    }else{
                        $error = '在右括号后面规定 %s 空格;只有 %s 空格';
                    }
                }
            }
            if($error){
                $data  = [
                    $expected,
                    $found,
                ];

                $fix = $phpcsFile->addFixableError($error, $closer, '控制语句规范', $data);
                if ($fix === true) {
                    $padding = str_repeat(' ', $expected);
                    if ($closer === ($opener - 1)) {
                        $phpcsFile->fixer->addContent($closer, $padding);
                    } else {
                        $phpcsFile->fixer->beginChangeset();
                        if (trim($content) === '') {
                            $phpcsFile->fixer->addContent($closer, $padding);
                            if ($found !== 0) {
                                for ($i = ($closer + 1); $i < $opener; $i++) {
                                    $phpcsFile->fixer->replaceToken($i, '');
                                }
                            }
                        } else {
                            $phpcsFile->fixer->addContent($closer, $padding.$tokens[$opener]['content']);
                            $phpcsFile->fixer->replaceToken($opener, '');

                            if ($tokens[$opener]['line'] !== $tokens[$closer]['line']) {
                                $next = $phpcsFile->findNext(T_WHITESPACE, ($opener + 1), null, true);
                                if ($tokens[$next]['line'] !== $tokens[$opener]['line']) {
                                    for ($i = ($opener + 1); $i < $next; $i++) {
                                        $phpcsFile->fixer->replaceToken($i, '');
                                    }
                                }
                            }
                        }

                        $phpcsFile->fixer->endChangeset();
                    }//end if
                }//end if
            }

//            if ($found !== $expected) {
//
//
////                strtoupper($tokens[$stackPtr]['content']);
//                $error = '';
//                if(($lineEnd - $lineStart - 1) > 3){
//                    if($found != 'newline'){
////                        $error = $tokens[$stackPtr]['content'] . '语句主体超过三行换行';
//                        $error = '在右括号后面规定 %s 空1格;只有 %s 空格1';
//                    }
//                }else{
////                    //                $error = 'Expected %s space(s) after closing parenthesis; found %s';
//                    $error = '在右括号后面规定 %s 空1格;只有 %s 空格'.($lineEnd - $lineStart - 1);
//                }
//                $data  = [
//                    $expected,
//                    $found,
//                ];
//
//                $fix = $phpcsFile->addFixableError($error, $closer, 'SpaceAfterCloseParenthesis', $data);
//                if ($fix === true) {
//                    $padding = str_repeat(' ', $expected);
//                    if ($closer === ($opener - 1)) {
//                        $phpcsFile->fixer->addContent($closer, $padding);
//                    } else {
//                        $phpcsFile->fixer->beginChangeset();
//                        if (trim($content) === '') {
//                            $phpcsFile->fixer->addContent($closer, $padding);
//                            if ($found !== 0) {
//                                for ($i = ($closer + 1); $i < $opener; $i++) {
//                                    $phpcsFile->fixer->replaceToken($i, '');
//                                }
//                            }
//                        } else {
//                            $phpcsFile->fixer->addContent($closer, $padding.$tokens[$opener]['content']);
//                            $phpcsFile->fixer->replaceToken($opener, '');
//
//                            if ($tokens[$opener]['line'] !== $tokens[$closer]['line']) {
//                                $next = $phpcsFile->findNext(T_WHITESPACE, ($opener + 1), null, true);
//                                if ($tokens[$next]['line'] !== $tokens[$opener]['line']) {
//                                    for ($i = ($opener + 1); $i < $next; $i++) {
//                                        $phpcsFile->fixer->replaceToken($i, '');
//                                    }
//                                }
//                            }
//                        }
//
//                        $phpcsFile->fixer->endChangeset();
//                    }//end if
//                }//end if
//            }//end if
        }//end if

        // Single newline after opening brace.
        if (isset($tokens[$stackPtr]['scope_opener']) === true) {
            $opener = $tokens[$stackPtr]['scope_opener'];
            for ($next = ($opener + 1); $next < $phpcsFile->numTokens; $next++) {
                $code = $tokens[$next]['code'];

                if ($code === T_WHITESPACE
                    || ($code === T_INLINE_HTML
                    && trim($tokens[$next]['content']) === '')
                ) {
                    continue;
                }

                // Skip all empty tokens on the same line as the opener.
                if ($tokens[$next]['line'] === $tokens[$opener]['line']
                    && (isset(Tokens::$emptyTokens[$code]) === true
                    || $code === T_CLOSE_TAG)
                ) {
                    continue;
                }

                // We found the first bit of a code, or a comment on the
                // following line.
                break;
            }//end for

            if ($tokens[$next]['line'] === $tokens[$opener]['line']) {
                $error = 'Newline required after opening brace';
                $fix   = $phpcsFile->addFixableError($error, $opener, '控制语句规范');
                if ($fix === true) {
                    $phpcsFile->fixer->beginChangeset();
                    for ($i = ($opener + 1); $i < $next; $i++) {
                        if (trim($tokens[$i]['content']) !== '') {
                            break;
                        }

                        // Remove whitespace.
                        $phpcsFile->fixer->replaceToken($i, '');
                    }

                    $phpcsFile->fixer->addContent($opener, $phpcsFile->eolChar);
                    $phpcsFile->fixer->endChangeset();
                }
            }//end if
        } else if ($tokens[$stackPtr]['code'] === T_WHILE) {
            // Zero spaces after parenthesis closer.
            $closer = $tokens[$stackPtr]['parenthesis_closer'];
            $found  = 0;
            if ($tokens[($closer + 1)]['code'] === T_WHITESPACE) {
                if (strpos($tokens[($closer + 1)]['content'], $phpcsFile->eolChar) !== false) {
                    $found = 'newline';
                } else {
                    $found = strlen($tokens[($closer + 1)]['content']);
                }
            }

            if ($found !== 0) {
                $error = 'Expected 0 spaces before semicolon; %s found';
                $data  = [$found];
                $fix   = $phpcsFile->addFixableError($error, $closer, '控制语句规范', $data);
                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken(($closer + 1), '');
                }
            }
        }//end if

        // Only want to check multi-keyword structures from here on.
        if ($tokens[$stackPtr]['code'] === T_DO) {
            if (isset($tokens[$stackPtr]['scope_closer']) === false) {
                return;
            }

            $closer = $tokens[$stackPtr]['scope_closer'];
        } else if ($tokens[$stackPtr]['code'] === T_ELSE
            || $tokens[$stackPtr]['code'] === T_ELSEIF
            || $tokens[$stackPtr]['code'] === T_CATCH
        ) {
            if (isset($tokens[$stackPtr]['scope_opener']) === true
                && $tokens[$tokens[$stackPtr]['scope_opener']]['code'] === T_COLON
            ) {
                // Special case for alternate syntax, where this token is actually
                // the closer for the previous block, so there is no spacing to check.
                return;
            }

            $closer = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
            if ($closer === false || $tokens[$closer]['code'] !== T_CLOSE_CURLY_BRACKET) {
                return;
            }
        } else {
            return;
        }//end if

        // Single space after closing brace.
        $found = 1;
        if ($tokens[($closer + 1)]['code'] !== T_WHITESPACE) {
            $found = 0;
        } else if ($tokens[($closer + 1)]['content'] !== ' ') {
            if (strpos($tokens[($closer + 1)]['content'], $phpcsFile->eolChar) !== false) {
                $found = 'newline';
            } else {
                $found = strlen($tokens[($closer + 1)]['content']);
            }
        }

        if($found !== 1) {
//            $error = 'Expected 1 space after closing brace; %s found';
//            $data  = [$found];
//            $fix   = $phpcsFile->addFixableError($error, $closer, 'SpaceAfterCloseBrace', $data);
//            if ($fix === true) {
//                if ($found === 0) {
//                    $phpcsFile->fixer->addContent($closer, ' ');
//                } else {
//                    $phpcsFile->fixer->replaceToken(($closer + 1), ' ');
//                }
//            }
        }

    }//end process()


}//end class
