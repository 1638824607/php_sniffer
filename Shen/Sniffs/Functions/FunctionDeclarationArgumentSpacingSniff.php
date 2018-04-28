<?php
namespace PHP_CodeSniffer\Standards\Shen\Sniffs\Functions;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * 检查函数声明中的参数间隔是否正确
 * Class FunctionDeclarationArgumentSpacingSniff
 * @package PHP_CodeSniffer\Standards\Shen\Sniffs\Functions
 * @author shenruxiang
 * @date 2018/4/27 15:35
 */
class FunctionDeclarationArgumentSpacingSniff implements Sniff
{

    # 规定默认值=空格数
    public $equalsSpacing = 1;

    public $requiredSpacesAfterOpen = 0;

    public $requiredSpacesBeforeClose = 0;

    public $norn_name = '函数参数规范';

    public function register()
    {
        return [
            T_FUNCTION,
            T_CLOSURE,
        ];

    }

    /**
     * @param File $phpcsFile
     * @param      $stackPtr
     * @author shenruxiang
     * @date 2018/4/27 15:35
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (isset($tokens[$stackPtr]['parenthesis_opener']) === false
            || isset($tokens[$stackPtr]['parenthesis_closer']) === false
            || $tokens[$stackPtr]['parenthesis_opener'] === null
            || $tokens[$stackPtr]['parenthesis_closer'] === null
        ) {
            return;
        }

        $this->equalsSpacing           = (int) $this->equalsSpacing;
        $this->requiredSpacesAfterOpen = (int) $this->requiredSpacesAfterOpen;
        $this->requiredSpacesBeforeClose = (int) $this->requiredSpacesBeforeClose;

        $openBracket = $tokens[$stackPtr]['parenthesis_opener'];
        $this->processBracket($phpcsFile, $openBracket);

        if ($tokens[$stackPtr]['code'] === T_CLOSURE) {
            $use = $phpcsFile->findNext(T_USE, ($tokens[$openBracket]['parenthesis_closer'] + 1), $tokens[$stackPtr]['scope_opener']);
            if ($use !== false) {
                $openBracket = $phpcsFile->findNext(T_OPEN_PARENTHESIS, ($use + 1), null);
                $this->processBracket($phpcsFile, $openBracket);
            }
        }

    }

    /**
     * @param $phpcsFile
     * @param $openBracket
     * @author shenruxiang
     * @date 2018/4/27 15:35
     */
    public function processBracket($phpcsFile, $openBracket)
    {
        $tokens       = $phpcsFile->getTokens();
        $closeBracket = $tokens[$openBracket]['parenthesis_closer'];
        $multiLine    = ($tokens[$openBracket]['line'] !== $tokens[$closeBracket]['line']);

        $nextParam = $openBracket;
        $params    = [];
        while (($nextParam = $phpcsFile->findNext(T_VARIABLE, ($nextParam + 1), $closeBracket)) !== false) {
            $nextToken = $phpcsFile->findNext(T_WHITESPACE, ($nextParam + 1), ($closeBracket + 1), true);
            if ($nextToken === false) {
                break;
            }

            $nextCode = $tokens[$nextToken]['code'];

            if ($nextCode === T_EQUAL) {
                $spacesBefore = 0;
                if (($nextToken - $nextParam) > 1) {
                    $spacesBefore = strlen($tokens[($nextParam + 1)]['content']);
                }

                if ($spacesBefore !== $this->equalsSpacing) {
                    $error = '"="和参数间隔不规范,规定'.$this->equalsSpacing . '空格,在%s后面';
                    $data  = [
                        $tokens[$nextParam]['content'],
                        $spacesBefore,
                    ];

                    $fix = $phpcsFile->addFixableError($error, $nextToken, $this->norn_name, $data);
                    if ($fix === true) {
                        $padding = str_repeat(' ', $this->equalsSpacing);
                        if ($spacesBefore === 0) {
                            $phpcsFile->fixer->addContentBefore($nextToken, $padding);
                        } else {
                            $phpcsFile->fixer->replaceToken(($nextToken - 1), $padding);
                        }
                    }
                }//end if

                $spacesAfter = 0;
                if ($tokens[($nextToken + 1)]['code'] === T_WHITESPACE) {
                    $spacesAfter = strlen($tokens[($nextToken + 1)]['content']);
                }

                if ($spacesAfter !== $this->equalsSpacing) {
                    $error = '"="和参数间隔不规范,规定'.$this->equalsSpacing . '空格';
                    $data  = [
                        $tokens[$nextParam]['content'],
                        $spacesAfter,
                    ];

                    $fix = $phpcsFile->addFixableError($error, $nextToken, $this->norn_name, $data);
                    if ($fix === true) {
                        $padding = str_repeat(' ', $this->equalsSpacing);
                        if ($spacesAfter === 0) {
                            $phpcsFile->fixer->addContent($nextToken, $padding);
                        } else {
                            $phpcsFile->fixer->replaceToken(($nextToken + 1), $padding);
                        }
                    }
                }
            }

            $nextComma = $phpcsFile->findNext(T_COMMA, ($nextParam + 1), $closeBracket);
            if ($nextComma !== false) {
                if ($tokens[($nextComma - 1)]['code'] === T_WHITESPACE) {
                    $error = '参数%s的值和","规定0空格,现有%s空格';
                    $data  = [
                        $tokens[$nextParam]['content'],
                        strlen($tokens[($nextComma - 1)]['content']),
                    ];

                    $fix = $phpcsFile->addFixableError($error, $nextToken, $this->norn_name, $data);
                    if ($fix === true) {
                        $phpcsFile->fixer->replaceToken(($nextComma - 1), '');
                    }
                }
            }

            $checkToken = ($nextParam - 1);
            $prev       = $phpcsFile->findPrevious(T_WHITESPACE, $checkToken, null, true);
            if ($tokens[$prev]['code'] === T_ELLIPSIS) {
                $checkToken = ($prev - 1);
            }

            if ($phpcsFile->isReference($checkToken) === true) {
                $whitespace = ($checkToken - 1);
            } else {
                $whitespace = $checkToken;
            }

            if (empty($params) === false) {
                // This is not the first argument in the function declaration.
                $arg = $tokens[$nextParam]['content'];

                // Before we throw an error, make sure there is no type hint.
                $comma     = $phpcsFile->findPrevious(T_COMMA, ($nextParam - 1));
                $nextToken = $phpcsFile->findNext(Tokens::$emptyTokens, ($comma + 1), null, true);
                if ($phpcsFile->isReference($nextToken) === true) {
                    $nextToken++;
                }

                $gap = 0;
                if ($tokens[$whitespace]['code'] === T_WHITESPACE) {
                    $gap = strlen($tokens[$whitespace]['content']);
                }

                # 类型提示 用不到
                if ($nextToken !== $nextParam) {
                    // There was a type hint, so check the spacing between
                    // the hint and the variable as well.
                    $hint = $tokens[$nextToken]['content'];

                    if ($gap !== 1) {
                        $error = 'Expected 1 space between type hint and argument "%s"; %s found';
                        $data  = [
                            $arg,
                            $gap,
                        ];
                        $fix   = $phpcsFile->addFixableError($error, $nextToken, $this->norn_name, $data);
                        if ($fix === true) {
                            if ($gap === 0) {
                                $phpcsFile->fixer->addContent($whitespace, ' ');
                            } else {
                                $phpcsFile->fixer->replaceToken($whitespace, ' ');
                            }
                        }
                    }

                    if ($multiLine === false) {
                        if ($tokens[($comma + 1)]['code'] !== T_WHITESPACE) {
                            $error = 'Expected 1 space between comma and type hint "%s"; 0 found';
                            $data  = [$hint];
                            $fix   = $phpcsFile->addFixableError($error, $nextToken, $this->norn_name, $data);
                            if ($fix === true) {
                                $phpcsFile->fixer->addContent($comma, ' ');
                            }
                        } else {
                            $gap = strlen($tokens[($comma + 1)]['content']);
                            if ($gap !== 1) {
                                $error = 'Expected 1 space between comma and type hint "%s"; %s found';
                                $data  = [
                                    $hint,
                                    $gap,
                                ];
                                $fix   = $phpcsFile->addFixableError($error, $nextToken, $this->norn_name, $data);
                                if ($fix === true) {
                                    $phpcsFile->fixer->replaceToken(($comma + 1), ' ');
                                }
                            }
                        }//end if
                    }//end if
                } else {
                    if ($gap === 0) {
                        $error = '","与参数规定一个空格,现0空格';
                        $data  = [$arg];
                        $fix   = $phpcsFile->addFixableError($error, $nextToken, $this->norn_name, $data);
                        if ($fix === true) {
                            $phpcsFile->fixer->addContent($whitespace, ' ');
                        }
                    } else if ($gap !== 1) {
                        // Just make sure this is not actually an indent.
                        if ($tokens[$whitespace]['line'] === $tokens[($whitespace - 1)]['line']) {
                            $error = 'Expected 1 space between comma and argument "%s"; %s found';
                            $error = '","和参数%s规定一个空格,现%s空格';
                            $data  = [
                                $arg,
                                $gap,
                            ];

                            $fix = $phpcsFile->addFixableError($error, $nextToken, $this->norn_name, $data);
                            if ($fix === true) {
                                $phpcsFile->fixer->replaceToken($whitespace, ' ');
                            }
                        }
                    }//end if
                }//end if
            } else {
                $gap = 0;
                if ($tokens[$whitespace]['code'] === T_WHITESPACE) {
                    $gap = strlen($tokens[$whitespace]['content']);
                }

                $arg = $tokens[$nextParam]['content'];

                // Before we throw an error, make sure there is no type hint.
                $bracket   = $phpcsFile->findPrevious(T_OPEN_PARENTHESIS, ($nextParam - 1));
                $nextToken = $phpcsFile->findNext(Tokens::$emptyTokens, ($bracket + 1), null, true);
                if ($phpcsFile->isReference($nextToken) === true) {
                    $nextToken++;
                }

                if ($tokens[$nextToken]['code'] !== T_ELLIPSIS && $nextToken !== $nextParam) {
                    // There was a type hint, so check the spacing between
                    // the hint and the variable as well.
                    $hint = $tokens[$nextToken]['content'];

                    if ($gap !== 1) {
                        $error = 'Expected 1 space between type hint and argument "%s"; %s found';
                        $data  = [
                            $arg,
                            $gap,
                        ];
                        $fix   = $phpcsFile->addFixableError($error, $nextToken, $this->norn_name, $data);
                        if ($fix === true) {
                            if ($gap === 0) {
                                $phpcsFile->fixer->addContent($nextToken, ' ');
                            } else {
                                $phpcsFile->fixer->replaceToken(($nextToken + 1), ' ');
                            }
                        }
                    }

                    $spaceAfterOpen = 0;
                    if ($tokens[($bracket + 1)]['code'] === T_WHITESPACE) {
                        $spaceAfterOpen = strlen($tokens[($bracket + 1)]['content']);
                    }

                    if ($multiLine === false && $spaceAfterOpen !== $this->requiredSpacesAfterOpen) {
                        $error = 'Expected %s spaces between opening bracket and type hint "%s"; %s found';
                        $data  = [
                            $this->requiredSpacesAfterOpen,
                            $hint,
                            $spaceAfterOpen,
                        ];
                        $fix   = $phpcsFile->addFixableError($error, $nextToken, $this->norn_name, $data);
                        if ($fix === true) {
                            $padding = str_repeat(' ', $this->requiredSpacesAfterOpen);
                            if ($spaceAfterOpen === 0) {
                                $phpcsFile->fixer->addContent($openBracket, $padding);
                            } else {
                                $phpcsFile->fixer->replaceToken(($openBracket + 1), $padding);
                            }
                        }
                    }
                } else if ($multiLine === false && $gap !== $this->requiredSpacesAfterOpen) {
                    $error     = '参数与"("规定%s空格,现有%s空格';
                    $data  = [
                        $this->requiredSpacesAfterOpen,
                        $arg,
                        $gap,
                    ];
                    $fix   = $phpcsFile->addFixableError($error, $nextToken, $this->norn_name, $data);
                    if ($fix === true) {
                        $padding = str_repeat(' ', $this->requiredSpacesAfterOpen);
                        if ($gap === 0) {
                            $phpcsFile->fixer->addContent($openBracket, $padding);
                        } else {
                            $phpcsFile->fixer->replaceToken(($openBracket + 1), $padding);
                        }
                    }
                }
            }

            $params[] = $nextParam;
        }

        $gap = 0;
        if ($tokens[($closeBracket - 1)]['code'] === T_WHITESPACE) {
            $gap = strlen($tokens[($closeBracket - 1)]['content']);
        }

        if (empty($params) === true) {
            // 空参数
            if (($closeBracket - $openBracket) !== 1) {
                $error = '空参数规定没有空格,现有%s空格';
                $data  = [$gap];
                $fix   = $phpcsFile->addFixableError($error, $openBracket, $this->norn_name, $data);
                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken(($openBracket + 1), '');
                }
            }
        } else if ($multiLine === false && $gap !== $this->requiredSpacesBeforeClose) {
            $lastParam = array_pop($params);
            $error     = '参数与")"规定%s空格,现有%s空格';
            $data      = [
                $this->requiredSpacesBeforeClose,
                $tokens[$lastParam]['content'],
                $gap,
            ];
            $fix       = $phpcsFile->addFixableError($error, $closeBracket, $this->norn_name, $data);
            if ($fix === true) {
                $padding = str_repeat(' ', $this->requiredSpacesBeforeClose);
                if ($gap === 0) {
                    $phpcsFile->fixer->addContentBefore($closeBracket, $padding);
                } else {
                    $phpcsFile->fixer->replaceToken(($closeBracket - 1), $padding);
                }
            }
        }
    }
}