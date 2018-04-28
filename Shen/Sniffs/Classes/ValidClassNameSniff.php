<?php
namespace PHP_CodeSniffer\Standards\Shen\Sniffs\Classes;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Common;

/**
 * 检测类名首字母是否大写
 * Class ValidClassNameSniff
 * @package PHP_CodeSniffer\Standards\Shen\Sniffs\Classes
 * @author shenruxiang
 * @date 2018/4/26 15:51
 */
class ValidClassNameSniff implements Sniff
{
    public function register()
    {
        return [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT,
        ];
    }

    /**
     * @param File $phpcsFile
     * @param      $stackPtr
     * @author shenruxiang
     * @date 2018/4/26 15:52
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $opener    = $tokens[$stackPtr]['scope_opener'];
        $nameStart = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), $opener, true);
        $nameEnd   = $phpcsFile->findNext(T_WHITESPACE, $nameStart, $opener);
        if ($nameEnd === false) {
            $name = $tokens[$nameStart]['content'];
        } else {
            $name = trim($phpcsFile->getTokensAsString($nameStart, ($nameEnd - $nameStart)));
        }

        # 检测类名首字母是否大写
        $valid = Common::isCamelCaps($name, true, true, false);
        if ($valid === false) {
            $type  = ucfirst($tokens[$stackPtr]['content']);
            $error = '%s name "%s" 不符合类首字母大写规范';
            $data  = [
                $type,
                $name,
            ];
            $phpcsFile->addError($error, $stackPtr, '类规范', $data);
        } else {
        }
    }
}
