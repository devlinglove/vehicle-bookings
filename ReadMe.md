composer require cloudinary/cloudinary_php --prefer-install=source


echo test > vendor\composer\cmd-test.txt
php -r "var_dump(file_put_contents('vendor/composer/php-test.txt', 'x'));"

If the echo works but the PHP line shows Permission denied and bool(false), Windows is blocking php.exe specifically. That's Controlled folder access.
If both fail, the folder itself is locked or read-only (see the last section).

del vendor\composer\tmp-*
set COMPOSER_MAX_PARALLEL_HTTP=1
composer require cloudinary/cloudinary_php