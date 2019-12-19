<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4b9761e6b2abe31455f45787d3c61481
{
    public static $prefixLengthsPsr4 = array (
        'F' => 
        array (
            'FreeDSx\\Socket\\' => 15,
            'FreeDSx\\Sasl\\' => 13,
            'FreeDSx\\Ldap\\' => 13,
            'FreeDSx\\Asn1\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'FreeDSx\\Socket\\' => 
        array (
            0 => __DIR__ . '/..' . '/freedsx/socket/src/FreeDSx/Socket',
        ),
        'FreeDSx\\Sasl\\' => 
        array (
            0 => __DIR__ . '/..' . '/freedsx/sasl/src/FreeDSx/Sasl',
        ),
        'FreeDSx\\Ldap\\' => 
        array (
            0 => __DIR__ . '/..' . '/freedsx/ldap/src/FreeDSx/Ldap',
        ),
        'FreeDSx\\Asn1\\' => 
        array (
            0 => __DIR__ . '/..' . '/freedsx/asn1/src/FreeDSx/Asn1',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit4b9761e6b2abe31455f45787d3c61481::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit4b9761e6b2abe31455f45787d3c61481::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
