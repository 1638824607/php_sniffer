<?php
namespace PHP_CodeSniffer\Standards\Shen\Sniffs\Arrays;

use PHP_CodeSniffer\Sniffs\AbstractArraySniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * 一维或多维数组=>符号对齐
 * Class ArrayAlignSniff
 * @package PHP_CodeSniffer\Standards\Shen\Sniffs\Arrays
 * @author shenruxiang
 * @date 2018/4/25 16:07
 */
class ArrayAlignSniff extends AbstractArraySniff
{
    private $norm_name = '数组规范';

    public function processMultiLineArray($phpcsFile, $stackPtr, $arrayStart, $arrayEnd, $indices)
    {
        $tokens = $phpcsFile->getTokens();

        $doubleColumn = '';

        $arrayIndexLength = array();
        # 检测数组键最长的key column
        foreach($indices as $index){
            # 判断是否所在key 代表存在=>符号
            if (isset($index['index_start']) === true) {
                $arrayIndexLength[] = $tokens[$index['index_start'] + 1]['column'];
            }
        }

        # 确定数组=>的列数
        if($arrayIndexLength){
            $doubleColumn = max($arrayIndexLength) + 1;
        }

        # 检测数组中=>是否存在空格不合法
        foreach($indices as $index){
            # 判断是否所在key 代表存在=>符号
            if (isset($index['index_start']) === true) {
                $indexStart = $index['index_start'];
                $doubleArrow = $phpcsFile->findNext(T_DOUBLE_ARROW, $indexStart, $arrayEnd);

                if($doubleColumn !== $tokens[$doubleArrow]['column']){
                    $error = '数组键与"=>"规定间隔一个空格,行号:'.$tokens[$doubleArrow]['line'];
                    $fix = $phpcsFile->addFixableError($error, $doubleArrow, $this->norm_name);
                }

            }
        }
    }

    public function processSingleLineArray($phpcsFile, $stackPtr, $arrayStart, $arrayEnd, $indices)
    {

    }






//    public function process(File $phpcsFile, $stackPtr)
//    {
//        $tokens = $phpcsFile->getTokens();
//
//        # 栈数组开始位置
//        $arrayStart = $tokens[$stackPtr]['parenthesis_opener'];
//        # token实际所在行
//        $lineStart  = $tokens[$arrayStart]['line'];
//
//        # 栈数组结束位置
//        $arrayEnd   = $tokens[$stackPtr]['parenthesis_closer'];
//        # token实际所在行
//        $lineEnd  = $tokens[$arrayEnd]['line'];
//
//        # 判断是否为多行数组
//        if($tokens[$arrayEnd]['line'] > $tokens[$arrayStart]['line'])
//        {
//            # 解析array =>符号开始的第一个
//            $doubleArrow = $forDoubleArrow = $phpcsFile->findNext(T_DOUBLE_ARROW, $arrayStart, $arrayEnd);
//            # 初始化=>column位置
//            $doubleColumn = $tokens[$doubleArrow]['column'];
//
//            for($line = $lineStart + 1; $line < $lineEnd; ++$line)
//            {
//                $forDoubleArrow = $phpcsFile->findNext(T_DOUBLE_ARROW, $forDoubleArrow + 1, $arrayEnd);
//                $forDoubleColumn = $tokens[$forDoubleArrow]['column'];
//                if($forDoubleArrow){
//                    if($forDoubleColumn != $doubleColumn){
//                        $error = '数组键值没对齐' . $tokens[$forDoubleArrow]['line'] . '行';
//
//                        $fix = $phpcsFile->addFixableError($error, $forDoubleArrow, 'SpaceAfterKeyword',[],5);
//
//                        if ($fix === true) {
//                            $phpcsFile->fixer->beginChangeset();
//                            $phpcsFile->fixer->endChangeset();
//                        }
//                    }
//                }
//
//            }
//
//        }
//
//    }
}