discord-rss
==============
Publica el contenido de un rss a un webhook

Modo de uso
==============

1. Editar _main.php_ con tu editor de texto favorito
2. Editar la url del Webhook
	```php
	const WEBHOOK = 'https://discordapp.com/api/webhooks/...';
	```
2. Editar la url del rss a cargar
	```php
	$rss->load('https://...', Rss::ANCHOR_TO_MD);
	```
3. Ejecutar desde consola
	```cmd
	>php main.php
	```
Por implementar
==================
  * daemon que publique automáticamente el nuevo contenido
