# Shielda Captcha for PHP (کتابخانه رسمی PHP شیلدا)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/shielda/captcha-php.svg?style=flat-square)](https://packagist.org/packages/shielda/captcha-php)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

کتابخانه رسمی و مستقل PHP برای اعتبارسنجی چالش‌های امنیتی سامانه **شیلدا (Shielda)**. این پکیج فاقد هرگونه وابستگی به فریم‌ورک‌های سنگین است و در هر پروژه و فریم‌ورک PHP (مانند Symfony، CodeIgniter، Slim یا کدهای PHP خام) قابل استفاده است.

---

## 📦 نصب با Composer

```bash
composer require shielda/captcha-php
```

---

## 🚀 نحوه استفاده

### ۱. نمایش کپچا در فرم HTML

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Shielda\Shielda;

$siteKey = 'your_site_key_here';
$secretKey = 'your_secret_key_here';

$shielda = new Shielda($siteKey, $secretKey);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ورود به سایت</title>
    <!-- لود اسکریپت شیلدا -->
    <?= $shielda->getScriptTag(); ?>
</head>
<body>
    <form method="POST" action="login.php">
        <input type="text" name="username" placeholder="نام کاربری" required>
        <input type="password" name="password" placeholder="کلمه عبور" required>

        <!-- نمایش باکس کپچا -->
        <?= $shielda->getWidgetTag(); ?>

        <button type="submit">ورود</button>
    </form>
</body>
</html>
```

### ۲. اعتبارسنجی در سرور (سمت Backend)

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Shielda\Shielda;

$shielda = new Shielda('YOUR_SITE_KEY', 'YOUR_SECRET_KEY');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // روش اول: اعتبارسنجی خودکار از $_POST
    if ($shielda->verify()) {
        echo "کپچا با موفقیت تایید شد! کاربر مجاز است.";
    } else {
        echo "خطا در تایید کپچا: " . $shielda->getLastError();
    }

    // یا روش دوم: ارسال دستی توکن
    $token = $_POST['darban-captcha-response'] ?? '';
    $details = $shielda->verifyWithDetails($token);
    /*
    خروجی نمونه $details:
    [
        'success' => true,
        'challenge_ts' => '2026-10-13T10:00:00Z',
        'hostname' => 'yourdomain.com'
    ]
    */
}
```

---

## 📄 لایسنس
[MIT License](LICENSE) - [https://shielda.ir](https://shielda.ir)
