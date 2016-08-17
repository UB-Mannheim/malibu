# Install Notes for Windows with XAMPP

It may be handy to install the tools on
windows for testing and/or developing.
For such purposes you can install and
run the tools in cominbation with
XAMPP.

 1. Install [XAMPP](https://www.apachefriends.org/), e.g. in the folder `C:\xampp\`
 2. Clone malibu in the `htdocs` subfolder
 3. Download [jQuery](https://code.jquery.com/jquery-2.1.1.min.js) and place it in `.\htdocs\malibu\isbn`
 4. Install [`yaz`](http://www.indexdata.com/yaz) for windows and make sure that the `bin` directory of `YAZ` is part of the [PATH environment variable](https://cloud.githubusercontent.com/assets/5199995/17752243/2fcc2c92-64cb-11e6-915e-02879865ed8f.png).
 5. Download [`php_yaz.dll`](http://ftp.indexdata.dk/pub/phpyaz/windows/) (and possibly the other dll's) from IndexData
 6. Copy `php_yaz.dll` (and possibly the other dll's) into `.\php\ext`
 7. Open `.\php\php.ini` and add this line
 ```ini
 extension=php_yaz.dll
 ```
 and also check that `extension=php_openssl.dll` is activated.
 8. Copy `isbn\conf.example.php` to `isbn\conf.php` and copy `isbn\paketinfo.example.js` to `isbn\paketinfo.js` and costumize the values
 9. Start XAMPP
 10. Open `localhost/malibu` in your browser

## Remarks

It is also possible to copy the dll's from the `yaz` directory to the `apache\bin` directory and then you don't need to bother about the path environment variable and you can even deinstall `yaz` again, cf. https://blog.verweisungsform.de/2009-08-17/yaz-php-apache-und-windows/.

Some components are available for 64-bit and/or x86 (32-bit) architecture. It is important that you use the compatible versions for all components, e.g. only use x86-architecture versions.
 
For me the following components are working together:
 * XAMPP 3.2.2 with PHP 7.0.8 (only available on x86-architecture)
 * yaz 5.15.2 for x86-architectures
 * php_yaz.dll for x86-architectures

Anyway, it seems that the same functionality on windows can be achieved with docker and we should concentrate on that way. :sunglasses:
