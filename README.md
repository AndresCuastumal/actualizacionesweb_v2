# 🏛️ Sistema de Registro - Guía de Despliegue

Sistema web desarrollado en **PHP + MySQL + Apache** con dos roles de usuario:
- **Administrador**: Gestiona usuarios operadores, ve estadísticas y todos los registros.
- **Operador**: Accede al formulario de registro de datos y ve su historial.

---

## 📋 Requisitos del Servidor

| Requisito | Versión mínima |
|-----------|---------------|
| PHP | 7.4+ (recomendado 8.0+) |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Apache | 2.4+ |
| Extensiones PHP | `pdo`, `pdo_mysql`, `mbstring` |

---

## 🚀 Despliegue Paso a Paso

### PASO 1: Descargar y colocar los archivos

1. Descomprime el archivo `sistema_registro.zip`
2. Copia la carpeta `sistema_registro` dentro de tu directorio web:
   - **XAMPP/WAMP**: `C:\xampp\htdocs\sistema_registro`
   - **Linux (Apache)**: `/var/www/html/sistema_registro`
   - **cPanel**: `public_html/sistema_registro`

### PASO 2: Configurar la conexión a la base de datos

Edita el archivo: `config/database.php`

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_registro');
define('DB_USER', 'root');      // ← Tu usuario de MySQL
define('DB_PASS', '');          // ← Tu contraseña de MySQL
define('BASE_URL', 'http://localhost/sistema_registro/');  // ← Ajusta la URL
```

**Ejemplos de configuración:**

| Entorno | DB_USER | DB_PASS | BASE_URL |
|---------|---------|---------|----------|
| XAMPP local | `root` | `''` (vacío) | `http://localhost/sistema_registro/` |
| WAMP local | `root` | `''` (vacío) | `http://localhost/sistema_registro/` |
| Servidor cPanel | `usuario_bd` | `contraseña_bd` | `https://tudominio.com/sistema_registro/` |

### PASO 3: Crear la base de datos (Opción A - Automática)

1. Abre tu navegador y ve a:
   ```
   http://localhost/sistema_registro/sql/instalar.php
   ```
2. El script creará automáticamente:
   - La base de datos `sistema_registro`
   - La tabla `usuarios`
   - La tabla `registros`
   - El usuario admin por defecto
3. **⚠️ IMPORTANTE**: Después de instalar, **BORRA** el archivo `sql/instalar.php`

### PASO 3: Crear la base de datos (Opción B - Manual con phpMyAdmin)

1. Abre **phpMyAdmin** (`http://localhost/phpmyadmin`)
2. Crea una nueva base de datos llamada `sistema_registro`
3. Ve a la pestaña **SQL** y pega el contenido del archivo `sql/database.sql`
4. Ejecuta el script
5. Para crear el usuario admin, necesitas generar el hash de la contraseña. Crea un archivo temporal:
   ```php
   <?php echo password_hash('Admin2024!', PASSWORD_DEFAULT);
   ```
6. Copia el hash generado y actualiza el INSERT del admin en la base de datos.

### PASO 4: Verificar permisos (Linux/Mac)

Si estás en Linux, asegúrate de que Apache pueda leer los archivos:

```bash
sudo chown -R www-data:www-data /var/www/html/sistema_registro
sudo chmod -R 755 /var/www/html/sistema_registro
```

### PASO 5: Acceder al sistema

Abre tu navegador en:
```
http://localhost/sistema_registro/
```

**Credenciales por defecto:**
- **Usuario:** `admin`
- **Contraseña:** `Admin2024!`

---

## 📁 Estructura del Proyecto

```
sistema_registro/
├── config/
│   └── database.php          # Configuración de conexión MySQL
├── includes/
│   ├── functions.php         # Funciones comunes (sesiones, CSRF, utilidades)
│   ├── header.php            # Plantilla cabecera HTML
│   └── footer.php            # Plantilla pie HTML
├── assets/
│   └── css/
│       └── style.css         # Estilos personalizados
├── admin/
│   ├── dashboard.php         # Panel del administrador
│   └── usuarios.php          # CRUD de usuarios (crear, editar, eliminar)
├── operador/
│   ├── formulario.php        # Formulario de registro de datos
│   └── historial.php         # Historial de registros del operador
├── sql/
│   ├── database.sql          # Script SQL de la estructura
│   └── instalar.php          # Script de instalación automática
├── index.php                 # Redirección según rol
├── login.php                 # Página de inicio de sesión
├── logout.php                # Cerrar sesión
└── README.md                 # Esta guía
```

---

## 🔐 Seguridad implementada

| Medida | Descripción |
|--------|-------------|
| **Contraseñas hasheadas** | Usa `password_hash()` con bcrypt |
| **Prepared Statements** | Todas las consultas usan PDO con parámetros |
| **CSRF Tokens** | Protección contra ataques CSRF en todos los formularios |
| **Validación de roles** | Separación estricta entre admin y operador |
| **XSS Protection** | Todas las salidas usan `htmlspecialchars()` |
| **Sesiones PHP** | Control de acceso basado en sesiones |

---

## 👤 Flujo de uso

### Como Administrador:
1. Inicia sesión con `admin` / `Admin2024!`
2. En el **Dashboard** ves estadísticas y últimos registros
3. En **Gestión de Usuarios** creas operadores:
   - Nombre completo
   - Usuario (único)
   - Correo
   - Contraseña (mínimo 6 caracteres)
   - Rol: Operador o Administrador
4. Puedes editar, activar/desactivar o eliminar usuarios

### Como Operador:
1. Inicia sesión con las credenciales que te dio el admin
2. En **Nuevo Registro** llenas el formulario con los campos:
   - TD, DOC, APE1, APE2, NOM1, NOM2
   - FECHANACE, SEXO, PARENTESCO
   - NIVEL_SISBEN, FICHA, DISCAPACIDAD
   - GP, GP_SISBEN, MENSAJE, OBSERVACION
3. En **Mis Registros** ves tu historial con búsqueda y paginación

---

## ⚠️ Notas importantes

1. **Cambiar la contraseña del admin** inmediatamente después de instalar
2. **Borrar `sql/instalar.php`** después de la instalación
3. En producción, mover `config/database.php` fuera del directorio público si es posible
4. El sistema usa Bootstrap 5 vía CDN, requiere conexión a Internet para los estilos (o descargar Bootstrap localmente)

---

## 🛠️ Solución de problemas

| Problema | Solución |
|----------|----------|
| "Error de conexión a la base de datos" | Verifica `config/database.php` - usuario, contraseña y host |
| "Página en blanco" | Activa `display_errors` en php.ini o revisa logs de Apache |
| "No puedo iniciar sesión" | Asegúrate de que el hash de la contraseña en la BD sea correcto |
| "Acceso denegado a admin" | Verifica que el rol en la BD sea `administrador` |
| "No guarda registros" | Revisa que todos los campos obligatorios (*) estén llenos |

---

## 📞 Soporte

Para generar un nuevo hash de contraseña (si necesitas cambiar la del admin):
```php
<?php echo password_hash('TuNuevaContraseña', PASSWORD_DEFAULT); ?>
```

---

**Desarrollado para:** Alcaldía - Sistema de Registro de Datos  
**Tecnologías:** PHP 8, MySQL, Apache, Bootstrap 5
