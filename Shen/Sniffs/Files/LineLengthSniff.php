<?php
namespace PHP_CodeSniffer\Standards\Shen\Sniffs\Files;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * 检查文件中所有行的长度
 * Class LineLengthSniff
 * @package PHP_CodeSniffer\Standards\Shen\Sniffs\Files
 * @author shenruxiang
 * @date 2018/4/26 16:19
 */
class LineLengthSniff implements Sniff
{

    # 每行代码限制的宽度
    public $lineLimit = 120;

    # 规范竖线宽度 120
    public $absoluteLineLimit = 120;

    # 是否忽略注释行
    public $ignoreComments = false;

    public function register()
    {
        return [T_OPEN_TAG];

    }


    /**
     * @param File $phpcsFile
     * @param      $stackPtr
     * @return mixed
     * @author shenruxiang
     * @date 2018/4/26 16:23
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = 1; $i < $phpcsFile->numTokens; $i++) {
            if ($tokens[$i]['column'] === 1) {

                # 当栈的列处于第一行进行行长度判断
                $this->checkLineLength($phpcsFile, $tokens, $i);
            }
        }

        $this->checkLineLength($phpcsFile, $tokens, $i);

        return ($phpcsFile->numTokens + 1);

    }//end process()


    /**
     * 检测行长度方法
     * @param $phpcsFile
     * @param $tokens
     * @param $stackPtr
     * @author shenruxiang
     * @date 2018/4/26 16:23
     */
    protected function checkLineLength($phpcsFile, $tokens, $stackPtr)
    {
        # 回到前一个栈位置
        $stackPtr--;

        if ($tokens[$stackPtr]['column'] === 1
            && $tokens[$stackPtr]['length'] === 0
        ) {
            # 空行返回不检测
            return;
        }


        if ($tokens[$stackPtr]['column'] !== 1
            && $tokens[$stackPtr]['content'] === $phpcsFile->eolChar
        ) {
            $stackPtr--;
        }

        if (isset(Tokens::$phpcsCommentTokens[$tokens[$stackPtr]['code']]) === true) {
            $prevNonWhiteSpace = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
            if ($tokens[$stackPtr]['line'] !== $tokens[$prevNonWhiteSpace]['line']) {
                return;
            }
            unset($prevNonWhiteSpace);
        }

        $lineLength = ($tokens[$stackPtr]['column'] + $tokens[$stackPtr]['length'] - 1);
//        // Record metrics for common line length groupings.
//        if ($lineLength <= 80) {
//            $phpcsFile->recordMetric($stackPtr, 'Line length', '80 or less');
//        } else if ($lineLength <= 120) {
//            $phpcsFile->recordMetric($stackPtr, 'Line length', '81-120');
//        } else if ($lineLength <= 150) {
//            $phpcsFile->recordMetric($stackPtr, 'Line length', '121-150');
//        } else {
//            $phpcsFile->recordMetric($stackPtr, 'Line length', '151 or more');
//        }
        if ($tokens[$stackPtr]['code'] === T_COMMENT
            || $tokens[$stackPtr]['code'] === T_DOC_COMMENT_STRING
        ) {
            # 忽略注释
            if ($this->ignoreComments === true) {
                return;
            }

            if ($lineLength > $this->lineLimit) {
                $oldLength = strlen($tokens[$stackPtr]['content']);
                $newLength = strlen(ltrim($tokens[$stackPtr]['content'], "/#\t "));
                $indent    = (($tokens[$stackPtr]['column'] - 1) + ($oldLength - $newLength));

                $nonBreakingLength = $tokens[$stackPtr]['length'];

                $space = strrpos($tokens[$stackPtr]['content'], ' ');
                if ($space !== false) {
                    $nonBreakingLength -= ($space + 1);
                }

                if (($nonBreakingLength + $indent) > $this->lineLimit) {
                    return;
                }
            }
        }

        if ($this->absoluteLineLimit > 0
            && $lineLength > $this->absoluteLineLimit
        ) {
            $data = [
                $this->absoluteLineLimit,
                $lineLength,
            ];

            $error = '每行限制%s字符,现有%s字符';
            $phpcsFile->addError($error, $stackPtr, '行规范', $data);
        } else if ($lineLength > $this->lineLimit) {
            # 限制的宽度和规范线的宽度一致，故不执行该流程
            $data = [
                $this->lineLimit,
                $lineLength,
            ];

            $warning = 'Line exceeds %s characters; contains %s characters';
            $phpcsFile->addWarning($warning, $stackPtr, 'TooLong', $data);
        }

    }//end checkLineLength()


}//end class
