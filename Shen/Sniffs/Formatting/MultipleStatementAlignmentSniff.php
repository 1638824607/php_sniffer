<?php
namespace PHP_CodeSniffer\Standards\Shen\Sniffs\Formatting;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * 检查变量分配是否对齐
 * Class MultipleStatementAlignmentSniff
 * @package PHP_CodeSniffer\Standards\Shen\Sniffs\Formatting
 * @author shenruxiang
 * @date 2018/4/26 17:11
 */
class MultipleStatementAlignmentSniff implements Sniff
{
    public $supportedTokenizers = [
        'PHP',
        'JS',
    ];

    # 如果为false，则会抛出错误; 否则是警告 警告为波浪线
    public $error = true;

    # 当前上下文超过规定的填充数量，则将被忽略
    public $maxPadding = 1000;

    /**
     * @return mixed
     * @author shenruxiang
     * @date 2018/4/26 17:18
     */
    public function register()
    {
        # 监听的符号列表 去除数组=>符号
        $tokens = Tokens::$assignmentTokens;
        unset($tokens[T_DOUBLE_ARROW]);
        return $tokens;

    }

    /**
     * @param File $phpcsFile
     * @param      $stackPtr
     * @return int|void
     * @author shenruxiang
     * @date 2018/4/26 17:18
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        # 忽略变量在条件语句使用场景
        if (isset($tokens[$stackPtr]['nested_parenthesis']) === true) {
            foreach ($tokens[$stackPtr]['nested_parenthesis'] as $start => $end) {
                if (isset($tokens[$start]['parenthesis_owner']) === true) {
                    return;
                }
            }
        }

        $lastAssign = $this->checkAlignment($phpcsFile, $stackPtr);
        return ($lastAssign + 1);

    }

    /**
     * 处理变量对齐方法
     * @param $phpcsFile
     * @param $stackPtr
     * @return int|string
     * @author shenruxiang
     * @date user
     */
    public function checkAlignment($phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $assignments = [];
        $prevAssign  = null;
        $lastLine    = $tokens[$stackPtr]['line'];
        $maxPadding  = null;
        $stopped     = null;
        $lastCode    = $stackPtr;
        $lastSemi    = null;

        $find = Tokens::$assignmentTokens;
        unset($find[T_DOUBLE_ARROW]);

        for ($assign = $stackPtr; $assign < $phpcsFile->numTokens; $assign++) {

            # 如果当前栈位置令牌不存在于定义的解析器令牌表中
            if (isset($find[$tokens[$assign]['code']]) === false) {
                # 忽略匿名函数
                if ($tokens[$assign]['code'] === T_CLOSURE
                    || $tokens[$assign]['code'] === T_ANON_CLASS
                ) {
                    # 栈位置到匿名函数后面
                    $assign   = $tokens[$assign]['scope_closer'];
                    $lastCode = $assign;
                    continue;
                }

                # 忽略数组
                if ($tokens[$assign]['code'] === T_OPEN_SHORT_ARRAY
                    && isset($tokens[$assign]['bracket_closer']) === true
                ) {
                    # 栈位置到数组后面[]
                    $assign = $lastCode = $tokens[$assign]['bracket_closer'];
                    continue;
                }

                # 忽略数组
                if ($tokens[$assign]['code'] === T_ARRAY
                    && isset($tokens[$assign]['parenthesis_opener']) === true
                    && isset($tokens[$tokens[$assign]['parenthesis_opener']]['parenthesis_closer']) === true
                ) {
                    # 栈位置到数组后面array()
                    $assign = $lastCode = $tokens[$tokens[$assign]['parenthesis_opener']]['parenthesis_closer'];
                    continue;
                }


                if (isset(Tokens::$emptyTokens[$tokens[$assign]['code']]) === false) {

                    # 如果上一个变量语句与下一个变量语句间隔空行 则忽略
                    if (($tokens[$assign]['line'] - $tokens[$lastCode]['line']) > 1) {
                        break;
                    }

                    $lastCode = $assign;

                    if ($tokens[$assign]['code'] === T_SEMICOLON) {
                        if ($tokens[$assign]['conditions'] === $tokens[$stackPtr]['conditions']) {
                            if ($lastSemi !== null && $prevAssign !== null && $lastSemi > $prevAssign) {

                                # 声明中没有赋值运算符
                                break;
                            } else {
                                $lastSemi = $assign;
                            }
                        } else {
                            break;
                        }
                    }
                }

                continue;
            } else if ($assign !== $stackPtr && $tokens[$assign]['line'] === $lastLine) {
                # 没啥用
                continue;
            }

            if ($assign !== $stackPtr) {
                if ($tokens[$assign]['conditions'] !== $tokens[$stackPtr]['conditions']) {
                    break;
                }

                if (isset($tokens[$assign]['nested_parenthesis']) === true) {
                    foreach ($tokens[$assign]['nested_parenthesis'] as $start => $end) {
                        if (isset($tokens[$start]['parenthesis_owner']) === true) {
                            break(2);
                        }
                    }
                }
            }

            # 获取变量的栈位置
            $var = $phpcsFile->findPrevious(
                Tokens::$emptyTokens,
                ($assign - 1),
                null,
                true
            );

            # 获取变量值的栈位置
            $val = $phpcsFile->findPrevious(
                Tokens::$emptyTokens,
                ($assign + 1),
                null,
                true
            );

            # 获取变量后一个位置列数
            $varEnd    = $tokens[($var + 1)]['column'];
            $valStart  = $tokens[($val - 1)]['column'];

            # 获取变量和=间隔的空格长度
            # $assign =符号的栈位置
            $assignLen = $tokens[$assign]['length'];
            $assignValLen = $tokens[$assign+1]['length'];
            if ($assign !== $stackPtr) {
                if (($varEnd + 1) > $assignments[$prevAssign]['assign_col']) {
                    $padding      = 1;
                    $assignColumn = ($varEnd + 1);
                } else {
                    $padding = ($assignments[$prevAssign]['assign_col'] - $varEnd + $assignments[$prevAssign]['assign_len'] - $assignLen);
                    if ($padding <= 0) {
                        $padding = 1;
                    }

                    if ($padding > $this->maxPadding) {
                        $stopped = $assign;
                        break;
                    }

                    $assignColumn = ($varEnd + $padding);
                }

                if (($assignColumn + $assignLen) > ($assignments[$maxPadding]['assign_col'] + $assignments[$maxPadding]['assign_len'])) {
                    $newPadding = ($varEnd - $assignments[$maxPadding]['var_end'] + $assignLen - $assignments[$maxPadding]['assign_len'] + 1);
                    if ($newPadding > $this->maxPadding) {
                        $stopped = $assign;
                        break;
                    } else {
                        # 预分配的对齐参数
                        foreach ($assignments as $i => $data) {
                            if ($i === $assign) {
                                break;
                            }

                            $newPadding = ($varEnd - $data['var_end'] + $assignLen - $data['assign_len'] + 1);
                            $assignments[$i]['expected']   = $newPadding;
                            $assignments[$i]['assign_col'] = ($data['var_end'] + $newPadding);
                        }

                        $padding      = 1;
                        $assignColumn = ($varEnd + 1);
                    }
                } else if ($padding > $assignments[$maxPadding]['expected']) {
                    $maxPadding = $assign;
                }
            } else {
                $padding      = 1;
                $assignColumn = ($varEnd + 1);
                $maxPadding   = $assign;
            }

            $found = 0;
            if ($tokens[($var + 1)]['code'] === T_WHITESPACE) {
                $found = $tokens[($var + 1)]['length'];
                if ($found === 0) {
                    $found = 1;
                }
            }

            $assignments[$assign] = [
                'var_end'    => $varEnd,
                'assign_len' => $assignLen,
                'assign_col' => $assignColumn,
                'expected'   => $padding,
                'found'      => $found,
                'val_start'  => $valStart,
                'val_len'=> $assignValLen
            ];

            $lastLine   = $tokens[$assign]['line'];
            $prevAssign = $assign;
        }

        if (empty($assignments) === true) {
            return $stackPtr;
        }

        $numAssignments = count($assignments);

        $errorGenerated = false;

        # 判断=前面空格是否符合规范 默认1空格
        foreach ($assignments as $assignment => $data) {
            if ($data['found'] === $data['expected']) {
                continue;
            }

            $expectedText = $data['expected'].'空格';
            if ($data['expected'] !== 1) {
                $expectedText .= 's';
            }

            if ($data['found'] === null) {
                $foundText = 'a new line';
            } else {
                $foundText = $data['found'].'空格';
//                if ($data['found'] !== 1) {
//                    $foundText .= 's';
//                }
            }

            # 等号和变量间隔超过一个空格
            if ($numAssignments === 1) {
                $type  = '等号规范';
                $error = '3等号不正确对齐;规定%s,只有%s'. $data['val_len'];
            } else {
                # 等号和上下文等号没对齐
                $type  = '等号规范';
                $error = '当前等号与上下文等号没对齐;规定%s只有%s';
            }

            $errorData = [
                $expectedText,
                $foundText,
            ];

            if ($this->error === true) {
                $fix = $phpcsFile->addFixableError($error, $assignment, $type, $errorData);
            } else {
                $fix = $phpcsFile->addFixableWarning($error, $assignment, $type.'Warning', $errorData);
            }

            $errorGenerated = true;

            if ($fix === true && $data['found'] !== null) {
                $newContent = str_repeat(' ', $data['expected']);
                if ($data['found'] === 0) {
                    $phpcsFile->fixer->addContentBefore($assignment, $newContent);
                } else {
                    $phpcsFile->fixer->replaceToken(($assignment - 1), $newContent);
                }
            }
        }

        # 判断=后面空格是否符合规范 默认1空格
        foreach($assignments as $assignment => $data){
            if($data['val_len'] != 1){
                $type  = '等号规范';
                $error = '当前等号后面规定1空格,现有%s空格';
                $errorData = [
                    $data['val_len']
                ];
                $fix = $phpcsFile->addFixableError($error, $assignment, $type, $errorData);
            }
        }

        if ($numAssignments > 1) {
            if ($errorGenerated === true) {
                $phpcsFile->recordMetric($stackPtr, 'Adjacent assignments aligned', 'no');
            } else {
                $phpcsFile->recordMetric($stackPtr, 'Adjacent assignments aligned', 'yes');
            }
        }

        if ($stopped !== null) {
            return $this->checkAlignment($phpcsFile, $stopped);
        } else {
            return $assignment;
        }

    }
}
