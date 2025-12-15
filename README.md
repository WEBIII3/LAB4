# MyBox – Gestor de Archivos en Base de Datos
**Versión actual:** almacenamiento 100% en **MySQL (LONGBLOB)**  
**Ya NO usamos filesystem (/home/myboxusers)**

## Características

-  Login y registro de usuarios
-  Carpetas jerárquicas por usuario
-  Subida de archivos (máx. 20 MB)
-  Descarga directa desde la base de datos
-  Compartir archivos y carpetas
- Eliminación con CASCADE automático
-  Aislamiento total entre usuarios
- Sin acceso directo al filesystem

## Guia de instalacion
Guia de instalacion o uso de la plataforma mybox en **Linux**
Primero tener un servidor apache corriendo y poner la carpeta mybox en su directorio /html
despues de esto es importante que usted ejecute el query que se encuentra en esta misma rama (Examen 1)
Despues de esto usted va a tener que configurar las variales para la conexion con la base de datos
que acaba de crear, este archivo se encuentra en la carpeta /codigos de mybox, Se llama conexion.inc
revise bien los datos ingrasados ya que la base de la web es en mysql.
