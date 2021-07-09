PHP_ARG_WITH(yaz,for YAZ support,
[  --with-yaz[=DIR]        Include YAZ support (ANSI/NISO Z39.50).
                          DIR is the YAZ bin install directory.])


if test "$PHP_YAZ" != "no"; then
  if `pkg-config --exists yaz`; then
    AC_DEFINE(HAVE_YAZ,1,[Whether you have YAZ])
    AC_MSG_CHECKING([for YAZ version via pkg-config])
    if `pkg-config --atleast-version=3.0.2 yaz`; then
      AC_MSG_RESULT([$YAZVERSION])
    else
      AC_MSG_ERROR([YAZ version 3.0.2 or later required.])
    fi

    prefix=`pkg-config --variable=prefix yaz`
    exec_prefix=`pkg-config --variable=exec_prefix yaz`
    libdir=`pkg-config --variable=libdir yaz`

    YAZLIB=`pkg-config --libs yaz`
    AC_MSG_NOTICE([YAZLIB: $YAZLIB])
    for c in $YAZLIB; do
      case $c in
       -L*)
         dir=`echo $c|cut -c 3-|sed 's%/\.libs%%g'`
         PHP_ADD_LIBPATH($dir,YAZ_SHARED_LIBADD)
        ;;
       -l*)
         lib=`echo $c|cut -c 3-`
         PHP_ADD_LIBRARY($lib,,YAZ_SHARED_LIBADD)
        ;;
      esac
    done
    YAZINC=`pkg-config --cflags yaz`
    AC_MSG_NOTICE([YAZINC: $YAZINC])
    PHP_EVAL_INCLINE($YAZINC)
    PHP_NEW_EXTENSION(yaz, php_yaz.c, $ext_shared)
    PHP_SUBST(YAZ_SHARED_LIBADD)
  else
    AC_MSG_ERROR([YAZ not found (missing $yazconfig)])
  fi
fi
