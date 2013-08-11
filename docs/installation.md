Installation
============

We recommend installing the extension with [composer](http://getcomposer.org/). Add this to
the `require` section of your `composer.json`:

    'codemix/oauth2yii' : 'dev-master'

> Note: There's no stable version yet.

You also need to include composer's autoloader on top of your `index.php`:

    require_once __DIR__.'/protected/vendor/autoload.php';

Make sure to fix the path to your composer's `vendor` directory. Finally you also need to
configure an `alias` in your `main.php`:

```
$vendor = realpath(__DIR__.'/../vendor');
return array(
    'alias' => array(
        'OAuth2Yii' => $vendor.'/codemix/oauth2yii/src/OAuth2Yii',
    ),
    ...
```
