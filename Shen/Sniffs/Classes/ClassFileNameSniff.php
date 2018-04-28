<?php
namespace PHP_CodeSniffer\Standards\Shen\Sniffs\Classes;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * 检测文件名和文件中包含的类的名称是否匹配
 * Class ClassFileNameSniff
 * @package PHP_CodeSniffer\Standards\Shen\Sniffs\Classes
 * @author shenruxiang
 * @date 2018/4/26 15:38
 */
class ClassFileNameSniff implements Sniff
{
    public function register()
    {
        return [
            T_CLASS,
            T_INTERFACE,
        ];

    }

    /**
     * @param File $phpcsFile
     * @param      $stackPtr
     * @author shenruxiang
     * @date 2018/4/26 15:38
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $fullPath = basename($phpcsFile->getFilename());
        $fileName = substr($fullPath, 0, strrpos($fullPath, '.'));
        if ($fileName === '') {
            return;
        }

        $tokens  = $phpcsFile->getTokens();
        $decName = $phpcsFile->findNext(T_STRING, $stackPtr);

        if ($tokens[$decName]['content'] !== $fileName) {
            $error = '%s 和 当前文件名不符; 规定 "%s %s"';
            $data  = [
                ucfirst($tokens[$stackPtr]['content']),
                $tokens[$stackPtr]['content'],
                $fileName,
            ];
            $phpcsFile->addError($error, $stackPtr, '类规范', $data);
        }

    }
}
