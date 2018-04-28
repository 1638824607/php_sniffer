<?php
namespace PHP_CodeSniffer\Standards\Shen\Sniffs\Arrays;

use PHP_CodeSniffer\Sniffs\AbstractArraySniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * 检查数组和键值的缩进和数组键值空格规范
 * Class ArrayIndentSniff
 * @package PHP_CodeSniffer\Standards\Shen\Sniffs\Arrays
 * @author shenruxiang
 * @date 2018/4/24 17:14
 */
class ArrayIndentSniff extends AbstractArraySniff
{

    # 数组键默认缩进4个空格
    public $indent = 4;

    private $norm_name = '数组规范';

    /**
     * 处理单行数组定义
     * @param $phpcsFile
     * @param $stackPtr
     * @param $arrayStart
     * @param $arrayEnd
     * @param $indices
     * @author shenruxiang
     * @date 2018/4/24 19:20
     */
    public function processSingleLineArray($phpcsFile, $stackPtr, $arrayStart, $arrayEnd, $indices)
    {
        $tokens = $phpcsFile->getTokens();

        # 检测数组开头是否存在空格
        $start_trimmed = ltrim($tokens[$arrayStart+1]['content']);
        # 检测数组结尾是否存在空格
        $end_trimmed = ltrim($tokens[$arrayEnd-1]['content']);

        # 定义错误信息变量
        $error = '';

        if($start_trimmed === '') {
            $error = '单行数组开头存在空格';
        }

        if($end_trimmed === ''){
            $error = '单行数组结尾存在空格';
        }

        foreach($indices as $index){
            # 获取数组键或元素位置
            if (isset($index['index_start']) === true) {
                $start = $index['index_start'];
                $v_start = $index['value_start'];
            } else {
                $start = $v_start = $index['value_start'];
            }

            # 去除数组第一个元素或键空格
            if($tokens[$arrayStart + 1]['column'] == $tokens[$start]['column']){
                if(isset($index['index_start']) === true){
                    if(ltrim($tokens[$v_start + 1]['content']) === ''){
                        $error = "数组值与','符号存在空格,现有".strlen($tokens[$v_start + 1]['content']).'空格';
                        break;
                    }
                }
                if($tokens[($start + 1)]['code'] === T_WHITESPACE){
                    if(ltrim($tokens[$start + 1]['content']) === '' || ltrim($tokens[$v_start - 1]['content']) === ''){
                        $error = '数组键值与=>规定没有空格';
                        break;
                    }
                }else{
                    continue;
                }
            }

            # 判断数组 键前面是否存在空格
            if($tokens[($start - 1)]['code'] === T_WHITESPACE && strlen($tokens[$start - 1]['content']) > 1 || ltrim($tokens[$start - 1]['content']) !== ''){
                $space = ltrim($tokens[$start-1]['content']) !== '' ? 0 : strlen($tokens[$start - 1]['content']);
                $error = '数组键前面规定一个空格,现有'.$space .'空格';
                $phpcsFile->addFixableError($error, $start, $this->norm_name);
                break;
            }

            # 检测数组值后面是否存在空格
            if(isset($index['index_start']) === true){
                if(ltrim($tokens[$start + 1]['content']) === '' || ltrim($tokens[$v_start - 1]['content']) === ''){
                    $error = '数组键值与=>规定没有空格';
                    break;
                }
            }else{
                if(ltrim($tokens[$start + 1]['content']) === ''){
                    $error = '数组键值与=>规定没有空格';
                    break;
                }
            }
        }

        # 检测数组
        if($error){
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, $this->norm_name);
        }
    }

    /**
     * 处理多行数组定义
     * @param $phpcsFile
     * @param $stackPtr
     * @param $arrayStart
     * @param $arrayEnd
     * @param $indices
     * @author shenruxiang
     * @date 2018/4/24 19:20
     */
    public function processMultiLineArray($phpcsFile, $stackPtr, $arrayStart, $arrayEnd, $indices)
    {
        $tokens = $phpcsFile->getTokens();

        # $stackPtr 数组第一行初始栈位置 例如上面的array的位置就是$stackPtr的栈位置
        # 找到数组对齐的基准 例如上面的$a的位置 因为$a-1就是T_WHITESPACE的位置
        $first          = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
        # 获取数组第一个键实际列数
        $expectedIndent = ($tokens[$first]['column'] - 1 + $this->indent);

        foreach ($indices as $index) {
            # 判断是否存在键
            if (isset($index['index_start']) === true) {
                $start = $index['index_start'];
                $vstart = $index['value_start'];
            } else {
                $start = $vstart = $index['value_start'];
            }

            $prev = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($start - 1), null, true);
            if ($tokens[$prev]['line'] === $tokens[$start]['line']) {
                continue;
            }

            # 判断数组值后是否存在空格
            if($tokens[($vstart + 1)]['code'] === T_WHITESPACE && strlen($tokens[$vstart + 1]['content']) > 0
                && $tokens[$vstart + 1]['content'] !== $phpcsFile->eolChar){
                $error = '数组值后面规定没有空格,现有'.strlen($tokens[$vstart + 1]['content']).'空格';
                $phpcsFile->addFixableError($error, $vstart, $this->norm_name);
                break;
            }

            if($tokens[($vstart - 1)]['code'] === T_WHITESPACE && strlen($tokens[$vstart - 1]['content']) > 1){
                $error = '数组值前面规定一个空格,现有'.strlen($tokens[$vstart - 1]['content']).'空格';
                $phpcsFile->addFixableError($error, $vstart, $this->norm_name);
                break;
            }

            # 判断数组 键前面是否存在空格
            if($tokens[($vstart - 1)]['code'] === T_WHITESPACE && strlen($tokens[$vstart - 1]['content']) > 1 || ltrim($tokens[$vstart - 1]['content']) !== ''){
                $space = ltrim($tokens[$vstart-1]['content']) !== '' ? 0 : strlen($tokens[$vstart - 1]['content']);
                $error = '数组键前面规定一个空格,现有'.$space .'空格';
                $phpcsFile->addFixableError($error, $vstart, $this->norm_name);
                break;
            }

            # 下面两种查找键列的方法是一致的
            $first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $start, true);
            $firsts = $tokens[$start]['column'] - 1;

            $foundIndent = ($tokens[$first]['column'] - 1);
            if ($foundIndent === $expectedIndent) {

                continue;
            }

            $error = '数组key没有对齐;规定%s空格,现有%s';
            $data  = [
                $expectedIndent,
                $foundIndent,
            ];
            $fix   = $phpcsFile->addFixableError($error, $first, $this->norm_name, $data);
            if ($fix === false) {
                continue;
            }

            $padding = str_repeat(' ', $expectedIndent);
            if ($foundIndent === 0) {
                $phpcsFile->fixer->addContentBefore($first, $padding);
            } else {
                $phpcsFile->fixer->replaceToken(($first - 1), $padding);
            }
        }
        # 检查数组右括号前一个位置是否是T_WHITESPACE换行
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($arrayEnd - 1), null, true);

        if ($tokens[$prev]['line'] === $tokens[$arrayEnd]['line']) {
            $error = '数组闭合括号必须另启一行';
            $fix   = $phpcsFile->addFixableError($error, $arrayEnd, $this->norm_name);
            if ($fix === true) {
                $padding = $phpcsFile->eolChar.str_repeat(' ', $expectedIndent);
                $phpcsFile->fixer->addContentBefore($arrayEnd, $padding);
            }
            return;
        }
        $expectedIndent -= $this->indent;
        $foundIndent     = ($tokens[$arrayEnd]['column'] - 1);
        if ($foundIndent === $expectedIndent) {
            return;
        }

        $error = '数组闭合括号没有对齐,规定%s空格,现有%s空格';
        $data  = [
            $expectedIndent,
            $foundIndent,
        ];
        $fix   = $phpcsFile->addFixableError($error, $arrayEnd, $this->norm_name, $data);
        if ($fix === false) {
            return;
        }

        $padding = str_repeat(' ', $expectedIndent);
        if ($foundIndent === 0) {
            $phpcsFile->fixer->addContentBefore($arrayEnd, $padding);
        } else {
            $phpcsFile->fixer->replaceToken(($arrayEnd - 1), $padding);
        }

    }

}
