<?php
/**
 * Check whether the function method conforms to the space specification, and the default is an empty line.
 * 检查函数方法之间是否符合空格规范（默认一空行）
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2015 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Standards\Shen\Sniffs\Methods;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class FunctionSpaceSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [
            T_FUNCTION,
            T_CLOSURE,
        ];

    }//end register()


    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in
     *                                               the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['scope_closer']) === false) {
            // Probably an interface method.
            return;
        }

        $closeBrace  = $tokens[$stackPtr]['scope_closer'];
        $nextContent = $phpcsFile->findNext(T_WHITESPACE, ($closeBrace + 1), null, true);
        $found       = ($tokens[$nextContent]['line'] - 1  - $tokens[$closeBrace]['line']);

        if($found != 1){
            $error = '函数方法之间规定一行空行;现有 %s 空行'.$found;   //shenruxiang
            $data  = [$found];
            $fix   = $phpcsFile->addFixableError($error, $closeBrace, 'FunctionSpace', $data);
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                for ($i = ($nextContent + 1); $i < $closeBrace; $i++) {
                    if ($tokens[$i]['line'] === $tokens[$nextContent]['line']) {
                        continue;
                    }

                    if ($tokens[$i]['line'] === $tokens[$closeBrace]['line']) {
                        break;
                    }

                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->endChangeset();
            }
        }


//        $error = '函数方法 } 必须在主体后面新的一行;在 } 前面有 %s 行空行'.$closeBrace.','.$tokens[$closeBrace]['line'] . ','.$tokens[$nextContent]['line']; //shenruxiang
//        $data  = [$found];
//        $fix   = $phpcsFile->addFixableError($error, $closeBrace, 'SpacingBeforeClose', $data);
//
//        if ($fix === true) {
//            $phpcsFile->fixer->beginChangeset();
//            for ($i = ($prevContent + 1); $i < $closeBrace; $i++) {
//                if ($tokens[$i]['line'] === $tokens[$prevContent]['line']) {
//                    continue;
//                }
//
//                // Don't remove any identation before the brace.
//                if ($tokens[$i]['line'] === $tokens[$closeBrace]['line']) {
//                    break;
//                }
//
//                $phpcsFile->fixer->replaceToken($i, '');
//            }
//
//            $phpcsFile->fixer->endChangeset();
//        }

    }//end process()


}//end class
