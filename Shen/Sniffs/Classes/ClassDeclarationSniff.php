<?php
namespace PHP_CodeSniffer\Standards\Shen\Sniffs\Classes;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * 检查类的声明是否正确
 * Class ClassDeclarationSniff
 * @package PHP_CodeSniffer\Standards\Shen\Sniffs\Classes
 * @author shenruxiang
 * @date 2018/4/24 10:34
 */
class ClassDeclarationSniff implements Sniff
{
    # 返回想要侦听的令牌数组
    public function register()
    {
        return [
            T_CLASS,      # class     类
            T_INTERFACE,  # interface 接口
            T_TRAIT,      # trait     代码复用
        ];
    }

    private $norm_name = '类规范';

    /**
     * @param File $phpcsFile
     * @param      $stackPtr
     * @author shenruxiang
     * @date 2018/4/24 10:37
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens    = $phpcsFile->getTokens();

        # 获取监听类型
        $errorData = [strtolower($tokens[$stackPtr]['content'])];

        # 获取监听类型左闭合栈位置
        $curlyBrace  = $tokens[$stackPtr]['scope_opener'];

        # 判断监听类型左闭合栈位置之前的的栈位置是否是换行
        # T_WHITESPACE 	\t 相当于tab键 \r\n 换行
        //$lastContent = $phpcsFile->findPrevious(T_WHITESPACE, ($curlyBrace - 1), $stackPtr, true);

        # 判断类缩进是否正确
        $classColumn = $tokens[$stackPtr]['column'];
        if($classColumn > 1){
            $error = $tokens[$stackPtr]['content'].'名前面有'.($classColumn-1) . '空格';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, $this->norm_name);
            if($fix === true){
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->endChangeset();
            }
        }

        # 获取类行号和左闭合行号
        $classLine   = $tokens[$stackPtr]['line'];
        $braceLine   = $tokens[$curlyBrace]['line'];
        if ($braceLine === $classLine) {
            $error = '%s 结构左闭合应该换行'.$classColumn;
            $fix   = $phpcsFile->addFixableError($error, $curlyBrace, $this->norm_name, $errorData);
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                if ($tokens[($curlyBrace - 1)]['code'] === T_WHITESPACE) {
                    $phpcsFile->fixer->replaceToken(($curlyBrace - 1), '');
                }

                $phpcsFile->fixer->addNewlineBefore($curlyBrace);
                $phpcsFile->fixer->endChangeset();
            }
        } else {
            if ($braceLine > ($classLine + 1)) {
                $error = '%s 和左闭合规定 1 回车,现 %s 回车';

                $data  = [
                    $tokens[$stackPtr]['content'],
                    ($braceLine - $classLine),
                ];

                $fix   = $phpcsFile->addFixableError($error, $curlyBrace, $this->norm_name, $data);
                if ($fix === true) {
                    $phpcsFile->fixer->beginChangeset();
                    $phpcsFile->fixer->endChangeset();
                }

                return;
            }
        }

        if ($tokens[($curlyBrace + 1)]['content'] !== $phpcsFile->eolChar) {
            $error = '%s的左闭合右边不允许有空格';
            $fix   = $phpcsFile->addFixableError($error, $curlyBrace, $this->norm_name, $errorData);
            if ($fix === true) {
                $phpcsFile->fixer->addNewline($curlyBrace);
            }
        }

        # phpcsFile->eolChar 换行 /n
        if ($tokens[($curlyBrace - 1)]['code'] === T_WHITESPACE) {
            $prevContent = $tokens[($curlyBrace - 1)]['content'];
            if ($prevContent === $phpcsFile->eolChar) {
                $spaces = 0;
            } else {
                $blankSpace = substr($prevContent, strpos($prevContent, $phpcsFile->eolChar));
                $spaces     = strlen($blankSpace);
            }

            # 找到类名栈位置之前的空格 类名要和左闭合保持对齐
            $first    = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
            $expected = ($tokens[$first]['column'] - 1);
            if ($spaces !== $expected) {
                $error = '%s名和左闭合要保持对齐,规定左闭合%s空格,现有%s空格';
                $data  = [
                    $tokens[$stackPtr]['content'],
                    $expected,
                    $spaces,
                ];

                $fix = $phpcsFile->addFixableError($error, $curlyBrace, $this->norm_name, $data);
                if ($fix === true) {
                    $indent = str_repeat(' ', $expected);
                    if ($spaces === 0) {
                        $phpcsFile->fixer->addContentBefore($curlyBrace, $indent);
                    } else {
                        $phpcsFile->fixer->replaceToken(($curlyBrace - 1), $indent);
                    }
                }
            }
        }
    }
}
