<?php
namespace PHP_CodeSniffer\Standards\Shen\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * 不得在非空行结尾处尾随空格  检查不需要的空格
 * Class SuperfluousWhitespaceSniff
 * @package PHP_CodeSniffer\Standards\Shen\Sniffs\WhiteSpace
 * @author shenruxiang
 * @date 2018/4/24 14:21
 */
class CodeWhitespaceSniff implements Sniff
{

    # 这个sniff支持的分词器列表
    public $supportedTokenizers = [
        'PHP',
        'JS',
        'CSS',
    ];

    public $ignoreBlankLines = false;

    public function register()
    {
        return [
            T_OPEN_TAG,
            T_CLOSE_TAG,
            T_WHITESPACE,
            T_COMMENT,
            T_DOC_COMMENT_WHITESPACE,
            T_CLOSURE,
        ];

    }

    private $norm_name = '结尾空格规范';

    /**
     *
     * @param File $phpcsFile
     * @param      $stackPtr
     * @author shenruxiang
     * @date 2018/4/24 14:22
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        # 检查代码开头空格
        if ($tokens[$stackPtr]['code'] === T_OPEN_TAG) {
            if ($phpcsFile->tokenizerType !== 'PHP') {
                if ($tokens[($stackPtr + 1)]['code'] !== T_WHITESPACE) {
                    return;
                }
                if ($phpcsFile->fixer->enabled === true) {

                    # 检查<?php 前面是否有空格
                    $stackPtr = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
                }
            } else {
                if ($stackPtr === 0) {
                    return;
                }

                # 内联html暂不作处理
                for ($i = ($stackPtr - 1); $i >= 0; $i--) {
                    if ($tokens[$i]['type'] !== 'T_INLINE_HTML') {
                        return;
                    }
                    $tokenContent = trim($tokens[$i]['content']);
                    if ($tokenContent !== '') {
                        return;
                    }
                }
            }

            $fix = $phpcsFile->addFixableError('代码前面找到额外的空格', $stackPtr, $this->norm_name);
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                for ($i = 0; $i < $stackPtr; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->endChangeset();
            }
            # 如果存在？> php文件结尾符号 检查代码后面空格
        } else if ($tokens[$stackPtr]['code'] === T_CLOSE_TAG) {
            if ($phpcsFile->tokenizerType === 'PHP') {
                if (isset($tokens[($stackPtr + 1)]) === false) {
                    return;
                }

                for ($i = ($stackPtr + 1); $i < $phpcsFile->numTokens; $i++) {

                    # 内联html暂不做处理
                    if ($tokens[$i]['type'] !== 'T_INLINE_HTML') {
                        return;
                    }

                    $tokenContent = trim($tokens[$i]['content']);
                    if (empty($tokenContent) === false) {
                        return;
                    }
                }
            } else {
                $stackPtr--;

                if ($tokens[$stackPtr]['code'] !== T_WHITESPACE) {
                    return;
                }

                # 在文件最后一行的末尾有换行符可以
                if ($tokens[($stackPtr - 1)]['code'] !== T_WHITESPACE
                    && $tokens[$stackPtr]['content'] === $phpcsFile->eolChar
                ) {
                    return;
                }

                if ($phpcsFile->fixer->enabled === true) {
                    $prev     = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
                    $stackPtr = ($prev + 1);
                }
            }

            $fix = $phpcsFile->addFixableError('代码后面找到额外的空格', $stackPtr, $this->norm_name);
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                for ($i = ($stackPtr + 1); $i < $phpcsFile->numTokens; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->endChangeset();
            }
        } else {
            # 检查行结束符空白
            # 忽略不在行尾的空白.
            if (isset($tokens[($stackPtr + 1)]['line']) === true
                && $tokens[($stackPtr + 1)]['line'] === $tokens[$stackPtr]['line']
            ) {
                return;
            }

            # 忽略空白行
            if ($tokens[$stackPtr]['column'] == 1
                && $tokens[($stackPtr - 1)]['line'] !== $tokens[$stackPtr]['line']
            ) {
                if($tokens[$stackPtr]['column'] == 1 && $tokens[$stackPtr -1]['column'] == 1){
                    $phpcsFile->addWarning('规定最多一行空行', $stackPtr, $this->norm_name);
                    $phpcsFile->addWarning('规定最多一行空行', $stackPtr-1, $this->norm_name);
                }
            }

            $tokenContent = rtrim($tokens[$stackPtr]['content'], $phpcsFile->eolChar);
            if (empty($tokenContent) === false) {
                if ($tokenContent !== rtrim($tokenContent)) {
                    $fix = $phpcsFile->addFixableError('代码后面规定不能有空格', $stackPtr, $this->norm_name);
                    if ($fix === true) {
                        $phpcsFile->fixer->replaceToken($stackPtr, rtrim($tokenContent).$phpcsFile->eolChar);
                    }
                }
            } else if ($tokens[($stackPtr - 1)]['content'] !== rtrim($tokens[($stackPtr - 1)]['content'])
                && $tokens[($stackPtr - 1)]['line'] === $tokens[$stackPtr]['line']
            ) {
                $fix = $phpcsFile->addFixableError('Whitespace found at end of line', ($stackPtr - 1), 'EndLine');
                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken(($stackPtr - 1), rtrim($tokens[($stackPtr - 1)]['content']));
                }
            }

            # 检查函数中的多个空行
//            if (($phpcsFile->hasCondition($stackPtr, T_FUNCTION) === true
//                || $phpcsFile->hasCondition($stackPtr, T_CLOSURE) === true)
//                && $tokens[($stackPtr - 1)]['line'] < $tokens[$stackPtr]['line']
//                && $tokens[($stackPtr - 2)]['line'] === $tokens[($stackPtr - 1)]['line']
//            ) {
//                $next  = $phpcsFile->findNext(T_WHITESPACE, $stackPtr, null, true);
//                $lines = ($tokens[$next]['line'] - $tokens[$stackPtr]['line']);
//                if ($lines > 1) {
////                    $phpcsFile->addWarning('规定最多一行空行2', $stackPtr, $this->norm_name);
//                }
//            }
        }

    }


}//end class
