# Base SPA - Arquitectura y Documentación

## Descripción General

Base SPA es una aplicación de **Single Page Application** (SPA) moderna que utiliza **Laravel** como backend (API REST) y **Vue.js 3** como frontend, con soporte para aplicaciones móviles mediante **Capacitor.js**.

La arquitectura sigue un enfoque modular tanto en el backend como en el frontend, facilitando la escalabilidad y mantenibilidad del código.

## Arquitectura del Proyecto

```
BaseSPA/
├── backend/          # API REST con Laravel
├── frontend/         # SPA con Vue.js 3 + Capacitor
└── start-dev.ps1    # Script de desarrollo
```

### Separación de Responsabilidades

- **Backend (Laravel)**: Proporciona una API REST autenticada con Sanctum, maneja la lógica de negocio, base de datos, y permisos con Spatie Permission.
- **Frontend (Vue.js 3)**: Aplicación cliente que consume la API, construida con Composition API, Pinia para estado global, y Capacitor para soporte móvil.

---

## Backend - Laravel

### Características Principales

- **Framework**: Laravel 11
- **Autenticación**: Laravel Sanctum (SPA Authentication)
- **Permisos**: Spatie Permission (roles y permisos)
- **Auditoría**: Activity Log automático mediante BaseModel
- **Timestamps Personalizados**: `creado_el`, `actualizado_el`, `eliminado_el`
- **Soft Deletes**: Eliminación suave en todos los modelos

### Estructura de Carpetas

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/[Modulo]/Controlador[Nombre].php
│   │   ├── Requests/[Modulo]/Solicitud[Accion][Recurso].php
│   │   ├── Resources/[Modulo]/Recurso[Modelo].php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── BaseModel.php        # Modelo base con Activity Log
│   │   ├── User.php             # Modelo de usuario (en inglés)
│   │   └── [Modelo].php         # Modelos que extienden BaseModel
│   ├── Policies/[Modulo]/Politica[Modelo].php
│   └── Services/[Modulo]/Servicio[Nombre].php
├── routes/
│   ├── api.php
│   ├── autenticacion.php
│   └── app/[modulo].php         # Rutas organizadas por módulo
└── database/
    ├── migrations/[Modulo]/     # Migraciones organizadas por módulo
    └── seeders/
```

### Convenciones de Nomenclatura

#### Controladores
- **Formato**: `Controlador[Nombre]` (PascalCase)
- **Ubicación**: `app/Http/Controllers/[Modulo]/Controlador[Nombre].php`
- **Métodos estándar**:
  - `index()` - Listar recursos
  - `mostrar($id)` - Mostrar recurso específico
  - `almacenar(Request $request)` - Crear recurso
  - `actualizar(Request $request, $id)` - Actualizar recurso
  - `eliminar($id)` - Eliminar recurso

#### Requests (Validación)
- **Formato**: `Solicitud[Accion][Recurso]` (PascalCase)
- **Ejemplos**: `SolicitudLogin`, `SolicitudCrearMensaje`, `SolicitudActualizarNotificacion`

#### Resources (Transformación de Datos)
- **Formato**: `Recurso[Modelo]` (PascalCase)
- **Ubicación**: `app/Http/Resources/[Modulo]/Recurso[Modelo].php`

#### Models
- **Formato**: PascalCase en español
- **IMPORTANTE**: Todos los modelos deben extender `BaseModel` (excepto `User`)
- **BaseModel incluye**:
  - SoftDeletes
  - Activity Log automático (Spatie)
  - Timestamps personalizados: `creado_el`, `actualizado_el`, `eliminado_el`

#### Migrations
- **Organización**: Por módulo en subcarpetas `database/migrations/[Modulo]/`
- **Blueprint Auditable**: Siempre usar `$table->auditable()` en lugar de `timestamps()` y `softDeletes()`
- **Formato de archivo**: `[timestamp]_[accion]_[tabla]_table.php` (snake_case)

### BaseModel y Activity Log

Todos los modelos (excepto `User`) extienden `BaseModel`, que proporciona:

1. **SoftDeletes**: Eliminación suave de registros
2. **Activity Log**: Registro automático de cambios (crear, actualizar, eliminar, restaurar)
3. **Timestamps Personalizados**: Campos `creado_el`, `actualizado_el`, `eliminado_el`

El Activity Log se configura automáticamente y registra cambios solo en campos `fillable`.

### Autenticación

- **Método**: Laravel Sanctum (SPA Authentication)
- **Tipo**: Stateful con cookies HTTP-only
- **CSRF**: Requerido para operaciones POST/PUT/DELETE
- **Middleware**: `auth:sanctum` para rutas protegidas

### Respuestas API Estándar

#### Éxito Simple
```php
return response()->json([
    'mensaje' => 'Operación exitosa',
    'datos' => new RecursoModelo($modelo),
]);
```

#### Lista Paginada
```php
return response()->json([
    'datos' => RecursoModelo::collection($modelos),
    'meta' => [
        'total' => $modelos->total(),
        'por_pagina' => $modelos->perPage(),
        'pagina_actual' => $modelos->currentPage(),
    ],
]);
```

### Crear un Nuevo Módulo

1. Crear estructura de carpetas (`Controllers`, `Requests`, `Resources`, `migrations`)
2. Crear Controlador: `Controlador[Nombre].php`
3. Crear Requests: `SolicitudCrear[Modelo].php`, `SolicitudActualizar[Modelo].php`
4. Crear Resource: `Recurso[Modelo].php`
5. Crear Modelo: `[Modelo].php` (extiende `BaseModel`)
6. Crear Migración: `create_[tabla]_table.php` (usar `$table->auditable()`)
7. Crear Rutas: `routes/app/[modulo].php`

---

## Frontend - Vue.js 3

### Características Principales

- **Framework**: Vue.js 3 con Composition API
- **Estado Global**: Pinia (Composition API)
- **Routing**: Vue Router con guards de autenticación y permisos
- **HTTP Client**: Axios configurado para Sanctum
- **Mobile**: Capacitor.js para Android e iOS
- **Estilos**: CSS con variables semánticas (soporte dark mode)
- **Progreso**: nprogress para indicadores de carga

### Estructura de Carpetas

```
frontend/src/
├── components/
│   ├── globales/          # Componentes reutilizables
│   │   ├── iconos/        # Iconos SVG
│   │   ├── inputs/        # Inputs de formulario
│   │   └── modales/       # Modales globales
│   └── vistas/            # Componentes específicos de vistas
│       └── [modulo]/       # Organizados por módulo
├── composables/           # Lógica reutilizable
│   ├── useApi.js
│   ├── usePlatform.js
│   ├── useForm.js
│   └── useProgress.js
├── layouts/               # Plantillas de la aplicación
│   ├── PlantillaApp.vue   # Para vistas autenticadas
│   └── PlantillaLogin.vue # Para autenticación
├── views/                 # Vistas principales (páginas)
│   ├── app/               # Vistas autenticadas
│   └── autenticacion/     # Vistas de login
├── stores/                # Stores de Pinia
│   ├── auth.js            # Autenticación
│   └── theme.js           # Tema (light/dark)
├── services/              # Servicios de API
│   ├── api.js             # Configuración base
│   ├── auth.js            # Servicio de autenticación
│   └── csrf.js            # Manejo de CSRF
└── assets/
    └── styles/            # Estilos globales
        ├── base/          # Variables CSS
        └── views/         # Estilos por vista
```

### Convenciones de Nomenclatura

#### Vistas
- **Formato**: `Vista[Nombre]` (PascalCase)
- **Ubicación**: `views/[modulo]/Vista[Nombre].vue`
- **Ejemplo**: `VistaInicio.vue`, `VistaLogin.vue`

#### Componentes Globales
- **Formato**: PascalCase en español
- **Ubicación**: `components/globales/[categoria]/[Nombre].vue`
- **Categorías**: `iconos/`, `inputs/`, `modales/`

#### Componentes de Vista
- **Formato**: PascalCase descriptivo
- **Ubicación**: `components/vistas/[modulo]/[Nombre].vue`
- **Uso**: Componentes específicos para una vista

#### Stores
- **Formato**: camelCase en español
- **Ubicación**: `stores/[nombre].js`
- **Ejemplos**: `auth.js`, `theme.js`, `modulo.js`

#### Services
- **Formato**: camelCase
- **Ubicación**: `services/[nombre].js`

#### Composables
- **Formato**: camelCase con prefijo `use`
- **Ubicación**: `composables/use[Nombre].js`
- **Ejemplos**: `useApi.js`, `usePlatform.js`, `useForm.js`

### Stores de Pinia

#### Store de Autenticación (`auth.js`)
- **Estado**: `usuario`, `cargando`, `autenticado`
- **Getters**: `estaAutenticado` (computed)
- **Acciones**: `inicializar()`, `login()`, `logout()`, `tienePermiso()`, `tieneRol()`
- **Inicialización**: En `main.js` antes de montar la app

#### Store de Tema (`theme.js`)
- **Estado**: `theme` ('light'|'dark'|'system'), `currentTheme` (resuelto)
- **Acciones**: `initializeTheme()`, `setTheme()`, `toggleTheme()`
- **Inicialización**: En `main.js` antes de montar la app

### Autenticación con Sanctum

1. **CSRF Token**: Obtener antes de operaciones POST/PUT/DELETE
   ```javascript
   await getCsrfToken()
   await api.post('/endpoint', data)
   ```

2. **Cookies**: Configurado con `withCredentials: true` en Axios
3. **Guards de Router**: Verificación de autenticación y permisos en rutas

### Guards de Router

El router verifica automáticamente:
- Autenticación (`meta.requiereAuth`)
- Permisos (`meta.permisos`)
- Roles (`meta.roles`)

```javascript
{
  path: '/ruta',
  name: 'nombre',
  component: () => import('@/views/app/VistaNombre.vue'),
  meta: {
    requiereAuth: true,
    permisos: ['modulo.ver'],
    roles: ['admin']
  }
}
```

### Paleta de Colores (Variables CSS)

El proyecto usa variables CSS semánticas para mantener consistencia entre temas:

#### Tema Claro
```css
--color-background: #ffffff;
--color-surface-bg: #ffffff;
--color-content-bg: #f5f8fa;
--color-text: #2c3e50;
--color-text-muted: #64748b;
--color-border: rgba(60, 60, 60, 0.12);
--color-primary: #3b82f6;
```

#### Tema Oscuro
```css
--color-background: #111111;
--color-surface-bg: #111111;
--color-content-bg: #161616;
--color-text: #e0e0e0;
--color-text-muted: #a0a0a0;
--color-border: #2a2a2a;
--color-primary: #3b82f6;
```

**Regla**: Siempre usar variables semánticas, nunca colores hardcodeados.

### Capacitor.js - Soporte Móvil

- **Configuración**: `capacitor.config.json` en raíz de `frontend/`
- **Build**: `npm run build` → `npx cap sync` → `npx cap open android/ios`
- **Variables de Entorno**: URLs absolutas en mobile (usar `VITE_API_BASE_URL`)
- **Detección de Plataforma**: Composable `usePlatform()`

### Lineamientos de Diseño

#### Encabezados
- Título (`h1.page-title`) y subtítulo descriptivo (`p.page-subtitle`)
- Acciones principales en el encabezado (ej: botón "Nuevo")

#### Listados
- **Evitar tablas tradicionales**: Usar cards o filas con diseño premium
- Micro-animaciones en hover
- Acciones claras (Ver, Editar, Eliminar)

#### Vistas de Detalle
- Secciones colapsables organizadas lógicamente
- Espaciado consistente entre bloques
- Guardado por sección cuando sea posible

---

## Desarrollo

### Configuración Inicial

1. **Backend**:
   ```bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   php artisan serve
   ```

2. **Frontend**:
   ```bash
   cd frontend
   npm install
   cp .env.example .env
   # Configurar VITE_API_BASE_URL=http://localhost:8000/api
   npm run dev
   ```

### Script de Desarrollo

El proyecto incluye `start-dev.ps1` para iniciar ambos servidores simultáneamente (Windows PowerShell).

### Variables de Entorno

#### Backend (.env)
```env
APP_NAME=BaseSPA
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
# ... configuración estándar de Laravel
```

#### Frontend (.env)
```env
VITE_API_BASE_URL=http://localhost:8000/api
```

---

## Convenciones Importantes

### Idioma
- **Código y mensajes al usuario**: Español
- **Excepción**: Modelo `User` y algunos archivos del framework se mantienen en inglés

### Estilo de Código
- **Backend**: PSR-12 (PHP), nomenclatura en español
- **Frontend**: ESLint configurado, Composition API estándar

### Reglas Fundamentales

1. **Backend**:
   - Todos los modelos extienden `BaseModel` (excepto `User`)
   - Siempre usar `$table->auditable()` en migraciones
   - Organizar migraciones por módulo en subcarpetas
   - Mensajes siempre en español

2. **Frontend**:
   - Usar Composition API (`<script setup>`)
   - Stores críticos se inicializan en `main.js`
   - Siempre usar variables CSS semánticas
   - Lazy loading para rutas y componentes pesados

---

## Recursos Adicionales

### Documentación Técnica
- **Backend**: Ver `.cursor/rules/backend.mdc` para reglas detalladas
- **Frontend**: Ver `.cursor/rules/frontend.mdc` para reglas detalladas

### Tecnologías Utilizadas

**Backend:**
- Laravel 11
- Laravel Sanctum
- Spatie Permission
- Spatie Activity Log
- MySQL/PostgreSQL

**Frontend:**
- Vue.js 3 (Composition API)
- Pinia
- Vue Router
- Axios
- Capacitor.js
- nprogress

---

## Mantenimiento y Contribución

### Agregar Nuevos Módulos

Seguir las convenciones establecidas en las secciones correspondientes de este documento. Consultar los archivos de reglas (`.cursor/rules/`) para detalles específicos de implementación.

### Testing

- **Backend**: PHPUnit (configurado)
- **Frontend**: Pruebas manuales recomendadas en desarrollo

---

## Licencia

[Especificar licencia del proyecto]

---

**Última actualización**: Enero 2026
