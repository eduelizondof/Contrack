# Seeders de Base de Datos

## Usuario de Prueba

### Ejecutar Seeder

Para crear el usuario de prueba, ejecuta uno de los siguientes comandos:

```bash
# Ejecutar solo el seeder de usuario de prueba
php artisan db:seed --class=UsuarioPruebaSeeder

# O ejecutar todos los seeders (incluye el de usuario de prueba)
php artisan db:seed
```

### Credenciales de Prueba

**⚠️ IMPORTANTE: Estas son credenciales de PRUEBA**

- **Email:** `admin@admin.com`
- **Contraseña:** `admin123`

**⚠️ ADVERTENCIA DE SEGURIDAD:**
- Esta contraseña es débil y solo debe usarse en desarrollo
- **DEBES cambiar esta contraseña en producción**
- No uses estas credenciales en un entorno público

### Comportamiento del Seeder

El seeder:
1. Verifica si el usuario ya existe
2. Si existe, pregunta si deseas actualizar la contraseña
3. Si no existe, crea el nuevo usuario
4. Muestra mensajes informativos en la consola
5. Recuerda cambiar la contraseña en producción

### Ejemplo de Salida

```
═══════════════════════════════════════════════════════
  Creando Usuario de Prueba
═══════════════════════════════════════════════════════

✅ Usuario creado exitosamente:
   ID: 1
   Nombre: Administrador
   Email: admin@admin.com

⚠️  IMPORTANTE: Seguridad
   Esta es una contraseña de PRUEBA y debe ser cambiada
   en un entorno de producción.

📋 Credenciales de acceso:
   Email: admin@admin.com
   Contraseña: admin123

═══════════════════════════════════════════════════════
```
