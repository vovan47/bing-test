# bing-test
Bing API PHP SDK testing app - proof-of-concept

It will query your account for client account list, then campaigns for every client account, then ad groups, then ads.
Logs will be printed to stdout, with timestamp and memory usage.

Installation:

1) `$ composer install`
1) `$ cp parameters.php.dist parameters.php`
1) Edit `parameters.php` and provide your credentials
1) `$ php cli.php > logfile.log`
