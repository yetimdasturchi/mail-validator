# Email validator

SMTP ( Simple Mail Transfer Protocol ) va MX yozuvlari orqali emaillarni validatsiya qilish.

## Foydalanish sabablari

Ro'yxatdan o'tish, obuna yoqish yoki ko'plab email tasdiqlash mumkin bo'lgan loyihalarda foydalanuvchilar tomonidan xato email kiritish ehtimollari yo'q emas. Bu holatda saytda xato emailga to'lgan foydalanuvchilar ko'payishidan tashqari, sizning email serveringiz spam bot sifatida qo'ra ro'yxatga ham tushishi mumkin. Kichik tajriba esa aynan shu holatlarni oldini olishga qaratilgan.

## Bu qanday ishlaydi?

1. Avvalo kiruvchi email formati tekshiriladi.
2. Kiritilgan email hostida mail server bor yoki yo'qligi tekshiriladi
3. Oxirgi bosqichda mail serveridan profayl bor yoki yo'qligi so'raladi.

## Kamchiliklar

Barcha mail serverlar uchun ham bir xil ishlamasligida, odatda profayl haqidagi ma'lumot server tomonidan taqdim etilmaydi. Shu sababli ommabop bo'lmagan mail serverlar uchun istisno sozlamalar qilishga to'g'ri keladi.

*Keyingi tajribalarda albatta bu borada ham  yechimlar berishga harakat qilamiz*

## Foydalanish

```php
<?php

include 'Validator.php';

$validator = new Validator();
$validator->setEmail('yetimdasturchi@gmail.com');

$status = $validator->isValid();
echo "isValid Method: " . PHP_EOL;
var_dump( $status );

echo str_repeat('-', 40) . PHP_EOL;

$status = $validator->isRfcValid();

echo "RFC Method: " . PHP_EOL;
var_dump( $status );

echo str_repeat('-', 40) . PHP_EOL;

echo "MX records: " . PHP_EOL;
$st = $validator->getMXrecords();
var_dump( $st );

echo str_repeat('-', 40) . PHP_EOL;

echo "check Method: " . PHP_EOL;
$st = $validator->check();
var_dump( $st );
```

```php
<?php
include 'Validator.php';

$validator = new Validator();
$validator->setEmail('blablanotexistsaccount123@gmail.com');

$st = $validator->check();
var_dump( $st );
```
