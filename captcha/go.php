<?php
	// зададим имя куки для получения из неё кода капчи,
	// оно конечно же должно совпадать с соотв. именем в jcaptcha.php
	define('CAPTCHA_COOKIE', 'imgcaptcha_');
	// заметим: поле `captcha` обязательно для заполнения
	if(empty($_POST['captcha']) || md5($_POST['captcha']) != @$_COOKIE[CAPTCHA_COOKIE])
		$message = 'Неверный код с картинки. Вернитесь и повторите попытку.';
	else
		$message = 'Данные капчи введены верно!';
?>
<!doctype html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<title>jCaptcha - Шаг 2</title>
</head>
<body>
	<h3><?=$message?></h3>
</body>
</html>
