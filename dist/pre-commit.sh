#!/bin/bash

#------------------------------------------------------------------------------
# NAME
#         pre-commit.sh [basedir]
#
# SYNOPSIS
#         ln -s ../../pre-commit.sh .git/hooks
#
#         git add ... && git commit;
#
# LICENSE
#        Placed in the Public Domain by Mannheim University Library in 2016
#
# DESCRIPTION
#        Runs the php-codesniffer.php script against any staged PHP files
#
#        If basedir is passed, run the checks on all files below basedir instead
#        of git staged files.
#
# SEE ALSO
#        phpcs(1), git-diff(1), shellcheck(1)
#------------------------------------------------------------------------------

# git-diff command
if [[ -z "$1" ]];then
  git_diff="git diff --cached --name-only --diff-filter=ACM"
else
  git_diff="find $1 -type f"
fi

# If there are any added or modified files staged for commit
staged=$($git_diff)
if [[ -z "$staged" ]];then
    echo "Nothing staged"
    exit
fi

# If any PHP files are staged
staged_php=($($git_diff|grep '\.php$'))
if [[ "${#staged_php[@]}" -ne 0 ]];then
    # Run PHP_CodeSniffer
    # echo "${staged_php[@]}"
    dist/php-codesniffer.sh -n "${staged_php[@]}" || exit $?
    echo "PHP sources OK"
fi

# If any Shell (bash) scripts are staged
staged_shell=($($git_diff|grep '\.sh$'))
if [[ "${#staged_shell[@]}" -ne 0 ]];then
    if which shellcheck >/dev/null; then
        out=$(shellcheck --shell bash "${staged_shell[@]}")
        if [[ ! -z "$out" ]];then
            echo "$out";
            exit 1;
        fi
        echo "Shell sources OK"
    else
        echo "shellcheck not installed (In Debian/Ubuntu: shellcheck)"
    fi
fi
