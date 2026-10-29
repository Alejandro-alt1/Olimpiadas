INSTALACIÓN (XAMPP)
1. Copia la carpeta "olimpiadas" dentro de C:\xampp\htdocs\
2. Inicia Apache y MySQL en el panel de XAMPP.
3. phpMyAdmin > pestaña SQL (o Importar):
     a) Si es primera instalación: ejecuta database.sql
     b) Luego ejecuta database_fase2.sql  (agrega los retos; no borra equipos ni puntos)
4. Abre http://localhost/olimpiadas/login.php?rol=facilitador y crea la cuenta de facilitador (solo la primera vez).
5. Códigos de capitán: Panel > Equipos.
6. Carpeta uploads/ debe poder escribirse (en XAMPP/Windows normalmente ya puede).
Requiere PHP 8.0+ con extensión fileinfo activa (viene activa en XAMPP).
Si cambias el nombre de la carpeta, ajusta BASE_URL en config.php.
