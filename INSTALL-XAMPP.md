# Install Notes for Windows with XAMPP

It may be handy to install the tools on
windows for testing and/or developing.
For such purposes you can install and
run the tools in cominbation with
XAMPP.

 1. Install XAMPP, e.g. in the folder `C:\xampp\`
 2. Clone malibu in the `htdocs` subfolder
 3. Download jQuery and place it in `.\htdocs\malibu\isbn`
 4. Install [`yaz`](http://www.indexdata.com/yaz) for windows.
 5. Download [`php_yaz.dll`](http://ftp.indexdata.dk/pub/phpyaz/windows/) (and possibly the other dll's) from IndexData
 5. Copy `php_yaz.dll` (and possibly the other dll's) into `.\php\ext`
 6. Open `.\php\php.ini` and add this line
 ```ini
 extension=php_yaz.dll
 ```
 and also check that `extension=php_openssl.dll` is activated.
 7. Start XAMPP
 8. Open `localhost/malibu` in your browser

## Remarks

Some components are available for 64-bit and/or x86 (32-bit) architecture. It is important that you use the compatible versions for all components, e.g. only use x86-architecture versions.
 
For me the following components are working together:
 * XAMPP 3.2.2 with PHP 7.0.8 (only available on x86-architecture)
 * yaz 5.15.2 for x86-architectures
 * php_yaz.dll for x86-architectures

## Open problems

Starting XAMPP there is an error because `libiconv.dll` is not found...

Anyway, it seems that the same functionality on windows can be achieved with docker and we should concentrate on that way. :sunglasses:
