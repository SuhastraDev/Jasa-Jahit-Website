
(Get-Content resources\views\user\chat\index.blade.php) -replace '@push\(''scripts''\)', '' -replace '@endpush', '' | Set-Content resources\views\user\chat\index.blade.php -Encoding UTF8
(Get-Content resources\views\admin\chat\index.blade.php) -replace '@push\(''scripts''\)', '' -replace '@endpush', '' | Set-Content resources\views\admin\chat\index.blade.php -Encoding UTF8

